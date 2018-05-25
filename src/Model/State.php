<?php
/**
 * Created by PhpStorm.
 * User: Aron
 * Date: 2018. 05. 25.
 * Time: 13:59
 */

namespace AronSzigetvari\TestSelector\Model;


class State
{
    /** @var int */
    private $id;

    /** @var string */
    private $commit;

    public function __construct(int $id, string $commit)
    {
        $this->id = $id;
        $this->commit = $commit;
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
     * @return State
     */
    public function setId(int $id): State
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommit(): string
    {
        return $this->commit;
    }

    /**
     * @param string $commit
     * @return State
     */
    public function setCommit(string $commit): State
    {
        $this->commit = $commit;
        return $this;
    }


}