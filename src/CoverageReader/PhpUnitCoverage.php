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
     * @param string $codeCoverageBase
     * @param string $codeCoverageDS
     */
    public function __construct(
        CodeCoverage $codeCoverage,
        string $codeCoverageBase,
        string $codeCoverageDS
    ) {
        $this->codeCoverage = $codeCoverage;
        $this->codeCoverageData = $codeCoverage->getData();
        $this->codeCoverageBase = $codeCoverageBase;
        $this->codeCoverageDS = $codeCoverageDS;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    private function relative2CodeCoveragePath(string $relativePath)
    {
        return str_replace('/', $this->codeCoverageDS,$this->codeCoverageBase . '/' .  $relativePath);
    }

    public function hasCoverageForFile(string $path)
    {
        $ccPath = $this->relative2CodeCoveragePath($path);
        return isset($this->codeCoverageData[$ccPath]);
    }

    /**
     * @param string $path
     * @param int $line
     * @return array|bool|null
     */
    public function getTestsForLine(string $path, int $line)
    {
        if ($this->hasCoverageForFile($path)) {
            $ccPath = $this->relative2CodeCoveragePath($path);
            $fileCoverage = $this->codeCoverageData[$ccPath];
            if (isset($fileCoverage[$line])) {
                return $fileCoverage[$line]; // executable line
            } else {
                return false; // non-executable line
            }
        } else {
            return null; // non-executable file
        }
    }

    /**
     * @param string $path
     * @param int $start
     * @param int $end
     * @return array|bool|null
     */
    public function getTestsForLines(string $path, int $start, int $end)
    {
        $tests = [];
        if ($this->hasCoverageForFile($path)) {
            $ccPath = $this->relative2CodeCoveragePath($path);
            $fileCoverage = $this->codeCoverageData[$ccPath];
            for ($line = $start; $line <= $end; $line++) {
                if (!empty($fileCoverage[$line])) {
                    $tests += $fileCoverage[$line];
                }
            }
        } else {
            return null; // non-executable file
        }
        return array_unique($tests);
    }
}