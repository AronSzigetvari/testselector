<?php
namespace AronSzigetvari\TestSelector;

use SebastianBergmann\Diff\Parser;
use GitWrapper\GitWrapper;


class Differ
{
    /** @var string */
    private $repositoryPath;

    public function __construct(string $repositoryPath)
    {
        $this->git = new GitWrapper();
        $this->repositoryPath = $repositoryPath;
    }

    public function getLineBasedDiff(string $startCommit, string $endCommit = null) {
        if ($endCommit) {
            $param = escapeshellarg($startCommit) . " " . escapeshellarg($endCommit);
        } else {
            $param = escapeshellarg($startCommit);
        }
        $diffOutput = $this->git->git(
            'diff --no-ext-diff --no-prefix -U0 ' . $param . ' *.php',
            $this->repositoryPath
        );

        $parser = new Parser;
        $diff = $parser->parse($diffOutput);
        return $diff;
    }

    public function getFileBasedDiff(string $startCommit, string $endCommit = null) {
        if ($endCommit) {
            $param = escapeshellarg($startCommit) . " " . escapeshellarg($endCommit);
        } else {
            $param = escapeshellarg($startCommit);
        }
        echo 'diff --name-only ' . $param . ' *.php';
        $diffOutput = $this->git->git(
            'diff --name-only ' . $param . ' *.php',
            $this->repositoryPath
        );


        $lines = array_filter(
            explode("\n", $diffOutput),
            function ($value) {
                return trim($value) !== '';
            }
        );
        return $lines;
    }
}