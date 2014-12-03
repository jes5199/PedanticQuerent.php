<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfInserts;
use \PedanticQuerent\Query\Raw\RawInsertQuery;
use \PDO;

class InsertReturningIDsTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const INSERT_ZERO = "INSERT INTO auto SELECT value FROM auto WHERE 0";
    const INSERT_ONE = "INSERT INTO auto VALUES (null)";
    const INSERT_TWO = "INSERT INTO auto SELECT null FROM auto WHERE value <= 2";

    public static function setUpBeforeClass() {
        self::$pdo = new PDO("sqlite:/tmp/querent.sqlite");
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS auto (value INTEGER PRIMARY KEY)");
    }

    public function setUp() {
        $this->querent = new Querent(self::$pdo);
        self::$pdo->exec("DELETE FROM auto");
        self::$pdo->exec("INSERT INTO auto VALUES (1)");
        self::$pdo->exec("INSERT INTO auto VALUES (2)");
    }

    function testInsertOneWithNewID() {
        $query = new RawInsertQuery(self::INSERT_ONE);
        $id = $this->querent->insertOneWithNewID($query);
        $this->assertEquals(3, $id);

        try {
            $query = new RawInsertQuery(self::INSERT_ZERO);
            $id = $this->querent->insertOneWithNewID($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected exactly one record inserted, got 0", $e->getMessage());
        }

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $id = $this->querent->insertOneWithNewID($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected exactly one record inserted, got 2", $e->getMessage());
        }
    }

    function testInsertMaybeWithNewID() {
        $query = new RawInsertQuery(self::INSERT_ONE);
        $id = $this->querent->insertMaybeWithNewID($query);
        $this->assertEquals(3, $id);

        $query = new RawInsertQuery(self::INSERT_ZERO);
        $id = $this->querent->insertMaybeWithNewID($query);
        $this->assertEquals(null, $id);

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $id = $this->querent->insertMaybeWithNewID($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected no more than one record inserted, got 2", $e->getMessage());
        }
    }
}
