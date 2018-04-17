<?php

use SebastianBergmann\Diff\Parser;
use SebastianBergmann\Git\Git;

$repo = $argv[1];
$diffParam = $argv[2];

require_once 'vendor/autoload.php';

$cwd = getcwd();

$cdCommand = 'cd ' . escapeshellarg($repo);
$command = 'giti dff --no-ext-diff -U0 ' . $diffParam . ' *.php';
$command = 'git status';

exec($cdCommand);
exec($command, $outputLines);

print_r($outputLines);
die;
//$descriptorspec = array(
//    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
//    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
//    2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
//);
//
//$process = proc_open($command, $descriptorspec, $pipes, $repo);
//if (is_resource($process))
chdir($cwd);

$parser = new Parser;
$diff = $parser->parse(implode("\n", $outputLines));
//var_dump($diff);

echo $diff[0]->getFrom();
var_dump($diff[0]->getChunks());