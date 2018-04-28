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

    public function getDiff(string $refstate) {
        $diffOutput = $this->git->git(
            'diff --no-ext-diff --no-prefix -U0 ' . $refstate . ' *.php',
            $this->repositoryPath
        );

        $parser = new Parser;
        $diff = $parser->parse($diffOutput);
        return $diff;
    }
}