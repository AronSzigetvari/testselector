<?php
namespace AronSzigetvari\TestSelector;

use SebastianBergmann\Diff\Diff;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;



class TestSelector
{
    /** @var array|Diff[] */
    private $diff;

    /** @var CoverageReader */
    private $codeCoverageReader;

    /** @var string */
    private $repositoryBase;

    /** @var Wizard */
    private $wizard;

    /**
     * TestSelector constructor.
     * @param array $diff
     * @param CoverageReader $codeCoverageReader
     * @param string $codeCoverageBase
     * @param string $codeCoverageDS
     * @param string $repositoryBase
     */
    public function __construct(
        array $diff,
        CoverageReader $codeCoverageReader,
        string $repositoryBase
    ) {
        $this->diff = $diff;
        $this->codeCoverageReader = $codeCoverageReader;
        $this->repositoryBase = $repositoryBase;

        $this->wizard = new Wizard();
    }



    public function selectTestsByCoveredLines() : array
    {
        $selectedTests = [];

        foreach ($this->diff as $diff) {
            //echo "Diff start " . $diff->getFrom() . "\n";
            $newFile = $diff->getTo();
            if ($this->isTest($newFile)) {
                continue; // Omit tests
            }
            $originalFile = $diff->getFrom();
            if ($originalFile === '/dev/null') {
                continue; // file created
            }
            if ($this->codeCoverageReader->hasCoverageForFile($originalFile)) {
                foreach ($diff->getChunks() as $chunk) {
                    echo 'S:'.$chunk->getStart()."\n";
                    echo 'SR:'.$chunk->getStartRange()."\n";
                    echo 'E:'.$chunk->getEnd()."\n";
                    echo 'ER:'.$chunk->getEndRange()."\n";
                    $startLine = $chunk->getStart();
                    $endLine = $startLine + $chunk->getStartRange() - 1;
                    $coveredTests = $this->codeCoverageReader->getTestsForLines($originalFile, $startLine, $endLine);
                    echo count($coveredTests) . "tests\n";

                    $selectedTests = array_merge($selectedTests, $coveredTests);
                }
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