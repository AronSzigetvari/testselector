<?php

use AronSzigetvari\TestSelector\TestSelector;
use SebastianBergmann\Diff\Parser;
use AronSzigetvari\TestSelector\CoverageReader\PhpUnitCoverage as PhpUnitCoverageReader;

include 'vendor/autoload.php';

$options = getopt(
    'r::R:c:',
    [
        'refstate::',
        'repository:',
        'coverage:'
    ]
);

$map = [
    'r' => 'refstate',
    'R' => 'repository',
    'c' => 'coverage'
];

foreach ($map as $old => $new) {
    if (isset($options[$old])) {
        $options[$new] = $options[$old];
        unset($options[$old]);
    }
}


$coverageFile = $options['coverage'];
$coverage = include($coverageFile);

$repositoryPath = realpath($options['repository']);

$coverageReader = new PhpUnitCoverageReader($coverage, $repositoryPath, '\\');

$pdo = new PDO('mysql:dbname=ts_doctrine2', 'root', '');
$persister = new \AronSzigetvari\TestSelector\CoveragePersister\PDO($pdo);

$state = $persister->findStateByCommit($options['refstate']);

$persister->resetState($state);

$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\FileBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\ClassBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\FunctionBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();