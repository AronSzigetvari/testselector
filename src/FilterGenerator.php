<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 11:58
 */

namespace AronSzigetvari\TestSelector;


class FilterGenerator
{
    public function createSimplePattern(array $tests)
    {
        $pattern = implode('|', $tests);

        return '/' . $pattern . '$/';
    }

    public function createHierarchicPattern(array $tests)
    {
        $tree = [];
        sort($tests);
        foreach ($tests as $test) {
            $matches = null;
            if (preg_match('/^([\w\\\\]+)(?:::(\w+)(?: with data set (.*))?)?$/', $test, $matches)) {
                $class = $matches[1];
                $method = $matches[2] ?? false;
                $dataSet = (isset($matches[3])) ? $matches[3] : false;
                $namespaceComponents = explode('\\', $class);

                $components = [];

                foreach ($namespaceComponents as $index => $component) {
                    if ($index > 0) {
                        $components[] = '\\';
                    }
                    $components[] = $component;
                }
                if ($method) {
                    $components[] = '::';
                    $components[] = $method;
                    if ($dataSet) {
                        $components[] = ' with data set ';
                        $components[] = $dataSet;
                    }
                }

                $subtree = &$tree;
                $lastComponent = array_pop($components);
                foreach ($components as $component) {
                    if (!isset($subtree[$component])) {
                        $subtree[$component] = [];
                    }
                    $subtree = &$subtree[$component];
                }
                $subtree[$lastComponent] = true;

            } else {
                $tree[$test] = true;
            }
        }
        return '/^' . $this->createPatternFromTree($tree) . '/';
    }

    private function createPatternFromTree(array $tree): string
    {
        if ($tree === true) {
            return '';
        }
        $subpatterns = [];
        foreach ($tree as $key => $item) {
            $string = preg_quote($key);
            if (is_array($item)) {
                $string .= $this->createPatternFromTree($item);
            }
            $subpatterns[] = $string;
        }
        if (count($subpatterns) > 1) {
            return '(' . implode('|', $subpatterns) . ')';
        } else {
            return $subpatterns[0];
        }
    }
}