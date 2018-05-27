<?php
namespace AronSzigetvari\TestSelector\CoveragePersister;

use AronSzigetvari\TestSelector\CoveragePersister;
use AronSzigetvari\TestSelector\Model\DependentRange;
use AronSzigetvari\TestSelector\Model\SourceFile;
use AronSzigetvari\TestSelector\Model\State;
use AronSzigetvari\TestSelector\Model\Test;

class PDO implements CoveragePersister
{
    /** @var \PDO $pdo */
    private $pdo;

    /** @var array */
    private $mapsByString = [
        'test' => [],
        'source_file' => [],
        'state' => []
    ];

    /** @var \PDOStatement */
    private $saveRangeStatement;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findSourceFileByPath(string $path): SourceFile
    {
        return $this->saveOrRetrieveObject($path, 'source_file', 'path', 'SourceFile');
    }

    public function findStateByCommit(string $path): State
    {
        return $this->saveOrRetrieveObject($path, 'state', 'commit', 'State');
    }

    public function findTestByName(string $name): Test
    {
        return $this->saveOrRetrieveObject($name, 'test', 'name', 'Test');
    }

    private function saveOrRetrieveObject(string $name, string $table, string $field, string $class)
    {
        if (isset($this->maps[$table][$name])) {
            $object = $this->mapsByString[$table][$name];
        } else {
            $fqcn = 'AronSzigetvari\TestSelector\Model\\' . $class;
            $id = null;
            $stmt = $this->pdo->prepare("SELECT id FROM $table WHERE $field = ?");
            $stmt->execute([$name]);
            if ($stmt->rowCount()) {
                $id = $stmt->fetchColumn();
            }

            if (!$id) {
                $stmt = $this->pdo->prepare("INSERT INTO $table ($field) VALUES (?)");
                $stmt->execute([$name]);
                $id = $this->pdo->lastInsertId();
            }
            $object = new $fqcn($id, $name);
            $this->mapsByString[$table][$name] = $object;
        }

        return $object;
    }

    public function resetState(State $state, string $strategy): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM dependent_range WHERE state_id = ? AND strategy = ?");
        $stmt->execute([$state->getId(), $strategy]);
    }

    /**
     * @param DependentRange[] $ranges
     */
    public function saveDependentRanges(array $ranges): void
    {
        $this->pdo->beginTransaction();

        if ($this->saveRangeStatement) {
            $stmt = $this->saveRangeStatement;
        } else {
            $stmt = $this->saveRangeStatement = $stmt = $this->pdo->prepare(
            'INSERT INTO dependent_range (test_id, source_file_id, state_id, line_from, line_to, strategy) '
            . 'VALUES (?, ?, ?, ?, ?, ?)');
        }

        foreach ($ranges as $range) {
            $stmt->bindValue(1, $range->getTest()->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(2, $range->getSourceFile()->getId(), \PDO::PARAM_INT);
            $stmt->bindValue(3, $range->getState()->getId(), \PDO::PARAM_INT);
            if ($range->getType() === 'file') {
                $stmt->bindValue(4, $range->getLineFrom(), \PDO::PARAM_NULL);
                $stmt->bindValue(5, $range->getLineTo(), \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(4, $range->getLineFrom(), \PDO::PARAM_INT);
                $stmt->bindValue(5, $range->getLineTo(), \PDO::PARAM_INT);
            }
            $stmt->bindValue(6, $range->getType(), \PDO::PARAM_STR);
            $stmt->execute();
        }
        $this->pdo->commit();
    }
}