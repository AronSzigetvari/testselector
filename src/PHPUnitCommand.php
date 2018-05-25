<?php
namespace AronSzigetvari\TestSelector;
use PHPUnit\TextUI\Command;

class PHPUnitCommand extends Command
{

    public function __construct()
    {
        $this->longOptions['refstate='] = 'refStateHandler';
        $this->longOptions['repository='] = 'repositoryHandler';
        $this->longOptions['refcoveragefile='] = 'refCoverageFileHandler';
        $this->longOptions['refcoveragedsn='] = 'refCoveragedsnHandler';
    }

    protected function refStateHandler($value)
    {
        $this->arguments['testselector']['refstate'] = $value;
    }

    protected function repositoryHandler($value)
    {
        $this->arguments['testselector']['repository'] = $value;
    }

    protected function refCoverageFileHandler($value)
    {
        $this->arguments['testselector']['refcoveragefile'] = $value;
    }

    protected function refCoverageDsnHandler($value)
    {
        $this->arguments['testselector']['refcoveragedsn'] = $value;
    }

    /**
     * Creates TestRunner
     *
     * Overridden for creating our own TestRunner instead of original PHPUnit TestRunner
     *
     * @return TestRunner|\PHPUnit\TextUI\TestRunner
     */
    protected function createRunner()
    {
        return new TestRunner($this->arguments['loader']);
    }

    public static function main($exit = true)
    {
      $command = new static;

      return $command->run($_SERVER['argv'], $exit);
    }
}