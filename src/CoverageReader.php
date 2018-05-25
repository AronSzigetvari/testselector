<?php
namespace AronSzigetvari\TestSelector;

interface CoverageReader {
    public function getSourceFiles(): array;

    public function hasCoverageForFile(string $path): bool;

    public function getTestsForLine(string $path, int $line);

    public function getTestsForLines(string $path, int $start, int $end);

    public function getCoverageForFile(string $path): array;

}