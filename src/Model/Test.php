<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 13:59
 */

namespace AronSzigetvari\TestSelector\Model;


class Test
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;


    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Test
     */
    public function setId(int $id): Test
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Test
     */
    public function setName(string $name): Test
    {
        $this->name = $name;
        return $this;
    }


}