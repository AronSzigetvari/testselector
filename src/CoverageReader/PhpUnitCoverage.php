<?php
namespace AronSzigetvari\TestSelector\CoverageReader;

use AronSzigetvari\TestSelector\CoverageReader;
use SebastianBergmann\CodeCoverage\CodeCoverage;

class PhpUnitCoverage implements CoverageReader
{
    /** @var CodeCoverage */
    private $codeCoverage;

    /** @var array */
    private $codeCoverageData;

    /**
     * PhpUnitCoverage constructor.
     * @param CodeCoverage $codeCoverage
     */
    public function __construct(
        CodeCoverage $codeCoverage
    ) {
        $this->codeCoverage = $codeCoverage;
        $this->codeCoverageData = $codeCoverage->getData();
    }

    public function hasCoverageForFile(string $path): bool
    {
        return isset($this->codeCoverageData[$path]);
    }

    public function getCoverageForFile(string $path): array
    {
        return $this->codeCoverageData[$path];
    }

    public function getSourceFiles(): array
    {
        return array_keys($this->codeCoverageData);
    }
}