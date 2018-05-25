<?php
namespace AronSzigetvari\TestSelector\CoverageReader;

use PDO;
use AronSzigetvari\TestSelector\CoverageReader;


class Factory
{
    public function create(array $arguments, string $refstate, string $repositoryPath) : CoverageReader
    {
        if (isset($arguments['refcoveragedsn'])) {
            $connection = new PDO("mysql:dbname=" . $arguments['refcoveragedsn'], 'root');
            $coverageReader = new CoverageReader\Database($connection, $refstate);
        } elseif (isset($arguments['refcoveragefile'])) {
            $coverage = include($arguments['refcoveragefile']);
            $coverageReader = new CoverageReader\PhpUnitCoverage($coverage, $repositoryPath, '\\');
        }
        return $coverageReader;
    }
}