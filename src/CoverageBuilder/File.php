<?php
namespace AronSzigetvari\TestSelector\CoverageBuilder;

use AronSzigetvari\TestSelector\CoverageBuilder;
use AronSzigetvari\TestSelector\Model\DependentRange;

class File extends CoverageBuilder
{
    function __construct()
    {
    }

    function buildCoverageForFile(string $fullSourcePath): void
    {
        $tests = $this->getTestsForFile($fullSourcePath);


        $relativePath = $this->getRepositoryRelativePath($fullSourcePath);
        $sourceFile = $this->coveragePersister->findSourceFileByPath($relativePath);

        $ranges = [];
        foreach ($tests as $testName) {
            $test = $this->coveragePersister->findTestByName($testName);
            $range = (new DependentRange())
                ->setType('File')
                ->setTest($test)
                ->setSourceFile($sourceFile)
                ->setState($this->state);
            $ranges[] = $range;
        }

        $this->coveragePersister->saveDependentRanges($ranges);
    }

    private function getTestsForFile(string $fullSourceFilePath): array
    {
        $fileCoverage = $this->coverageReader->getCoverageForFile($fullSourceFilePath);

        $tests = [];

        foreach ($fileCoverage as $lineNumber => $coverageData) {
            if ($coverageData) {
                $tests = array_merge($tests, $coverageData);
            }
        }

        return array_unique($tests);
    }

}