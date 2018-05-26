<?php
namespace AronSzigetvari\TestSelector;


abstract class Command
{
    /** @var \StdClass */
    protected $config = [];

    /** @var array Options passed to the command as extra option (not switches) */
    protected $extraOptions = [];

    protected $shortOptions = 'c:';

    protected $longOptions = [
        'config:'
    ];

    protected $optionMap = [
        'c' => 'config',
    ];

    public static function main()
    {
        $command = new static;

        return $command->run($_SERVER['argv']);
    }

    protected function run(array $argv)
    {
        $this->processConfig($argv);
    }

    protected function processConfig(array $argv)
    {
        $options = $this->parseCommandLineOptions($argv);

        $configFile = null;
        if (isset($options['config'])) {
            $configFile = $options['config'];
            if (!is_file($configFile)) {
                $this->error("Config file does not exist");
            }
        } elseif (is_file('testselector.json')) {
            $configFile = 'testselector.json';
        }
        if ($configFile) {
            $configContent = file_get_contents($configFile);
            $config = json_decode($configContent);
            if (!$config) {
                $this->error("Invalid configuration");
            }
        } else {
            $config = new \StdClass();
        }

        $this->config = $config;
        $this->processCommandLineOptions($options);
    }

    protected function parseCommandLineOptions(array $argv)
    {
        $options = getopt($this->shortOptions, $this->longOptions,$optind);

        $this->extraOptions = array_slice($argv, $optind);

        foreach ($this->optionMap as $short => $long) {
            if (isset($options[$short])) {
                $options[$long] = $options[$short];
                unset($options[$short]);
            }
        }

        return $options;
    }

    protected function processCommandLineOptions($options)
    {
    }

    protected function error($message, $errorCode = 1) {
        echo $message . "\n";
        exit($errorCode);
    }
}