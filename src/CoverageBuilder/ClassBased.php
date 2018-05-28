<?php
namespace AronSzigetvari\TestSelector\CoverageBuilder;

use AronSzigetvari\TestSelector\Model\DependentRange;
use PHP_Token_Stream;

class ClassBased extends RangeBasedAbstract
{
    function __construct()
    {
    }

    function buildCoverageForFile(string $fullSourcePath): void
    {
        $tokenStream = new PHP_Token_Stream(file_get_contents($fullSourcePath));

        $relativePath = $this->getRepositoryRelativePath($fullSourcePath);
        $sourceFile = $this->coveragePersister->findSourceFileByPath($relativePath);

        $classesAndTraits = array_merge(
            $tokenStream->getClasses(),
            $tokenStream->getTraits()
        );

        $ranges = [];
        foreach ($classesAndTraits as $classOrTrait) {
            $startLine = $classOrTrait['startLine'];
            $endLine = $classOrTrait['endLine'];
            $tests = $this->getTestsForRange($fullSourcePath, $startLine, $endLine);

            foreach ($tests as $testName) {
                $test = $this->coveragePersister->findTestByName($testName);
                $range = (new DependentRange())
                    ->setStrategy('class')
                    ->setTest($test)
                    ->setSourceFile($sourceFile)
                    ->setState($this->state)
                    ->setLineFrom($startLine)
                    ->setLineTo($endLine);
                $ranges[] = $range;
            }
        }

        $this->coveragePersister->saveDependentRanges($ranges);
    }

}