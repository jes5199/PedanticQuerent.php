<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfInserts;
use \PedanticQuerent\Query\Raw\RawInsertQuery;
use \PDO;

class InsertRollbacksTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const INSERT_ONE = "INSERT INTO test VALUES (3)";
    const INSERT_TWO = "INSERT INTO test SELECT value FROM test WHERE value <= 2";

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

    public function countValues() {
        return self::$pdo->query("SELECT COUNT(value) FROM test")->fetch()[0];
    }

    public function testInsertOne() {
        $this->assertEquals(2, $this->countValues());

        $query = new RawInsertQuery(self::INSERT_ONE);
        $ok = $this->querent->insertOne($query);
        $this->assertEquals(1, $ok);
        $this->assertEquals(3, $this->countValues());

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $count = $this->querent->insertOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected exactly one record inserted, got 2", $e->getMessage());
        }

        $this->assertEquals(3, $this->countValues());
    }

    function testInsertMaybe() {
        $this->assertEquals(2, $this->countValues());

        $query = new RawInsertQuery(self::INSERT_ONE);
        $count = $this->querent->insertMaybe($query);
        $this->assertEquals(1, $count);

        $this->assertEquals(3, $this->countValues());

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $count = $this->querent->insertMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected no more than one record inserted, got 2", $e->getMessage());
        }

        $this->assertEquals(3, $this->countValues());
    }
}
