<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfUpdates;
use \PedanticQuerent\Query\Raw\RawUpdateQuery;
use \PDO;

class UpdateTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const UPDATE_ZERO = "UPDATE test SET value = 0 WHERE 0";
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

    function testUpdateOne() {
        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $ok = $this->querent->updateOne($query);
        $this->assertEquals(1, $ok);

        try {
            $query = new RawUpdateQuery(self::UPDATE_ZERO);
            $count = $this->querent->updateOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected exactly one record updated, got 0", $e->getMessage());
        }

        try {
            $query = new RawUpdateQuery(self::UPDATE_TWO);
            $count = $this->querent->updateOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected exactly one record updated, got 2", $e->getMessage());
        }
    }

    function testUpdateMaybe() {
        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $count = $this->querent->updateMaybe($query);
        $this->assertEquals(1, $count);

        $query = new RawUpdateQuery(self::UPDATE_ZERO);
        $count = $this->querent->updateMaybe($query);
        $this->assertEquals(0, $count);

        try {
            $query = new RawUpdateQuery(self::UPDATE_TWO);
            $count = $this->querent->updateMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected no more than one record updated, got 2", $e->getMessage());
        }
    }

    function testUpdateSome() {
        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $count = $this->querent->updateSome($query);
        $this->assertEquals(1, $count);

        try {
            $query = new RawUpdateQuery(self::UPDATE_ZERO);
            $count = $this->querent->updateSome($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpdates $e) {
            $this->assertEquals("Expected at least one record updated, got 0", $e->getMessage());
        }

        $query = new RawUpdateQuery(self::UPDATE_TWO);
        $count = $this->querent->updateSome($query);
        $this->assertEquals(2, $count);
    }

    function testUpdateAny() {
        $query = new RawUpdateQuery(self::UPDATE_ONE);
        $count = $this->querent->updateAny($query);
        $this->assertEquals(1, $count);

        $query = new RawUpdateQuery(self::UPDATE_ZERO);
        $count = $this->querent->updateAny($query);
        $this->assertEquals(0, $count);

        $query = new RawUpdateQuery(self::UPDATE_TWO);
        $count = $this->querent->updateAny($query);
        $this->assertEquals(2, $count);
    }
}
