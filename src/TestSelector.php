<?php
namespace AronSzigetvari\TestSelector;

use SebastianBergmann\Diff\Diff;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;
use AronSzigetvari\TestSelector\CoverageQuery\PDO as CoverageQuery;



class TestSelector
{
    /** @var array|Diff[] */
    private $diff;

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



    public function selectTestsByCoveredLines(string $strategy) : array
    {
        $selectedTests = [];

        foreach ($this->diff as $diff) {
            echo "Diff start " . $diff->getFrom() . "\n";
            $newFile = $diff->getTo();
            if ($this->isTest($newFile)) {
                continue; // Omit tests
            }
            $originalFile = $diff->getFrom();
            if ($originalFile === '/dev/null') {
                continue; // file created
            }
            if ($this->coverageQuery->hasCoverageForFile($originalFile)) {
                foreach ($diff->getChunks() as $chunk) {
                    echo 'S:'.$chunk->getStart()."\n";
                    echo 'SR:'.$chunk->getStartRange()."\n";
                    echo 'E:'.$chunk->getEnd()."\n";
                    echo 'ER:'.$chunk->getEndRange()."\n";
                    $startLine = $chunk->getStart();
                    $endLine = $startLine + $chunk->getStartRange() - 1;
                    $coveredTests = $this->coverageQuery->getTestsForLines($originalFile, $strategy, $startLine, $endLine);
                    echo count($coveredTests) . "tests\n";

                    $selectedTests = array_merge($selectedTests, $coveredTests);
                }
            } else {
                echo "No coverage for " . $originalFile . "\n";
            }
//            $ccPath = $this->relative2CodeCoveragePath($originalFile);
//            if (isset($coverageData[$ccPath])) {
//                $coverageDataOfOriginal = $coverageData[$ccPath];
//                $coverageDataOfOriginal->
//                echo "Coverage found for " . $ccPath . "\n";
//            } else echo 'No coverage for ' . $ccPath . "\n";
//            //if ($this->codeCoverage->getData())
        }
        return array_unique($selectedTests);
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

            include_once($newFile);

            if (!$this->isTest($newFile)) {
                continue; // Omit non-tests
            }

            foreach ($diff->getChunks() as $chunk) {
                $startLine = $chunk->getEnd();
                $lines = $chunk->getEndRange();
                for ($i = 0; $i < $lines; $i++) {
                    $result = $this->wizard->lookup($newFile, $startLine + $i);
                    $colonPos = strpos($result, '::');
                    if ($colonPos !== false) {
                        if ($wholeFile) {
                            $selectedTests[] = substr($result, 0, $colonPos) . '.*';
                            break;
                        } else {
                            $selectedTests[] = $result . '.*';
                        }
                    }
                }
            }
        }
        return $selectedTests;
    }

    /**
     * Returns if the filename given is a test file
     *
     * Condition: Test files are those that end with Test.php
     *
     * @param string $file
     * @return bool
     */
    protected function isTest(string $file) : bool
    {
        return (substr($file, -8) === 'Test.php');
    }


}