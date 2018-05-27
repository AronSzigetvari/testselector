<?php
namespace AronSzigetvari\TestSelector\PHPUnit;
use PHPUnit\TextUI\Command;

class TestSelectorCommand extends Command
{

    public function __construct()
    {
        $this->longOptions['ts-refstate='] = 'tsRefstateHandler';
        $this->longOptions['ts-repository='] = 'tsRepositoryHandler';
        $this->longOptions['ts-config='] = 'tsConfigHandler';
        $this->longOptions['ts-strategy='] = 'tsStrategyHandler';
    }

    protected function tsRefstateHandler($value)
    {
        $this->arguments['testselector']['refstate'] = $value;
    }

    protected function tsRepositoryHandler($value)
    {
        $this->arguments['testselector']['repository'] = $value;
    }

    protected function tsConfigHandler($value)
    {
        $this->arguments['testselector']['config'] = $value;
    }

    protected function tsStrategyHandler($value)
    {
        $this->arguments['testselector']['strategy'] = $value;
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