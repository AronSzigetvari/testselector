<?php
namespace AronSzigetvari\TestSelector\CoverageBuilder;

use AronSzigetvari\TestSelector\Model\DependentRange;
use AronSzigetvari\TestSelector\Model\Test;
use PHP_Token_Stream;

class LineBased extends RangeBasedAbstract
{
    function __construct()
    {
    }

    function buildCoverageForFile(string $fullSourcePath): void
    {
        $tokenStream = new PHP_Token_Stream(file_get_contents($fullSourcePath));

        $relativePath = $this->getRepositoryRelativePath($fullSourcePath);
        $sourceFile = $this->coveragePersister->findSourceFileByPath($relativePath);

        $fileCoverage = $this->coverageReader->getCoverageForFile($fullSourcePath);

        $ranges = [];

        $functionData = null;
        $lastFunctionName = null;
        $lastCoverableLine = null;
        /** @var DependentRange[] $currentRanges */
        $currentRanges = [];
        $endOfLastFunction = null;


        foreach ($fileCoverage as $lineNumber => $coverageInfo) {
            $functionName = $tokenStream->getFunctionForLine($lineNumber);

            if ($functionName !== $lastFunctionName) {
                // new function, closing existing ranges (ranges only within functions)
                if ($functionData) {
                    // Previous coverable line was in a function
                    $endLine = $functionData['endLine'];
                } else {
                    // Previous coverable line was outside a function
                    $endLine = $lineNumber - 1;
                }
                foreach ($currentRanges as $range) {
                    $range->setLineTo($endLine);
                }
                $currentRanges = [];

                $functionData = null;
                $lastFunctionName = $functionName;
                $lastCoverableLine = null;

                if ($functionName) {
                    $functionData = $this->getFunctionDataByName($functionName, $tokenStream);
                    $endOfLastFunction = $functionData['endLine'];
                }
            }

            if (is_array($coverageInfo)) {
                // This line is coverable, $coverageInfo contains
                // names of tests running this line
                $currentTestNames = array_keys($currentRanges);

                // Process ranges for which this is the first covered line
                $coverageStartedForTests = array_diff($coverageInfo, $currentTestNames);
                if (!empty($coverageStartedForTests)) {
                    if ($lastCoverableLine === null) {
                        if ($functionName) {
                            // This line is in a function
                            $startLine = $functionData['startLine'];
                        } else {
                            // This line is not in function
                            $startLine = $endOfLastFunction ?: 1;
                        }
                    } else {
                        $startLine = $lastCoverableLine + 1;
                    }
                    foreach ($coverageStartedForTests as $testName) {
                        $testObject = $this->coveragePersister->findTestByName($testName);
                        $range = (new DependentRange())
                            ->setTest($testObject)
                            ->setStrategy('line')
                            ->setState($this->state)
                            ->setSourceFile($sourceFile)
                            ->setLineFrom($startLine);
                        $currentRanges[$testName] = $range;
                        $ranges[] = $range;
                    }
                }

                // Process ranges for which this is the first line that is not covered
                $coverageEndedForTests = array_diff($currentTestNames, $coverageInfo);
                foreach ($coverageEndedForTests as $testName) {
                    $currentRanges[$testName]->setLineTo($lineNumber - 1);
                    unset($currentRanges[$testName]);
                }

                $lastCoverableLine = $lineNumber;
            }
        }
        // Close any existing ranges still open at the end of the file
        if ($functionData) {
            // Last ranges were within a function => to the end of the function
            $endLine = $functionData['endLine'];
        } else {
            // Last ranges were outside a function => to the end of the file
            $linesOfCode = $tokenStream->getLinesOfCode();
            $endLine = $linesOfCode['loc'];
        }
        foreach ($currentRanges as $range) {
            $range->setLineTo($endLine);
        }

        $this->coveragePersister->saveDependentRanges($ranges);
    }

    /**
     * Returns Token stream function data for a function or method
     *
     * @param string $functionName
     * @param PHP_Token_Stream $tokenStream
     * @return array
     */
    protected function getFunctionDataByName(string $functionName, PHP_Token_Stream $tokenStream): array
    {
        if (strpos($functionName, '::') !== false) {
            // class or trait
            list($className, $methodName) = explode('::', $functionName);
            $classes = $tokenStream->getClasses();
            $traits = $tokenStream->getTraits();
            if (isset($classes[$className])) {
                $classOrTrait = $classes[$className];
            } elseif (isset($traits[$className])) {
                $classOrTrait = $traits[$className];
            }
            $return = $classOrTrait['methods'][$methodName];
        } else {
            // function
            $return = $tokenStream->getFunctions()[$functionName];
        }
        return $return;
    }
}