<?php
namespace AronSzigetvari\TestSelector;

use PHP_Token_Stream as TokenStream;
use SebastianBergmann\Diff\Diff;



class ModifiedTestSelector
{
    /** @var array|Diff[] */
    private $diff;

    /** @var string */
    private $repositoryBase;

    /**
     * TestSelector constructor.
     * @param array $diff
     * @param string $repositoryBase
     */
    public function __construct(
        array $diff,
        string $repositoryBase
    ) {
        $this->diff = $diff;
        $this->repositoryBase = $repositoryBase;
    }

    public function selectModifiedOrNewTests() : array
    {
        $selectedTests = [];
        $selectedClasses = [];

        foreach ($this->diff as $diff) {
            if ($diff->getTo() === '/dev/null') {
                continue; // Deleted file
            }

            $fullPath = realpath($this->repositoryBase . '/' . $diff->getTo());
            if (!$this->isTestFile($fullPath)) {
                continue; // Omit non-test files
            }

            $tokenStream = new TokenStream(file_get_contents($fullPath));

            if ($diff->getFrom() === '/dev/null') {
                // This is a new test file, add all test classes
                foreach ($tokenStream->getClasses() as $className => $classInfo) {
                    $selectedClasses[] = $className;
                }
                continue;
            }

            $lastFunctionName = null;
            $skipUntilLine = null;

            foreach ($diff->getChunks() as $chunk) {
                $startLine = $chunk->getEnd();
                $lines = $chunk->getEndRange();
                for ($i = 0; $i < $lines; $i++) {
                    $currentLine = $startLine + $i;
                    if ($skipUntilLine) {
                        if ($currentLine <= $skipUntilLine) {
                            continue;
                        } else {
                            $skipUntilLine = null;
                        }
                    }

                    $functionName = $tokenStream->getFunctionForLine($currentLine);

                    if ($functionName) {
                        if ($lastFunctionName === $functionName) {
                            // We are in the same method/function
                            continue;
                        }
                        if ($this->isTestMethod($functionName, $tokenStream)) {
                            // Add test method to the list of retestable methods
                            $selectedTests[] = $functionName;
                        } elseif (strpos($functionName, '::') !== false) {
                            // Not a test method but it may affect any test methods in the class
                            list($className, $methodName) = explode('::', $functionName);
                            $selectedClasses[] = $className;
                            $skipUntilLine = $tokenStream->getClasses()[$className]['endLine'];
                            $selectedTests = $this->removeByPrefix($selectedTests, $className . '::');
                        }
                        $lastFunctionName = $functionName;
                    }
                }
            }
        }
        return array_merge($selectedClasses, $selectedTests);
    }

    private function isTestClass(string $className)
    {
        return substr($className, -4) === 'Test';
    }

    private function isTestMethod(string $functionName, TokenStream $tokenStream)
    {
        if (strpos($functionName, '::') !== false) {
            list($className, $methodName) = explode('::', $functionName);
            if ($className && $methodName) {
                if ($this->isTestClass($className)) {
                    if (substr($methodName, 0, 4) === 'test') {
                        return true;
                    }
                    $classes = $tokenStream->getClasses();
                    $method = $classes[$className]['methods'][$methodName];
                    $docbook = $method['docbook'];
                    if (strpos($docbook, '@test')) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function removeByPrefix(array $array, string $prefix) {
        $prefixLength = strlen($prefix);
        $result = array_filter(
            $array,
            function ($element) use ($prefix, $prefixLength) {
                return substr($element, 0, $prefixLength) !== $prefix;
            }
        );
        return $result;
    }

    /**
     * Returns if the filename given is a test file
     *
     * Condition: Test files are those that end with Test.php
     *
     * @param string $file
     * @return bool
     */
    protected function isTestFile(string $file) : bool
    {
        return (substr($file, -8) === 'Test.php');
    }


}