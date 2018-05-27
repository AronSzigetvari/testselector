<?php
namespace AronSzigetvari\TestSelector;

interface CoverageReader {
    public function getSourceFiles(): array;

    public function getCoverageForFile(string $path): array;

}