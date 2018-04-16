<?php

use SebastianBergmann\Diff\Parser;
use SebastianBergmann\Git\Git;

require_once 'vendor/autoload.php';

exec('git diff --no-ext-diff -U0 *.php', $outputLines);

$parser = new Parser;
$diff = $parser->parse(implode("\n", $outputLines));
//var_dump($diff);

echo $diff[0]->getFrom();
var_dump($diff[0]->getChunks());