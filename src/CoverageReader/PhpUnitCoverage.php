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

    /** @var string */
    private $codeCoverageBase;

    /** @var string */
    private $codeCoverageDS;

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

    public function getSourceFiles(): array
    {
        return array_keys($this->codeCoverageData);
    }
}