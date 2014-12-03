<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfResults;
use \PedanticQuerent\Query\Raw\RawSelectQuery;
use \PDO;

class SelectTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const SELECT_ZERO = "SELECT value FROM test WHERE 0";
    const SELECT_ONE = "SELECT 1 AS number";
    const SELECT_TWO = "SELECT value FROM test";

    public static function setUpBeforeClass() {
        self::$pdo = new PDO("sqlite:/tmp/querent.sqlite");
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS test (value integer)");
        self::$pdo->exec("DELETE FROM test");
        self::$pdo->exec("INSERT INTO test VALUES (1)");
        self::$pdo->exec("INSERT INTO test VALUES (2)");
    }

    public function setUp() {
        $this->querent = new Querent(self::$pdo);
    }

    function testSelectOne() {
        $query = new RawSelectQuery(self::SELECT_ONE);
        $row = $this->querent->selectOne($query);
        $this->assertEquals(["number" => 1], $row);

        try {
            $query = new RawSelectQuery(self::SELECT_ZERO);
            $row = $this->querent->selectOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfResults $e) {
            $this->assertEquals("Expected exactly one record returned, got 0", $e->getMessage());
        }

        try {
            $query = new RawSelectQuery(self::SELECT_TWO);
            $row = $this->querent->selectOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfResults $e) {
            $this->assertEquals("Expected exactly one record returned, got 2", $e->getMessage());
        }
    }

    function testSelectMaybe() {
        $query = new RawSelectQuery(self::SELECT_ONE);
        $row = $this->querent->selectMaybe($query);
        $this->assertEquals(["number" => 1], $row);

        $query = new RawSelectQuery(self::SELECT_ZERO);
        $nothing = $this->querent->selectMaybe($query);
        $this->assertNull($nothing);

        try {
            $query = new RawSelectQuery(self::SELECT_TWO);
            $row = $this->querent->selectMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfResults $e) {
            $this->assertEquals("Expected no more than one record returned, got 2", $e->getMessage());
        }
    }

    function testSelectSome() {
        $query = new RawSelectQuery(self::SELECT_ONE);
        $rows = $this->querent->selectSome($query);
        $this->assertEquals([["number" => 1]], $rows);

        try {
            $query = new RawSelectQuery(self::SELECT_ZERO);
            $rows = $this->querent->selectSome($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfResults $e) {
            $this->assertEquals("Expected at least one record returned, got 0", $e->getMessage());
        }

        $query = new RawSelectQuery(self::SELECT_TWO);
        $rows = $this->querent->selectSome($query);
        $this->assertEquals([["value" => 1], ["value" => 2]], $rows);
    }

    function testSelectAny() {
        $query = new RawSelectQuery(self::SELECT_ONE);
        $rows = $this->querent->selectAny($query);
        $this->assertEquals([["number" => 1]], $rows);

        $query = new RawSelectQuery(self::SELECT_ZERO);
        $rows = $this->querent->selectAny($query);
        $this->assertEquals([], $rows);

        $query = new RawSelectQuery(self::SELECT_TWO);
        $rows = $this->querent->selectAny($query);
        $this->assertEquals([["value" => 1], ["value" => 2]], $rows);
    }
}
