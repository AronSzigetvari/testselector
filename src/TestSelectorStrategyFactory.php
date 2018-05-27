<?php
namespace AronSzigetvari\TestSelector;

use PDO;

class TestSelectorStrategyFactory
{
    private $config;

    private $lineBasedDiff;

    private $fileBasedDiff;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function create()
    {
        $coverageQuery = $this->getCoverageQuery();
        $diff = $this->getDiff();
        $strategy = $this->config->selectionStrategy;

        switch ($strategy) {
            case 'line':
            case 'function':
            case 'class':
                $testSelector = new TestSelectorStrategy\LineRangeBased($diff, $coverageQuery);
                break;
            case 'file':
                $testSelector = new TestSelectorStrategy\FileBased($diff, $coverageQuery);
                break;
            default:
                throw new \OutOfRangeException('Invalid strategy ' . $strategy);
        }
        return $testSelector;
    }

    protected function getCoverageQuery(): CoverageQuery
    {
        $pdo = $this->getPdo();
        $coverageQuery = new CoverageQuery\PDO($pdo, $this->config->refstate);
        return $coverageQuery;
    }

    protected function getPdo(): PDO
    {
        if (!isset($this->config->connection, $this->config->connection->dsn)) {
            throw new \Exception("connection DSN is not specified in config.");
        }
        $connectionParams = $this->config->connection;
        $pdo = new PDO(
            $connectionParams->dsn,
            $connectionParams->username ?? 'root',
            $connectionParams->passwd ?? ''
        );

        return $pdo;
    }

    protected function getFileBasedDiff(): array
    {
        if (!$this->fileBasedDiff) {
            $differ = new Differ($this->config->repository);
            $this->fileBasedDiff = $differ->getFileBasedDiff($this->config->refstate, $this->config->newstate ?? null);
        }
        return $this->fileBasedDiff;
    }

    public function getLineBasedDiff(): array
    {
        if (!$this->lineBasedDiff) {
            $differ = new Differ($this->config->repository);
            $this->lineBasedDiff = $differ->getLineBasedDiff($this->config->refstate, $this->config->newstate ?? null);
        }
        return $this->lineBasedDiff;
    }

    public function getDiff(): array
    {
        if ($this->config->selectionStrategy === 'file') {
            $diff = $this->getFileBasedDiff();
        } else {
            $diff = $this->getLineBasedDiff();
        }
        return $diff;
    }
}