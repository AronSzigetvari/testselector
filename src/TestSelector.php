<?php
namespace AronSzigetvari\TestSelector;

use PHP_Token_Stream as TokenStream;
use SebastianBergmann\Diff\Diff;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;
use AronSzigetvari\TestSelector\CoverageQuery\PDO as CoverageQuery;
use AronSzigetvari\TestSelector\TestSelectorStrategy;



class TestSelector
{
    /** @var array|Diff[] */
    private $diff;

    /** @var Differ */
    private $differ;

    /** @var CoverageReader */
    private $coverageQuery;

    /** @var string */
    private $repositoryBase;

    /** @var Wizard */
    private $wizard;

    /**
     * TestSelector constructor.
     * @param array $diff
     * @param CoverageReader $coverageQuery
     * @param string $codeCoverageBase
     * @param string $codeCoverageDS
     * @param string $repositoryBase
     */
    public function __construct(
        array $diff,
        CoverageQuery $coverageQuery,
        string $repositoryBase
    ) {
        $this->diff = $diff;
        $this->coverageQuery = $coverageQuery;
        $this->repositoryBase = $repositoryBase;

        $this->wizard = new Wizard();
    }

    public function selectTestsByModificationAndCoverage(string $strategy, string $refstate, string $endstate): array
    {
        switch ($strategy) {
            case 'line':
            case 'function':
            case 'class':
                $diff = $this->differ->getLineBasedDiff($refstate, $endstate ?? null);
                $testSelector = new TestSelectorStrategy\LineRangeBased($diff, $this->coverageQuery);
                $tests = $testSelector->selectTestsByCoveredLines($strategy);
                break;
            case 'file':
                $diff = $this->differ->getFileBasedDiff($refstate, $endstate ?? null);
                $testSelector = new TestSelectorStrategy\FileBased($diff, $this->coverageQuery);
                $tests = $testSelector->selectTestsByCoveredFiles();
                break;
            default:
                throw new \OutOfRangeException('Invalid strategy ' . $strategy);
        }
        return $tests;
    }

    public function selectModifiedOrNewTests() : array
    {
        $selectedTests = [];

        foreach ($this->diff as $diff) {
            $wholeFile = ($diff->getFrom() === '/dev/null');
            if ($diff->getTo() === '/dev/null') {
                continue; // Deleted file
            }

            $newFile = realpath($this->repositoryBase . '/' . $diff->getTo());
            if (!$this->isTestFile($newFile)) {
                continue; // Omit non-tests
            }

            $tokenStream = new TokenStream(file_get_contents($newFile));

            $lastAddedClass = null;
            $lastClassName = null;
            $lastFunctionName = null;
            $selectedTests = [];
            $selectedClasses = [];
            $skipUntilLine = null;
            foreach ($diff->getChunks() as $chunk) {
                $startLine = $chunk->getEnd();
                $lines = $chunk->getEndRange();
                for ($i = 0; $i < $lines; $i++) {
                    $currentLine = $startLine + $i;
                    if ($skipUntilLine) {
                        if ($currentLine <= $skipUntilLine) {
                            continue;
                        } else {
                            $skipUntilLine = null;
                        }
                    }

                    $functionName = $tokenStream->getFunctionForLine($currentLine);

                    if ($functionName) {
                        if ($lastFunctionName === $functionName) {
                            // We are in the same method
                            continue;
                        }
                        if ($this->isTestMethod($functionName, $tokenStream)) {
                            // Add test method to the list of retestable methods
                            $selectedTests[] = $functionName;
                        } elseif (strpos($functionName, '::') !== false) {
                            // Not a test method but it may affect any test methods in the class
                            list($className, $methodName) = explode('::', $functionName);
                            $selectedClasses[] = $className;
                            $skipUntilLine = $tokenStream->getClasses()[$className]['endLine'];
                            $selectedTests = $this->removeByPrefix($selectedTests, $className . '::');
                        }
                        $lastFunctionName = $functionName;
                    }
                }
            }
        }
        return $selectedTests;
    }

    private function getClassForLine(int $line, TokenStream $tokenStream)
    {
        foreach ($tokenStream->getClasses() as $class) {
            if ($line >= $class['startLine'] && $line <= $class['endLine']) {
                return $class;
            }
        }
        return null;
    }

    private function isTestMethod(string $functionName, TokenStream $tokenStream)
    {
        if (strpos($functionName, '::') !== false) {
            list($className, $methodName) = explode('::', $functionName);
            if ($className && $methodName) {
                if (substr($className, -4) === 'Test') {
                    if (substr($methodName, 0, 4) === 'test') {
                        return true;
                    }
                    $classes = $tokenStream->getClasses();
                    $method = $classes[$className]['methods'][$methodName];
                    $docbook = $method['docbook'];
                    if (strpos($docbook, '@test')) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function removeByPrefix(array $array, string $prefix) {
        $prefixLength = strlen($prefix);
        $result = array_filter(
            $array,
            function ($element) use ($prefix, $prefixLength) {
                return substr($element, 0, $prefixLength) !== $prefix;
            }
        );
        return $result;
    }

    /**
     * Returns if the filename given is a test file
     *
     * Condition: Test files are those that end with Test.php
     *
     * @param string $file
     * @return bool
     */
    protected function isTestFile(string $file) : bool
    {
        return (substr($file, -8) === 'Test.php');
    }


}