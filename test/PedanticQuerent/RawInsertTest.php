<?php
namespace PedanticQuerent;

use \PedanticQuerent\Query\Raw\RawInsertQuery;
use \PDO;

class RawInsertTest extends \PHPUnit_Framework_TestCase {
    protected static $pdo;
    protected $querent;

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

    function testRawInsertWithBindingSpecifiedInConstructor() {
        $query = new RawInsertQuery("INSERT INTO test VALUES (:one)", [":one" => "1"]);
        $ok = $this->querent->insertOne($query);
        $this->assertEquals([1,2,1], $this->values());
    }

    function testRawInsertWithBindingSpecifiedByMethod() {
        $query = new RawInsertQuery("INSERT INTO test VALUES (:two)");
        $query->bind(":two", 2);
        $ok = $this->querent->insertOne($query);
        $this->assertEquals(1, $ok);
        $this->assertEquals([1,2,2], $this->values());
    }
}

