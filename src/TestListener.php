<?php
namespace AronSzigetvari\TestSelector;

use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Áron Szigetvári
 */

class TestListener implements TestListenerInterface
{
    use TestListenerDefaultImplementation;

    public function startTest(Test $test): void
    {
        if ($test instanceof TestCase) {

            echo $test->getName() . " execution started\n";
            $test->getTestResultObject()->getCodeCoverage();
        }
    }

    public function endTest(Test $test, float $time): void
    {
        if ($test instanceof TestCase) {

            echo $test->getName() . " execution ended.\n";
        }
    }
}