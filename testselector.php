<?php

use AronSzigetvari\TestSelector\TestSelector;
use SebastianBergmann\Diff\Parser;
use AronSzigetvari\TestSelector\CoverageReader\PhpUnitCoverage as PhpUnitCoverageReader;
use AronSzigetvari\TestSelector\Differ;

/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 04. 18.
 * Time: 22:20
 */
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

$differ = new Differ($repositoryPath);
$diff = $differ->getDiff($options['refstate']);


include_once($repositoryPath . '/vendor/autoload.php');


$coverageReader = new PhpUnitCoverageReader($coverage, $repositoryPath, '\\');
$testSelector = new TestSelector($diff, $coverageReader, $repositoryPath);

$tests = $testSelector->selectTestsByCoveredLines();
$modifiedTests = $testSelector->selectModifiedOrNewTests();

print_r($tests);
print_r($modifiedTests);

echo $testSelector->createHierarchicFilterPattern($tests);

//echo '"' . str_replace('\\', '\\\\', $testSelector->createFilterPattern($tests)) . '"';