<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 13:59
 */

namespace AronSzigetvari\TestSelector\Model;


class SourceFile
{
    /** @var int */
    private $id;

    /** @var string */
    private $path;

    public function __construct(int $id, string $path)
    {
        $this->id = $id;
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

}