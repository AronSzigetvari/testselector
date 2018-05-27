<?php
namespace AronSzigetvari\TestSelector;

interface CoverageReader {
    public function getSourceFiles(): array;

    public function getCoverageForFile(string $path): array;

    public function hasCoverageForFile(string $path): bool;

}