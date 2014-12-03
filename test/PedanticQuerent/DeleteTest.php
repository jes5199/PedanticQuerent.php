<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfDeletes;
use \PedanticQuerent\Query\Raw\RawDeleteQuery;
use \PDO;

class DeleteTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const DELETE_ZERO = "DELETE FROM test WHERE 0";
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

    function testDeleteOne() {
        $query = new RawDeleteQuery(self::DELETE_ONE);
        $ok = $this->querent->deleteOne($query);
        $this->assertEquals(1, $ok);

        try {
            $query = new RawDeleteQuery(self::DELETE_ZERO);
            $count = $this->querent->deleteOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected exactly one record deleted, got 0", $e->getMessage());
        }

        try {
            $query = new RawDeleteQuery(self::DELETE_TWO);
            $count = $this->querent->deleteOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected exactly one record deleted, got 2", $e->getMessage());
        }
    }

    function testDeleteMaybe() {
        $query = new RawDeleteQuery(self::DELETE_ONE);
        $count = $this->querent->deleteMaybe($query);
        $this->assertEquals(1, $count);

        $query = new RawDeleteQuery(self::DELETE_ZERO);
        $count = $this->querent->deleteMaybe($query);
        $this->assertEquals(0, $count);

        try {
            $query = new RawDeleteQuery(self::DELETE_TWO);
            $count = $this->querent->deleteMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected no more than one record deleted, got 2", $e->getMessage());
        }
    }

    function testDeleteSome() {
        $query = new RawDeleteQuery(self::DELETE_ONE);
        $count = $this->querent->deleteSome($query);
        $this->assertEquals(1, $count);

        try {
            $query = new RawDeleteQuery(self::DELETE_ZERO);
            $count = $this->querent->deleteSome($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfDeletes $e) {
            $this->assertEquals("Expected at least one record deleted, got 0", $e->getMessage());
        }

        $query = new RawDeleteQuery(self::DELETE_TWO);
        $count = $this->querent->deleteSome($query);
        $this->assertEquals(2, $count);
    }

    function testDeleteAny() {
        $query = new RawDeleteQuery(self::DELETE_ONE);
        $count = $this->querent->deleteAny($query);
        $this->assertEquals(1, $count);

        $query = new RawDeleteQuery(self::DELETE_ZERO);
        $count = $this->querent->deleteAny($query);
        $this->assertEquals(0, $count);

        $query = new RawDeleteQuery(self::DELETE_TWO);
        $count = $this->querent->deleteAny($query);
        $this->assertEquals(2, $count);
    }
}
