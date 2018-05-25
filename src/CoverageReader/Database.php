<?php
namespace AronSzigetvari\TestSelector\CoverageReader;

use AronSzigetvari\TestSelector\CoverageReader;
use PDO;

class Database implements CoverageReader
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $commit;

    /**
     * @var \PDOStatement
     */
    private $hasCoverageStatement;

    /**
     * @var \PDOStatement
     */
    private $getTestsForLineStatement;

    /**
     * @var \PDOStatement
     */
    private $getTestsForLinesStatement;

    /**
     * Database-based coverage reader constructor.
     * @param PDO $pdo
     * @param string $commit
     */
    public function __construct(PDO $pdo, string $commit)
    {
        $this->pdo = $pdo;
        $this->commit = $commit;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasCoverageForFile(string $path)
    {
        if ($this->hasCoverageStatement) {
            $stmt = $this->hasCoverageStatement;
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT sf.id\n"
                . "FROM state s\n"
                . "	   JOIN depends d ON s.id = d.state_id\n"
                . "    JOIN source_file sf ON d.source_file_id = sf.id\n"
                . "WHERE s.commit = ? \n"
                . "	   AND sf.path = ? \n"
                . "LIMIT 1 \n"
            );
            $this->hasCoverageStatement = $stmt;
        }
        $stmt->execute([$this->commit, $path]);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return count($result) > 0;
    }

    /**
     * @param string $path
     * @param int $line
     * @return array
     */
    public function getTestsForLine(string $path, int $line)
    {
        if ($this->getTestsForLineStatement) {
            $stmt = $this->getTestsForLineStatement;
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT t.name\n"
                . "FROM state s\n"
                . "	   JOIN depends d ON s.id = d.state_id\n"
                . "    JOIN source_file sf ON d.source_file_id = sf.id\n"
                . "    JOIN test t ON d.test_id = t.id \n"
                . "WHERE s.commit = ? \n"
                . "	   AND sf.path = ? \n"
                . "    AND d.line_from <= ? AND d.line_to >= ? \n"
            );
            $this->getTestsForLineStatement = $stmt;
        }
        $stmt->execute([$this->commit, $path, $line, $line]);
        $tests = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tests;
    }

    /**
     * @param string $path
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getTestsForLines(string $path, int $start, int $end)
    {
        if ($this->getTestsForLinesStatement) {
            $stmt = $this->getTestsForLinesStatement;
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT t.name\n"
                . "FROM state s\n"
                . "	   JOIN depends d ON s.id = d.state_id\n"
                . "    JOIN source_file sf ON d.source_file_id = sf.id\n"
                . "    JOIN test t ON d.test_id = t.id \n"
                . "WHERE s.commit = ? \n"
                . "	   AND sf.path = ? \n"
                . "    AND d.line_from <= ? AND d.line_to >= ? \n"
            );
            $this->getTestsForLinesStatement = $stmt;
        }
        $stmt->execute([$this->commit, $path, $end, $start]);
        $tests = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $tests;
    }
}