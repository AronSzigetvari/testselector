<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 04. 28.
 * Time: 19:38
 */

namespace AronSzigetvari\TestSelector;
use PHPUnit\Framework\Test;
use PHPUnit\TextUI\TestRunner as PhpUnitTestRunner;
use PHPUnit\Framework\Exception;

class TestRunner extends PhpUnitTestRunner
{
    public function doRun(Test $suite, array $arguments = [], $exit = true)
    {

        if (isset($arguments['testselector'])) {
            $filter = $this->handleSelectorFilter($arguments['testselector']);
            $this->write(
                "\nTestSelector filter generated: " . $filter
            );
            $arguments['filter'] = $filter;
        }
        return parent::doRun($suite, $arguments, $exit);
    }

    protected function handleSelectorFilter(array $testselectorArguments)
    {
        $repositoryPath = realpath($testselectorArguments['repository'] ?? getcwd());
        $refstate = $testselectorArguments['refstate'];
        $differ = new Differ($repositoryPath);
        $diff = $differ->getDiff($refstate);
        $codeCoverageReader = $this->getCodeCoverageReader($testselectorArguments['refcoverage'], $repositoryPath);
        $selector = new TestSelector($diff, $codeCoverageReader, $repositoryPath);
        $coveredTests = $selector->selectTestsByCoveredLines();
        $changedTests = $selector->selectModifiedOrNewTests();

        $testList = array_unique(array_merge($coveredTests, $changedTests));

        if (empty($testList)) {
            throw new Exception('Test selector found no tests to run.');
        }
        $filterArgument = $selector->createHierarchicFilterPattern($testList);
        return $filterArgument;
    }

    /**
     * @param string $codeCoverageArgument
     * @param string $repositoryPath
     * @return CoverageReader
     */
    protected function getCodeCoverageReader(string $codeCoverageArgument, string $repositoryPath) : CoverageReader
    {
        $coverage = include($codeCoverageArgument);
        $coverageReader = new CoverageReader\PhpUnitCoverage($coverage, $repositoryPath, '\\');
        return $coverageReader;
    }

}