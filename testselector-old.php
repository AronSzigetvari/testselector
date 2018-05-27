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
    'r:R:c:e:s:',
    [
        'refstate:',
        'endstate:',
        'repository:',
        'connection:',
        'strategy:',
    ]
);


$map = [
    'r' => 'refstate',
    'e' => 'endstate',
    'R' => 'repository',
    'c' => 'connection',
    's' => 'strategy'
];

foreach ($map as $old => $new) {
    if (isset($options[$old])) {
        $options[$new] = $options[$old];
        unset($options[$old]);
    }
}

if (!isset($options['strategy'])) {
    $options['strategy'] = 'line';
}

$dsn = $options['connection'];
$pdo = new PDO($dsn, 'root', '');

$repositoryPath = realpath($options['repository']);

$coverageQuery = new \AronSzigetvari\TestSelector\CoverageQuery\PDO($pdo, $options['refstate']);

$differ = new Differ($repositoryPath);
switch ($options['strategy']) {
    case 'line':
    case 'function':
    case 'class':
        $diff = $differ->getLineBasedDiff($options['refstate'], $options['endstate'] ?? null);
        $testSelector = new AronSzigetvari\TestSelector\TestSelectorStrategy\LineRangeBased($diff, $coverageQuery);
        $tests = $testSelector->selectTestsByCoveredLines($options['strategy']);
        break;
    case 'file':
        $diff = $differ->getFileBasedDiff($options['refstate'], $options['endstate'] ?? null);
        $testSelector = new AronSzigetvari\TestSelector\TestSelectorStrategy\FileBased($diff, $coverageQuery);
        $tests = $testSelector->selectTestsByCoveredFiles();
        break;
    default:
        echo "Invalid strategy specified\n";
        exit(1);
}

print_r($tests);
$patternGenerator = new \AronSzigetvari\TestSelector\FilterGenerator();
if (empty($tests)) {
    echo "No tests were selected.\n";
} else {
    echo $patternGenerator->createHierarchicPattern($tests);
}



if (!isset($options['endstate'])) {
    $modifiedTests = $testSelector->selectModifiedOrNewTests();
    include_once($repositoryPath . '/vendor/autoload.php');
    print_r($modifiedTests);


    if (empty($modifiedTests)) {
        echo "No tests were selected.\n";
    } else {
        echo $patternGenerator->createHierarchicPattern($modifiedTests);
    }
}



//echo '"' . str_replace('\\', '\\\\', $testSelector->createFilterPattern($tests)) . '"';