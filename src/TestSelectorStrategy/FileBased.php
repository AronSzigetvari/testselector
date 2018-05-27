<?php
namespace AronSzigetvari\TestSelector\TestSelectorStrategy;

use SebastianBergmann\Diff\Diff;
use AronSzigetvari\TestSelector\CoverageQuery\PDO as CoverageQuery;


class FileBased
{
    /** @var array */
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

    public function selectTestsByCoveredFiles() : array
    {
        $selectedTests = [];

        foreach ($this->diff as $diff) {
            if ($this->isTest($diff)) {
                continue;
            };
            $coveredTests = $this->coverageQuery->getTestsForSourceFile($diff);
            if (!empty($coveredTests)) {
                $selectedTests = array_merge($selectedTests, $coveredTests);
            } else {
//                echo "No coverage for " . $diff . "\n";
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