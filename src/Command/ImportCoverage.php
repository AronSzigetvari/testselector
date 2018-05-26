<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 26.
 * Time: 19:20
 */

namespace AronSzigetvari\TestSelector\Command;

use AronSzigetvari\TestSelector\CoverageReader;
use AronSzigetvari\TestSelector\Model\State;
use PDO;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\Diff\Parser;
use GitWrapper\GitWrapper;

use AronSzigetvari\TestSelector\CoveragePersister;
use AronSzigetvari\TestSelector\Command;
use AronSzigetvari\TestSelector\CoverageBuilder;
use AronSzigetvari\TestSelector\CoverageReader\PhpUnitCoverage as PhpUnitCoverageReader;

class ImportCoverage extends Command
{

    public function __construct()
    {
        $this->shortOptions .= 's:R:P';
        $this->longOptions[] = 'strategy:';
        $this->longOptions[] = 'repository:';
        $this->longOptions[] = 'no-progress';

        $this->optionMap['s'] = 'strategy';
        $this->optionMap['R'] = 'repository';
        $this->optionMap['P'] = 'no-progress';
    }

    protected function processCommandLineOptions($options)
    {
        if (isset($options['coverage'])) {
            $this->config->coverageFile = $options['coverage'];
        }
        if (isset($options['strategy'])) {
            $strategyOption = $options['strategy'];
            $this->config->strategy = (array)$strategyOption;
        }
        if (!isset($options['no-progress'])) {
            $this->config->progress = true;
        }
        if (isset($options['repository'])) {
            $this->config->repository = realpath($options['repository']);
        } elseif (!isset($this->config->repository)) {
            $this->config->repository = getcwd();
        }
    }

    protected function run(array $argv)
    {
        parent::run($argv);

        if (count($this->extraOptions) === 1) {
            $codeCoverage = $this->getCoverage($this->extraOptions[0]);
        } else {
            $this->error("Exactly 1 code coverage file must be specified.");
        }

        $codeCoverageReader = new PhpUnitCoverageReader($codeCoverage);
        $persister = $this->getPersister();
        $commitId = $this->getCurrentCommitId();

        $this->importCoverage(
            (array)$this->config->strategy,
            $codeCoverageReader,
            $persister,
            $commitId
        );
    }

    protected function importCoverage(
        array $strategies,
        CoverageReader $coverageReader,
        CoveragePersister $persister,
        string $commitId
    ) {
        $state = $persister->findStateByCommit($commitId);
        $repositoryPath = $this->config->repository;
        foreach ($strategies as $strategy) {
            $builder = null;
            switch ($strategy) {
                case 'line':
                    $builder = new CoverageBuilder\LineBased();
                    break;
                case 'function':
                    $builder = new CoverageBuilder\FunctionBased();
                    break;
                case 'class':
                    $builder = new CoverageBuilder\ClassBased();
                    break;
                case 'file':
                    $builder = new CoverageBuilder\FileBased();
                    break;
                default:
                    echo "Invalid strategy specified: " . $strategy;
            }
            if ($builder) {
                    echo "Starting importing $strategy based coverage\n";
                    $persister->resetState($state, $strategy);

                    $builder
                        ->setCoverageReader($coverageReader)
                        ->setState($state)
                        ->setCodeCoverageBase($repositoryPath)
                        ->setCodeCoverageDS(DIRECTORY_SEPARATOR)
                        ->setCoveragePersister($persister)
                        ->setShowProgressDisplay($this->config->progress);

                    $builder->buildCoverage();
                    echo "Finished.\n";
            }
        }
    }

    private function getCoverage(string $coverageFilePath): CodeCoverage
    {
        if (!is_file($coverageFilePath)) {
            $this->error("File " . $coverageFilePath . "doesn't exist.");
        }
        $coverage = include($coverageFilePath);
        if ($coverage instanceof CodeCoverage) {
            return $coverage;
        } else {
            $this->error("File " . $coverageFilePath . "is not a valid Code Coverage file.");
        }
    }

    private function getPersister(): CoveragePersister
    {
        if (!isset($this->config->connection, $this->config->connection->dsn)) {
            $this->error("connection DSN is not specified in config file.");
        }
        $connectionParams = $this->config->connection;
        $pdo = new PDO(
            $connectionParams->dsn,
            $connectionParams->username ?? 'root',
            $connectionParams->passwd ?? ''
        );

        $persister = new CoveragePersister\PDO($pdo);
        return $persister;
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