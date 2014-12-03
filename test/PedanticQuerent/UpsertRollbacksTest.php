<?php
namespace PedanticQuerent;

use \PedanticQuerent\Exception\WrongNumberOfUpserts;
use \PedanticQuerent\Query\Raw\RawUpsertQuery;
use \PDO;

class UpsertRollbacksTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

    const UPSERT_ONE = "INSERT OR REPLACE INTO uniq VALUES (3)";
    const UPSERT_TWO = "INSERT OR REPLACE INTO uniq SELECT value - 1 FROM uniq WHERE value <= 2";

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

    public function values() {
        return array_map(
            function($row) { return $row[0]; },
            self::$pdo->query("SELECT value FROM uniq")->fetchAll()
        );
    }

    public function testUpsertOne() {
        $this->assertEquals([1, 2], $this->values());

        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $ok = $this->querent->upsertOne($query);
        $this->assertEquals(1, $ok);
        $this->assertEquals([1, 2, 3], $this->values());

        try {
            $query = new RawUpsertQuery(self::UPSERT_TWO);
            $count = $this->querent->upsertOne($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected exactly one record updated/inserted, got 2", $e->getMessage());
        }

        $this->assertEquals([1, 2, 3], $this->values());
    }

    function testUpsertMaybe() {
        $this->assertEquals([1, 2], $this->values());

        $query = new RawUpsertQuery(self::UPSERT_ONE);
        $count = $this->querent->upsertMaybe($query);
        $this->assertEquals(1, $count);

        $this->assertEquals([1, 2, 3], $this->values());

        try {
            $query = new RawUpsertQuery(self::UPSERT_TWO);
            $count = $this->querent->upsertMaybe($query);
            $this->fail("Exception expected");
        } catch (WrongNumberOfUpserts $e) {
            $this->assertEquals("Expected no more than one record updated/inserted, got 2", $e->getMessage());
        }

        $this->assertEquals([1, 2, 3], $this->values());
    }
}
