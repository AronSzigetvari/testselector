<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 22:19
 */

namespace AronSzigetvari\TestSelector\CoverageBuilder;

use AronSzigetvari\TestSelector\CoverageBuilder;

abstract class RangeBasedAbstract extends CoverageBuilder
{
    protected function getTestsForRange(string $fullSourceFilePath, int $startLine, int $endLine): array
    {
        $fileCoverage = $this->coverageReader->getCoverageForFile($fullSourceFilePath);

        $tests = [];

        foreach ($fileCoverage as $lineNumber => $coverageData) {
            if ($lineNumber < $startLine) {
                continue;
            }
            if ($lineNumber > $endLine) {
                break;
            }
            if ($coverageData) {
                $tests = array_merge($tests, $coverageData);
            }
        }

        return array_unique($tests);
    }
}