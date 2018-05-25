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

    public function hasCoverageForFile(string $path): bool
    {
        $ccPath = $this->relative2CodeCoveragePath($path);
        return isset($this->codeCoverageData[$ccPath]);
    }

    public function getCoverageForFile(string $path): array
    {
        return $this->codeCoverageData[$path];
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

            // Extend range to reach previous executable line
            while ($start >= 1 && !isset($fileCoverage[$start])) {
                $start--;
            }
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

    public function getSourceFiles(): array
    {
        return array_keys($this->codeCoverageData);
    }
}