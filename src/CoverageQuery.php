<?php
namespace AronSzigetvari\TestSelector;


interface CoverageQuery
{
    /**
     * @param string $path
     * @return bool
     */
    public function hasCoverageForFile(string $path): bool;

    /**
     * @param string $path
     * @param int $line
     * @return array
     */
    public function getTestsForLine(string $path, string $strategy, int $line): array;

    /**
     * @param string $path
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getTestsForLines(string $path, string $strategy, int $start, int $end): array;


    /**
     * @param string $path
     * @return array
     */
    public function getTestsForSourceFile(string $path): array;
}