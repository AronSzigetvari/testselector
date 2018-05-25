<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 13:59
 */

namespace AronSzigetvari\TestSelector\Model;


class DependentRange
{
    /** @var int */
    private $lineFrom;

    /** @var int */
    private $lineTo;

    /** @var string */
    private $type;

    /** @var SourceFile */
    private $sourceFile;

    /** @var Test */
    private $test;

    /** @var State */
    private $state;

    /**
     * @return int|null
     */
    public function getLineFrom()
    {
        return $this->lineFrom;
    }

    /**
     * @param int $lineFrom
     * @return DependentRange
     */
    public function setLineFrom(int $lineFrom): DependentRange
    {
        $this->lineFrom = $lineFrom;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLineTo()
    {
        return $this->lineTo;
    }

    /**
     * @param int $lineTo
     * @return DependentRange
     */
    public function setLineTo(int $lineTo): DependentRange
    {
        $this->lineTo = $lineTo;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return DependentRange
     */
    public function setType(string $type): DependentRange
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return SourceFile
     */
    public function getSourceFile(): SourceFile
    {
        return $this->sourceFile;
    }

    /**
     * @param SourceFile $sourceFile
     * @return DependentRange
     */
    public function setSourceFile(SourceFile $sourceFile): DependentRange
    {
        $this->sourceFile = $sourceFile;
        return $this;
    }

    /**
     * @return Test
     */
    public function getTest(): Test
    {
        return $this->test;
    }

    /**
     * @param Test $test
     * @return DependentRange
     */
    public function setTest(Test $test): DependentRange
    {
        $this->test = $test;
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
     * @return DependentRange
     */
    public function setState(State $state): DependentRange
    {
        $this->state = $state;
        return $this;
    }


}