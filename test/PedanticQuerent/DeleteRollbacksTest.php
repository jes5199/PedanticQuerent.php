<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfDeletes;
use \PedanticQuerent\Query\Raw\RawDeleteQuery;
use \PDO;

class DeleteRollbacksTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const DELETE_ONE = "DELETE FROM test WHERE value = 1";
    const DELETE_TWO = "DELETE FROM test WHERE value > 1";

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
        self::$pdo->exec("INSERT INTO test VALUES (3)");
    }

    public function countValues() {
        return self::$pdo->query("SELECT COUNT(value) FROM test")->fetch()[0];
    }

    public function testDeleteOne() {
        $this->assertEquals(3, $this->countValues());

        $query = new RawDeleteQuery(self::DELETE_ONE);
        $ok = $this->querent->deleteOne($query);
        $this->assertEquals(1, $ok);
        $this->assertEquals(2, $this->countValues());

        try {
            $query = new RawDeleteQuery(self::DELETE_TWO);
            $count = $this->querent->deleteOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected exactly one record deleted, got 2", $e->getMessage());
        }

        $this->assertEquals(2, $this->countValues());
    }

    function testDeleteMaybe() {
        $this->assertEquals(3, $this->countValues());

        $query = new RawDeleteQuery(self::DELETE_ONE);
        $count = $this->querent->deleteMaybe($query);
        $this->assertEquals(1, $count);

        $this->assertEquals(2, $this->countValues());

        try {
            $query = new RawDeleteQuery(self::DELETE_TWO);
            $count = $this->querent->deleteMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected no more than one record deleted, got 2", $e->getMessage());
        }

        $this->assertEquals(2, $this->countValues());
    }
}
