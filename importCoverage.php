<?php

use AronSzigetvari\TestSelector\TestSelector;
use SebastianBergmann\Diff\Parser;
use AronSzigetvari\TestSelector\CoverageReader\PhpUnitCoverage as PhpUnitCoverageReader;

include __DIR__ . '/vendor/autoload.php';

AronSzigetvari\TestSelector\Command\ImportCoverage::main();
die;

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

$coverageReader = new PhpUnitCoverageReader($coverage);

$pdo = new PDO('mysql:dbname=ts_doctrine2', 'root', '');
$persister = new \AronSzigetvari\TestSelector\CoveragePersister\PDO($pdo);

$state = $persister->findStateByCommit($options['refstate']);

$persister->resetState($state);

echo "Starting File based\n";
$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\FileBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

echo "Starting Class based\n";
$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\ClassBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

echo "Starting Function based\n";
$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\FunctionBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

echo "Starting Line based\n";
$builder = new \AronSzigetvari\TestSelector\CoverageBuilder\LineBased();
$builder
    ->setCoverageReader($coverageReader)
    ->setState($state)
    ->setCodeCoverageBase($repositoryPath)
    ->setCodeCoverageDS('\\')
    ->setCoveragePersister($persister);

$builder->buildCoverage();

echo "Ready\n";