<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfUpdates;
use \PedanticQuerent\Query\Raw\RawUpdateQuery;
use \PDO;

class UpdateRollbacksTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const UPDATE_ONE = "UPDATE test SET value = 1 WHERE value = 2";
    const UPDATE_TWO = "UPDATE test SET value = 0";

    public static function setUpBeforeClass() {
        self::$pdo = new PDO("sqlite:/tmp/querent.sqlite");
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS test (value integer)");
    }

    public function setUp() {
        $this->querent = new Querent(self::$pdo);
        self::$pdo->exec("DELETE FROM test");
        self::$pdo->exec("INSERT INTO test VALUES (1)");
        self::$pdo->exec("INSERT INTO test VALUES (2)");
    }

    public function values() {
        return array_map(
            function($row) { return $row[0]; },
            self::$pdo->query("SELECT value FROM test")->fetchAll()
        );
    }

    public function testUpdateOne() {
        $this->assertEquals([1, 2], $this->values());

        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $ok = $this->querent->updateOne($query);
        $this->assertEquals(1, $ok);
        $this->assertEquals([1, 1], $this->values());

        try {
            $query = new RawUpdateQuery(self::UPDATE_TWO);
            $count = $this->querent->updateOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected exactly one record updated, got 2", $e->getMessage());
        }

        $this->assertEquals([1, 1], $this->values());
    }

    function testUpdateMaybe() {
        $this->assertEquals([1, 2], $this->values());

        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $count = $this->querent->updateMaybe($query);
        $this->assertEquals(1, $count);

        $this->assertEquals([1, 1], $this->values());

        try {
            $query = new RawUpdateQuery(self::UPDATE_TWO);
            $count = $this->querent->updateMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected no more than one record updated, got 2", $e->getMessage());
        }

        $this->assertEquals([1, 1], $this->values());
    }
}
