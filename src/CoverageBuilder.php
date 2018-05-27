<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 13:04
 */

namespace AronSzigetvari\TestSelector;


use AronSzigetvari\TestSelector\Model\State;

abstract class CoverageBuilder
{
    /** @var CoverageReader */
    protected $coverageReader;

    /** @var string */
    private $codeCoverageBase;

    /** @var string */
    private $codeCoverageDS;

    /** @var CoveragePersister */
    protected $coveragePersister;

    /** @var State */
    protected $state;

    /** @var bool */
    protected $showProgressDisplay;

    /**
     * @return CoverageReader
     */
    public function getCoverageReader(): CoverageReader
    {
        return $this->coverageReader;
    }

    /**
     * @param CoverageReader $coverageReader
     * @return CoverageBuilder
     */
    public function setCoverageReader(CoverageReader $coverageReader): CoverageBuilder
    {
        $this->coverageReader = $coverageReader;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodeCoverageBase(): string
    {
        return $this->codeCoverageBase;
    }

    /**
     * @param string $codeCoverageBase
     * @return CoverageBuilder
     */
    public function setCodeCoverageBase(string $codeCoverageBase): CoverageBuilder
    {
        $this->codeCoverageBase = $codeCoverageBase;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodeCoverageDS(): string
    {
        return $this->codeCoverageDS;
    }

    /**
     * @param string $codeCoverageDS
     * @return CoverageBuilder
     */
    public function setCodeCoverageDS(string $codeCoverageDS): CoverageBuilder
    {
        $this->codeCoverageDS = $codeCoverageDS;
        return $this;
    }

    /**
     * @return CoveragePersister
     */
    public function getCoveragePersister(): CoveragePersister
    {
        return $this->coveragePersister;
    }

    /**
     * @param CoveragePersister $coveragePersister
     * @return CoverageBuilder
     */
    public function setCoveragePersister(CoveragePersister $coveragePersister): CoverageBuilder
    {
        $this->coveragePersister = $coveragePersister;
        return $this;
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @param State $state
     * @return CoverageBuilder
     */
    public function setState(State $state): CoverageBuilder
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowProgressDisplay(): bool
    {
        return $this->showProgressDisplay;
    }

    /**
     * @param bool $useProgressDisplay
     * @return CoverageBuilder
     */
    public function setShowProgressDisplay(bool $useProgressDisplay): CoverageBuilder
    {
        $this->showProgressDisplay = $useProgressDisplay;
        return $this;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    protected function relative2CodeCoveragePath(string $relativePath)
    {
        return str_replace('/', $this->codeCoverageDS,$this->codeCoverageBase . '/' .  $relativePath);
    }

    /**
     * Returns the path converted to a repository relative path with normalized directory separators
     *
     * @param string $fullPath
     * @return string
     */
    protected function getRepositoryRelativePath(string $fullPath): string
    {
        $prefixLength = strlen($this->codeCoverageBase) + 1;
        if (substr($fullPath, 0, $prefixLength) !== $this->codeCoverageBase . $this->codeCoverageDS) {
            throw new \OutOfRangeException('Path (' . $fullPath . ') not in repository ' . $this->codeCoverageBase);
        }
        $rrPath = substr($fullPath, $prefixLength);

        if ($this->codeCoverageDS !== '/') {
            $rrPath = str_replace($this->codeCoverageDS, '/', $rrPath);
        }

        return $rrPath;
    }

    protected function getFullPath(string $relativePath): string
    {
        return str_replace(
            '/',
            $this->codeCoverageDS,
            $this->codeCoverageBase . '/' .  $relativePath
        );
    }


    public function buildCoverage()
    {
        $sourceFiles = $this->coverageReader->getSourceFiles();
        $count = count($sourceFiles);
        if ($this->showProgressDisplay) {
            echo "\n";
            $progress = 0;
        }
        foreach ($this->coverageReader->getSourceFiles() as $sourceFile) {
            if ($this->showProgressDisplay) {
                echo "\rsource file " . (++$progress) . '/' . $count;
            }
            $this->buildCoverageForFile($sourceFile);
        }
        if ($this->showProgressDisplay) {
            echo "\n";
        }
    }

    abstract function buildCoverageForFile(string $fullSourcePath): void;
}