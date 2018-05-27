<?php
namespace AronSzigetvari\TestSelector\Command;

use AronSzigetvari\TestSelector\CoverageReader;
use AronSzigetvari\TestSelector\FilterGenerator;
use AronSzigetvari\TestSelector\Model\State;
use AronSzigetvari\TestSelector\ModifiedTestSelector;
use AronSzigetvari\TestSelector\TestSelectorStrategy\FileBased;
use AronSzigetvari\TestSelector\TestSelectorStrategy\LineRangeBased;
use AronSzigetvari\TestSelector\TestSelectorStrategyFactory;
use PDO;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\Diff\Parser;
use GitWrapper\GitWrapper;

use AronSzigetvari\TestSelector\CoveragePersister;
use AronSzigetvari\TestSelector\Command;
use AronSzigetvari\TestSelector\CoverageBuilder;
use AronSzigetvari\TestSelector\CoverageReader\PhpUnitCoverage as PhpUnitCoverageReader;

class TestSelector extends Command
{

    public function __construct()
    {
        $this->shortOptions .= 's:R:r:n:l::f::m::d:u:p:';

        $this->longOptions[] = 'strategy:';
        $this->longOptions[] = 'repository:';
        $this->longOptions[] = 'refstate:';
        $this->longOptions[] = 'newstate:';
        $this->longOptions[] = 'list::';
        $this->longOptions[] = 'filter::';
        $this->longOptions[] = 'modified-tests::';
        $this->longOptions[] = 'raw::';
        $this->longOptions[] = 'dsn:';
        $this->longOptions[] = 'username:';
        $this->longOptions[] = 'passwd:';

        $this->optionMap['s'] = 'strategy';
        $this->optionMap['R'] = 'repository';
        $this->optionMap['r'] = 'refstate';
        $this->optionMap['n'] = 'newstate';
        $this->optionMap['l'] = 'list';
        $this->optionMap['f'] = 'filter';
        $this->optionMap['m'] = 'modified-tests';
        $this->optionMap['d'] = 'dsn';
        $this->optionMap['u'] = 'user';
        $this->optionMap['p'] = 'passwd';
    }

    protected function processCommandLineOptions($options)
    {
        if (isset($options['strategy'])) {
            $strategyOption = $options['strategy'];
            if (is_array($strategyOption)) {
                $this->error('Only one strategy can be specified for selection.');
            }
            $this->config->selectionStrategy = $strategyOption;
        }

        if (isset($options['repository'])) {
            $this->config->repository = realpath($options['repository']);
        } elseif (!isset($this->config->repository)) {
            $this->config->repository = getcwd();
        }

        // If both list and filter params are missing, default to list
        if (
            !isset($options['list'])
            && !isset($options['filter'])
            && !isset($this->config->list)
            && !isset($this->config->filter)
        ) {
            $options['list'] = true;
        }

        // Boolean params, defaulting to false
        $boolParamsMap = [
            'raw'            => 'raw',
            'list'           => 'list',
            'filter'         => 'filter',
            'modified-tests' => 'modifiedTests'
        ];
        foreach ($boolParamsMap as $paramName => $configName) {
            if (isset($options[$paramName])) {
                if ($options[$paramName] === false) {
                    // --raw without parameter
                    $this->config->$configName = true;
                } else {
                    // --raw=0 or 1
                    $this->config->$configName = (bool) $options[$paramName];
                }
            } else {
                $this->config->$configName = false;
            }
        }

        // Connection params
        foreach (['dsn', 'username', 'passwd'] as $paramName) {
            if (isset($options[$paramName])) {
                $this->config->connection->$paramName = $options[$paramName];
            }
        }

        if (isset($options['refstate'])) {
            $this->config->refstate = $options['refstate'];
        } else {
            $this->config->refstate = $this->getCurrentCommitId();
        }

        if (isset($options['newstate'])) {
            $this->config->newstate = $options['newstate'];
        } else {
            $this->config->newstate = 'HEAD';
        }

//        var_dump($this->config);
    }

    protected function run(array $argv)
    {
        parent::run($argv);

        $factory = new TestSelectorStrategyFactory($this->config);
        $tests = $this->selectTests($factory);

        if ($this->config->modifiedTests) {
            $lineBasedDiff = $factory->getLineBasedDiff();
            $modifiedTestsSelector = new ModifiedTestSelector($lineBasedDiff, $this->config->repository);
            $modifiedTests = $modifiedTestsSelector->selectModifiedOrNewTests();
        } else {
            $modifiedTests = null;
        }

        $this->report($tests, $modifiedTests);

    }

    protected function selectTests(TestSelectorStrategyFactory $factory) {
        try {
            $testSelector = $factory->create();

            if ($testSelector instanceof LineRangeBased) {
                $tests = $testSelector->selectTestsByCoveredLines($this->config->selectionStrategy ?? 'line');
            } elseif ($testSelector instanceof FileBased) {
                $tests = $testSelector->selectTestsByCoveredFiles();
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        return $tests;
    }

    protected function report(array $tests, array $modifiedTests = null)
    {
        $raw = $this->config->raw;
        if (!$raw) {
            echo "Regression Test Selection\n\n";
        }
        if ($this->config->list) {
            if (!$raw) {
                echo "\nModification Traversing tests:\n";
            }
            $this->listSelectedTests($tests, $raw);

            if ($modifiedTests) {
                if (!$raw) {
                    echo "\nModified test classes and methods:\n";
                }
                $this->listSelectedTests($modifiedTests, $raw);
            }
        }

        if ($this->config->filter) {
            $allTests = $modifiedTests ? array_merge($tests, $modifiedTests) : $tests;
            if (count($allTests) > 0) {
                $filterGenerator = new FilterGenerator();
                $pattern = $filterGenerator->createHierarchicPattern($allTests);
                if (!$raw) {
                    echo "Pattern for phpunit --filter:\n";
                }
                echo $pattern . "\n";
            }
        }
    }

    private function listSelectedTests(array $tests, bool $raw)
    {
        $count = count($tests);
        if ($count > 0) {
            foreach ($tests as $test) {
                echo $test . "\n";
            }
            if (!$raw) {
                echo "\n" . $count . " " . ($count === 1 ? 'test was' : 'tests were') . " selected.\n";
            }
        } else {
            if (!$raw) {
                if (!$raw) {
                    echo "No tests were selected.\n";
                }
            }
        }
    }



    private function getCurrentCommitId(): string
    {
        $git = new GitWrapper();
        echo $this->config->repository;
        $output = $git->git('rev-parse HEAD', $this->config->repository);

        if (preg_match('/[\da-f]{40}/', $output, $matches)) {
            return $matches[0];
        } else {
            $this->error("Git error: " . $output);
        }
    }
}