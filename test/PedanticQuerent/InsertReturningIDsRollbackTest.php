<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfInserts;
use \PedanticQuerent\Query\Raw\RawInsertQuery;
use \PDO;

class InsertReturningIDsRollbacksTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

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

    public function countValues() {
        return self::$pdo->query("SELECT COUNT(value) FROM auto")->fetch()[0];
    }

    public function testInsertOneWithNewID() {
        $this->assertEquals(2, $this->countValues());

        $query = new RawInsertQuery(self::INSERT_ONE);
        $id = $this->querent->insertOneWithNewID($query);
        $this->assertEquals(3, $id);

        $this->assertEquals(3, $this->countValues());

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $id = $this->querent->insertOneWithNewID($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected exactly one record inserted, got 2", $e->getMessage());
        }

        $this->assertEquals(3, $this->countValues());
    }

    function testInsertMaybeWithNewID() {
        $this->assertEquals(2, $this->countValues());

        $query = new RawInsertQuery(self::INSERT_ONE);
        $id = $this->querent->insertMaybeWithNewID($query);
        $this->assertEquals(3, $id);

        $this->assertEquals(3, $this->countValues());

        try {
            $query = new RawInsertQuery(self::INSERT_TWO);
            $id = $this->querent->insertMaybeWithNewID($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfInserts $e) {
            $this->assertEquals("Expected no more than one record inserted, got 2", $e->getMessage());
        }

        $this->assertEquals(3, $this->countValues());
    }
}
