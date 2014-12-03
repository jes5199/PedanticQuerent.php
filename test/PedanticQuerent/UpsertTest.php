<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfUpserts;
use \PedanticQuerent\Query\Raw\RawUpsertQuery;
use \PDO;

class UpsertTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const UPSERT_ZERO = "INSERT OR REPLACE INTO uniq SELECT value FROM uniq WHERE 0";
    const UPSERT_ONE = "INSERT OR REPLACE INTO uniq VALUES (1)";
    const UPSERT_TWO = "INSERT OR REPLACE INTO uniq SELECT value + 1 FROM uniq";

    public static function setUpBeforeClass() {
        self::$pdo = new PDO("sqlite:/tmp/querent.sqlite");
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS uniq (value integer)");
        self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS unique_value ON uniq (value)");
    }

    public function setUp() {
        $this->querent = new Querent(self::$pdo);
        self::$pdo->exec("DELETE FROM uniq");
        self::$pdo->exec("INSERT INTO uniq VALUES (1)");
        self::$pdo->exec("INSERT INTO uniq VALUES (2)");
    }

    function testUpsertOne() {
        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $ok = $this->querent->upsertOne($query);
        $this->assertEquals(1, $ok);

        try {
            $query = new RawUpsertQuery(self::UPSERT_ZERO);
            $count = $this->querent->upsertOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected exactly one record updated/inserted, got 0", $e->getMessage());
        }

        try {
            $query = new RawUpsertQuery(self::UPSERT_TWO);
            $count = $this->querent->upsertOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected exactly one record updated/inserted, got 2", $e->getMessage());
        }
    }

    function testUpsertMaybe() {
        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $count = $this->querent->upsertMaybe($query);
        $this->assertEquals(1, $count);

        $query = new RawUpsertQuery(self::UPSERT_ZERO);
        $count = $this->querent->upsertMaybe($query);
        $this->assertEquals(0, $count);

        try {
            $query = new RawUpsertQuery(self::UPSERT_TWO);
            $count = $this->querent->upsertMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected no more than one record updated/inserted, got 2", $e->getMessage());
        }
    }

    function testUpsertSome() {
        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $count = $this->querent->upsertSome($query);
        $this->assertEquals(1, $count);

        try {
            $query = new RawUpsertQuery(self::UPSERT_ZERO);
            $count = $this->querent->upsertSome($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected at least one record updated/inserted, got 0", $e->getMessage());
        }

        $query = new RawUpsertQuery(self::UPSERT_TWO);
        $count = $this->querent->upsertSome($query);
        $this->assertEquals(2, $count);
    }

    function testUpsertAny() {
        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $count = $this->querent->upsertAny($query);
        $this->assertEquals(1, $count);

        $query = new RawUpsertQuery(self::UPSERT_ZERO);
        $count = $this->querent->upsertAny($query);
        $this->assertEquals(0, $count);

        $query = new RawUpsertQuery(self::UPSERT_TWO);
        $count = $this->querent->upsertAny($query);
        $this->assertEquals(2, $count);
    }
}
