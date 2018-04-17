<?php

use SebastianBergmann\Diff\Parser;


$repo = $argv[1];
$diffParam = $argv[2];

require_once 'vendor/autoload.php';

$git = new GitWrapper\GitWrapper();
$output = $git->git('diff --no-ext-diff -U0 ' . $diffParam . ' *.php', $repo);

//echo $output;
//die;
$parser = new Parser;
$diff = $parser->parse($output);
//var_dump($diff);

echo $diff[0]->getFrom() . "\n";
echo $diff[0]->getTo() . "\n";
echo $diff[0]->getChunks()[0]->getStart() . "\n";
echo $diff[0]->getChunks()[0]->getEnd() . "\n";
echo $diff[0]->getChunks()[0]->getStartRange() . "\n";
echo $diff[0]->getChunks()[0]->getEndRange() . "\n";
print_r($diff[0]->getChunks()[0]->getLines()) . "\n";
//var_dump($diff[0]->getChunks());
