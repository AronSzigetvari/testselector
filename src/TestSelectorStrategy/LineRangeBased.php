<?php
namespace AronSzigetvari\TestSelector\TestSelectorStrategy;

use SebastianBergmann\Diff\Diff;
use AronSzigetvari\TestSelector\CoverageQuery\PDO as CoverageQuery;


class LineRangeBased
{
    /** @var array|Diff[] */
    private $diff;

    /** @var CoverageQuery */
    private $coverageQuery;

    /**
     * TestSelector constructor.
     * @param array $diff
     * @param CoverageQuery $coverageQuery
     */
    public function __construct(
        array $diff,
        CoverageQuery $coverageQuery
    ) {
        $this->diff = $diff;
        $this->coverageQuery = $coverageQuery;
    }

    public function selectTestsByCoveredLines(string $strategy) : array
    {
        $selectedTests = [];

        foreach ($this->diff as $diff) {
//            echo "Diff start " . $diff->getFrom() . "\n";
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
//                    echo 'S:'.$chunk->getStart()."\n";
//                    echo 'SR:'.$chunk->getStartRange()."\n";
//                    echo 'E:'.$chunk->getEnd()."\n";
//                    echo 'ER:'.$chunk->getEndRange()."\n";
                    $startLine = $chunk->getStart();
                    $endLine = $startLine + $chunk->getStartRange() - 1;
                    $coveredTests = $this->coverageQuery->getTestsForLines($originalFile, $strategy, $startLine, $endLine);
//                    echo count($coveredTests) . " tests\n";

                    $selectedTests = array_merge($selectedTests, $coveredTests);
                }
            } else {
//                echo "No coverage for " . $originalFile . "\n";
            }
        }
        return array_unique($selectedTests);
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