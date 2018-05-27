<?php
namespace AronSzigetvari\TestSelector\PHPUnit;

use AronSzigetvari\TestSelector\ModifiedTestSelector;
use PHPUnit\Framework\Test;
use PHPUnit\TextUI\TestRunner as PhpUnitTestRunner;
use PHPUnit\Framework\Exception;
use GitWrapper\GitWrapper;
use AronSzigetvari\TestSelector\TestSelectorStrategyFactory;
use AronSzigetvari\TestSelector\TestSelectorStrategy;

class TestRunner extends PhpUnitTestRunner
{
    public function doRun(Test $suite, array $arguments = [], $exit = true)
    {

        if (isset($arguments['testselector'])) {
            $filter = $this->handleSelectorFilter($arguments['testselector']);
            $this->write(
                "\nTestSelector filter generated: " . $filter . "\n\n"
            );
            $arguments['filter'] = $filter;
        }

        return parent::doRun($suite, $arguments, $exit);
    }

    protected function processTestSelectorConfig(array $testSelectorArguments = [])
    {

        $configFile = null;
        $config = new \StdClass();
        if (isset($testSelectorArguments['config'])) {
            $configFile = $testSelectorArguments['config'];
            if (!is_file($configFile)) {
                $this->runFailed("\nTest Selector: Config file does not exist\n");
            }
        } elseif (is_file('testselector.json')) {
            $configFile = 'testselector.json';
        }
        if ($configFile) {
            $configContent = file_get_contents($configFile);
            $config = json_decode($configContent);
            if (!$config) {
                $this->runFailed("\nTest Selector: Invalid configuration\n");
            }
        }

        // Strategy
        if (isset($testSelectorArguments['strategy'])) {
            $config->selectionStrategy = $testSelectorArguments['strategy'];
        }
        if (!isset($config->selectionStrategy)) {
            $config->selectionStrategy = 'line';
        }

        // Repository
        if (isset($testSelectorArguments['repository'])) {
            $config->repository = realpath($testSelectorArguments['repository']);
        }
        if (!isset($config->repository)) {
            $config->repository = getcwd();
        }

        // Refstate
        $refstate = $testSelectorArguments['refstate'] ?? $config->refstate ?? 'HEAD';
        if (!preg_match('/^[0-9a-f]{40}$/', $refstate)) {
            $refstate = $this->gitRevParse($refstate, $config->repository);
        }
        $config->refstate = $refstate;

        return $config;
    }

    protected function selectTests(TestSelectorStrategyFactory $factory, $strategy) {
        try {
            $testSelector = $factory->create();

            if ($testSelector instanceof TestSelectorStrategy\LineRangeBased) {
                $tests = $testSelector->selectTestsByCoveredLines($strategy);
            } elseif ($testSelector instanceof TestSelectorStrategy\FileBased) {
                $tests = $testSelector->selectTestsByCoveredFiles();
            }
        } catch (\Exception $e) {
            $this->runFailed($e->getMessage());
        }

        return $tests;
    }

    protected function handleSelectorFilter($testSelectorArguments)
    {
        $config = $this->processTestSelectorConfig($testSelectorArguments);

        $factory = new TestSelectorStrategyFactory($config);
        $coveredTests = $this->selectTests($factory, $config->selectionStrategy);

        $lineBasedDiff = $factory->getLineBasedDiff();
        $modifiedTestsSelector = new ModifiedTestSelector($lineBasedDiff, $config->repository);
        $modifiedTests = $modifiedTestsSelector->selectModifiedOrNewTests();

        $this->write("\n" . "Tests to rerun: " . count($coveredTests));
        $this->write("\n" . "New/modified tests: " . count($modifiedTests));

        $testList = array_unique(array_merge($coveredTests, $modifiedTests));

        if (empty($testList)) {
            throw new Exception('Test selector found no tests to run.');
        }

        $this->write("\n" . "Tests to run: " . count($testList));

        $patternGenerator = new \AronSzigetvari\TestSelector\FilterGenerator();
        $filterArgument = $patternGenerator->createHierarchicPattern($testList);
        return $filterArgument;
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