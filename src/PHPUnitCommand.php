<?php
namespace AronSzigetvari\TestSelector;
use PHPUnit\TextUI\Command;

class PHPUnitCommand extends Command
{

    public function __construct()
    {
        // my-secondswitch will accept a value - note the equals sign
        $this->longOptions['refstate='] = 'refstateHandler';
        $this->longOptions['repository='] = 'repositoryHandler';
        $this->longOptions['refcoverage='] = 'refcoverageHandler';
    }

    protected function refstateHandler($value)
    {
        $this->arguments['testselector']['refstate'] = $value;
    }

    protected function repositoryHandler($value)
    {
        $this->arguments['testselector']['repository'] = $value;
    }

    protected function refcoverageHandler($value)
    {
        $this->arguments['testselector']['refcoverage'] = $value;
    }

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