<?php
namespace AronSzigetvari\TestSelector;

use PDO;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;
use PHP_Token_Stream as TokenStream;

/**
 * @author Áron Szigetvári
 */

class TestListener implements TestListenerInterface
{
    use TestListenerDefaultImplementation;

    const LINE_COVERED = 1;
    const LINE_NOT_COVERED = -1;

    /**
     * @var PDO
     */
    private $pdo;

    private $coverageWhitelist;

    /**
     * @var string
     */
    private $repositoryPath;

    private $currentCommit;

    private $currentStateId;

    private $functionIds = [];

    private $maps = [
        'test' => [],
        'source_file' => [],
        'state' => []
    ];

    private $testMap = [];

    /**
     * @var Wizard
     */
    private $wizard;

    public function  __construct(
        array $coverageWhitelist,
        string $repositoryPath,
        string $dsn,
        string $username = 'root',
        string $password = ''
    ) {
        $this->pdo = new PDO($dsn, $username, $password);
        $this->coverageWhitelist = $coverageWhitelist;
        $this->repositoryPath = $repositoryPath;

        //$this->setupCodeCoverage();
        $this->currentCommit = $this->readCurrentCommitId();


        //$this->wizard = new Wizard();
        $this->prepareDatabase();
    }

    private function prepareDatabase()
    {
        $this->currentStateId = $this->saveOrRetrieveStateId($this->currentCommit);

        $stmt = $this->pdo->prepare("DELETE FROM depends WHERE state_id = ?");
        $stmt->execute([$this->currentStateId]);
    }

    private function readCurrentCommitId()
    {
        $output = [];
        exec("git rev-parse HEAD", $output);
        return $output[0];
    }

    private function setupCodeCoverage()
    {
        xdebug_set_filter(
            XDEBUG_FILTER_CODE_COVERAGE,
            XDEBUG_PATH_WHITELIST,
            $this->coverageWhitelist
        );
    }


    /**
     * Determines if file is part of the repository
     *
     * @param string $fullFilename
     * @return bool
     */
    private function isFileInRepository(string $fullFilename)
    {
        $prefixlen = strlen($this->repositoryPath) + 1;
        return substr($fullFilename, 0, $prefixlen) === $this->repositoryPath . DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the filename relative to the repo root
     *
     * Precondition: file must be part of the repository
     *
     * @param string $fullFilename
     * @return bool|string
     */
    private function getRepositoryRelativeFilename(string $fullFilename)
    {
        $prefixlen = strlen($this->repositoryPath) + 1;
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($fullFilename, $prefixlen));
    }

    public function startTest(Test $test): void
    {
        if ($test instanceof TestCase) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED);
        }
    }

    public function endTest(Test $test, $time): void
    {
        if ($test instanceof TestCase) {
            $codeCoverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            $fullTestName = get_class($test) . "::" . $test->getName();
            $this->processCoverage($codeCoverage, $fullTestName);
        }
    }

    private function processCoverage(array $codeCoverage, string $test)
    {
        $this->pdo->beginTransaction();
        $testId = $this->saveOrRetrieveTestId($test);

        $extendedCoverage = [];
        $coveredFunctions = [];
        foreach ($codeCoverage as $file => $lines) {
            $tokenStream = new TokenStream($file);
            if (!$this->isFileInRepository($file)) {
                continue;
            }
//            echo "$file\n";
            $hasAnyCoverage = false;
            $sourceFileId = null;
            $lastNonCoveredExecutableLine = 0;
            $lastStatus = null;
            $lastCoveredLine = null;
            foreach ($lines as $line => $status) {
                if ($status > 0) { // executed line
                    $lastCoveredLine = $line;
                    if (!$sourceFileId) {
                        //$tokenStream = new TokenStream($file);
                        $relativeFilename = $this->getRepositoryRelativeFilename($file);
                        $sourceFileId = $this->saveOrRetrieveSourceFileId($relativeFilename);
                    }
                    //$this->saveDependence($testId, $sourceFileId, $this->currentStateId, $line, $line);
                    $hasAnyCoverage = true;
//                    $codeElement = $this->wizard->lookup($file, $line);
//                    echo "$line: $codeElement\n";
//                    $matches = [];
//                    if (preg_match('/^([\w\\]+::)?([\w]+)$/', $codeElement, $matches)) {
//                        // This is a function or method
//                        $class = $matches[1];
//                        $functionName = $matches[2];
//                        if ($class) {
//                            $reflectionClass = new \ReflectionClass($class);
//                            $method = $reflectionClass->getMethod($functionName);
//                            $startLine = $method->getStartLine();
//                            $endLine = $method->getEndLine();
//                        } else {
//                            $function = new \ReflectionFunction($functionName);
//                            $startLine = $function->getStartLine();
//                            $endLine = $function->getEndLine();
//                        }
//                        $this->saveFunction($class, $functionName, $startLine, $endLine);
//                    }
                }
                if ($status < 0) { // Not executed
                    if ($lastStatus > 0) { // Previous executable line was executed
                        // Find current function
//                        $functionName = $tokenStream->getFunctionForLine($line);
//                        if ($functionName) {
//                            $tokenStream->getFunctions()[$functionName]
//                        }

                        $this->saveDependence(
                            $testId,
                            $sourceFileId,
                            $this->currentStateId,
                            $lastNonCoveredExecutableLine + 1,
                            $lastCoveredLine
                        );
                        $lastCoveredLine = null;
                    }
                    $lastNonCoveredExecutableLine = $line;
                }
                $lastStatus = $status;
            }
        }
        $this->pdo->commit();
    }

    private function processFunction(array $lines, int $testId, int $startLine, int $endLine)
    {
        $lineFrom = $startLine;
        for ($currentLine = $startLine; $currentLine <= $endLine; $currentLine++) {

            if (isset($lines[$currentLine])) {
                $status = $lines[$currentLine];
                if ($status === self::LINE_NOT_COVERED && $currentLine > $lineFrom) {
                    $this->saveDependence($testId, $sourceFileId, $this->currentStateId, $lineFrom, $lineTo);
                }
            } else {
                $lineTo = $currentLine;
            }
        }
    }

    private function saveFunction(string $className = null, string $functionName, int $startLine, int $endLine)
    {
        if (isset($this->functionIds[$className][$functionName])) {
        } else {
            if ($className) {
                $stmt = $this->pdo->prepare(
                    "SELECT f.id "
                    . "FROM function f "
                    . "  JOIN classlike c ON f.classlike_id = c.id "
                    . "WHERE c.name = ? AND f.name = ?");
                $stmt->execute([$className, $functionName]);
                $id = $stmt->fetchColumn();

                if (!$id) {
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO "
                    );
                }
            }
        }
    }

    private function saveDependence(int $testId, int $sourceFileId, int $stateId, int $lineFrom, int $lineTo)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO depends (test_id, source_file_id, state_id, line_from, line_to) '
            . 'VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$testId, $sourceFileId, $stateId, $lineFrom, $lineTo]);
    }

    private function saveOrRetrieveSourceFileId(string $path)
    {
        return $this->saveOrRetrieveId($path, 'source_file', 'path');
    }

    private function saveOrRetrieveStateId(string $path)
    {
        return $this->saveOrRetrieveId($path, 'state', 'commit');
    }

    private function saveOrRetrieveTestId(string $name)
    {
        return $this->saveOrRetrieveId($name, 'test', 'name');
    }

    private function saveOrRetrieveId(string $name, string $table, string $field) : int
    {
        $id = array_search($name, $this->maps[$table]);
        if ($id === false) {
            $stmt = $this->pdo->prepare("SELECT id FROM $table WHERE $field = ?");
            $stmt->execute([$name]);
            if ($stmt->rowCount()) {
                $id = $stmt->fetchColumn();
            }
        }
        if (!$id) {
            $stmt = $this->pdo->prepare("INSERT INTO $table ($field) VALUES (?)");
            $stmt->execute([$name]);
            $id = $this->pdo->lastInsertId();
            $this->maps[$table][$id] = $name;
        }
        return $id;
    }


}