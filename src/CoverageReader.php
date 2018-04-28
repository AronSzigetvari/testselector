<?php
namespace AronSzigetvari\TestSelector;

interface CoverageReader {
    public function hasCoverageForFile(string $path);

    public function getTestsForLine(string $path, int $line);

    public function getTestsForLines(string $path, int $start, int $end);

}