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
use GitWrapper\GitWrapper;

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

        $refstate = $testselectorArguments['refstate'] ?? 'HEAD';
        if (!preg_match('/^[0-9a-f]{40}$/', $refstate)) {
            $refstate = $this->gitRevParse($refstate, $repositoryPath);
        }

        $differ = new Differ($repositoryPath);
        $diff = $differ->getDiff($refstate);

        //$codeCoverageReader = $this->getCodeCoverageReader($testselectorArguments['refcoverage'], $repositoryPath);
        $factory = new CoverageReader\Factory();
        $codeCoverageReader = $factory->create($testselectorArguments, $refstate, $repositoryPath);
        $selector = new TestSelector($diff, $codeCoverageReader, $repositoryPath);
        $coveredTests = $selector->selectTestsByCoveredLines();
        $changedTests = $selector->selectModifiedOrNewTests();

        $this->write("\n" . "Tests to rerun: " . count($coveredTests));
        $this->write("\n" . "New/changed tests: " . count($changedTests));

        $testList = array_unique(array_merge($coveredTests, $changedTests));

        if (empty($testList)) {
            throw new Exception('Test selector found no tests to run.');
        }

        $this->write("\n" . "Tests to run: " . count($testList));

        $patternGenerator = new \AronSzigetvari\TestSelector\FilterGenerator();
        $filterArgument = $patternGenerator->createHierarchicPattern($testList);
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

    private function gitRevParse(string $revision, string $repository = null) : string
    {
        $git = new GitWrapper();
        $output = $git->git('rev-parse ' . $revision, $repository);
        if (preg_match('/^([0-9a-f]{40})\s*$/', $output, $matches)) {
            return $matches[1];
        }
        throw new \UnexpectedValueException('Revision not found in repository');
    }

}