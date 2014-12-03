<?php
namespace PedanticQuerent;

use \PedanticQuerent\Query;
use \PedanticQuerent\Query\DeleteQuery;
use \PedanticQuerent\Query\InsertQuery;
use \PedanticQuerent\Query\SelectQuery;
use \PedanticQuerent\Query\UpdateQuery;
use \PedanticQuerent\Query\UpsertQuery;
use \PedanticQuerent\Exception\PDOError;
use \PedanticQuerent\Exception\WrongNumberOfResults;
use \PedanticQuerent\Exception\WrongNumberOfInserts;
use \PedanticQuerent\Exception\WrongNumberOfUpdates;
use \PedanticQuerent\Exception\WrongNumberOfUpserts;
use \PedanticQuerent\Exception\WrongNumberOfDeletes;
use \PedanticQuerent\Exception\NoAutoIncrementKeyUpdated;

/*
 * Querent wraps a PDO object and enforces basic type safety on queries
 * and throws exceptions if you get a different number of results than
 * you expected.
 */
class Querent {
    private $pdo;
    private $transactionDepth;

    function __construct($pdo) {
        $this->pdo = $pdo;
        $this->transactionDepth = 0;
    }

    protected function pdo() {
        return $this->pdo;
    }

    protected function prepare($statement) {
        $pdo_statement = $this->pdo()->prepare($statement);
        $pdo_statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $pdo_statement;
    }

    protected function startTransaction() {
        if ($this->transactionDepth == 0) {
            $this->pdo()->beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL{$this->transactionDepth}");
        }
        $this->transactionDepth += 1;
    }

    protected function commitTransaction() {
        $this->transactionDepth -= 1;

        if ($this->transactionDepth == 0) {
            $this->pdo()->commit();
        } else {
            $this->exec("RELEASE LEVEL{$this->transactionDepth}");
        }
    }

    protected function rollbackTransaction() {
        $this->transactionDepth -= 1;

        if ($this->transactionDepth == 0) {
            $this->pdo()->rollBack();
        } else {
            $this->exec("ROLLBACK TO LEVEL{$this->transactionDepth}");
        }
    }

    public function inTransaction($function) {
        $this->startTransaction();
        try {
            $returnValue = $function();
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
        $this->commitTransaction();
        return $returnValue;
    }

    function executeSQL($querySQL, $bindings = array()) {
        $pdoStatement = $this->prepare($querySQL);
        try {
            if ($pdoStatement->execute($bindings)) {
                return $pdoStatement;
            } else {
                throw PDOError::createFromPDOErrorInfoAndSQL($pdoStatement->errorInfo(), $querySQL);
            }
        } catch (\PDOException $e) {
            throw PDOError::createFromPDOErrorInfoAndSQL($e->errorInfo, $querySQL);
        }
    }

    protected function executeQuery(Query $query) {
        return $this->executeSQL($query->getSQL(), $query->getBindings());
    }

    protected function executeQueryReturningResults(Query $query) {
        $pdoStatement = $this->executeQuery($query);
        return $pdoStatement->fetchAll();
    }

    protected function executeQueryAffectingRows(Query $query) {
        $pdoStatement = $this->executeQuery($query);
        return $pdoStatement->rowCount();
    }

    protected function lastInsertID() {
        return $this->pdo()->lastInsertId();
    }

    /*
     * returns 0..N rows
     */
    function selectAny(SelectQuery $query) {
        return $this->executeQueryReturningResults($query);
    }

    /*
     * returns 1..N rows
     */
    function selectSome(SelectQuery $query) {
        $results = $this->executeQueryReturningResults($query);
        WrongNumberOfResults::throwUnlessSome(count($results));
        return $results;
    }

    /*
     * returns exactly one row
     */
    function selectOne(SelectQuery $query) {
        $results = $this->executeQueryReturningResults($query);
        WrongNumberOfResults::throwUnlessOne(count($results));
        return $results[0];
    }

    /*
     * returns one row or null
     */
    function selectMaybe(SelectQuery $query) {
        $results = $this->executeQueryReturningResults($query);
        WrongNumberOfResults::throwUnlessMaybe(count($results));
        if (count($results) > 0) {
            return $results[0];
        } else {
            return null;
        }
    }

    /*
     * inserts any number of rows
     * returns count
     */
    function insertAny(InsertQuery $query) {
        $count = $this->executeQueryAffectingRows($query);
        return $count;
    }

    /*
     * inserts at least one row
     * returns count
     */
    function insertSome(InsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfInserts::throwUnlessSome($count);
            return $count;
        });
    }

    /*
     * inserts exactly one row
     */
    function insertOne(InsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfInserts::throwUnlessOne($count);
            return $count;
        });
    }

    /*
     * inserts exactly one row, returns the ID
     */
    function insertOneWithNewID(InsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfInserts::throwUnlessOne($count);
            $id = $this->lastInsertID();
            if ($id <= 0) {
                throw new NoAutoIncrementKeyUpdated($id);
            }
            return $id;
        });
    }

    /*
     * inserts one or zero rows, returns 1 or 0
     */
    function insertMaybe(InsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfInserts::throwUnlessMaybe($count);
            return $count;
        });
    }

    /*
     * inserts one or zero rows, returns the ID or null
     */
    function insertMaybeWithNewID(InsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfInserts::throwUnlessMaybe($count);
            if ($count > 0) {
                $id = $this->lastInsertID();
                if ($id <= 0) {
                    throw new NoAutoIncrementKeyUpdated($id);
                }
                return $id;
            } else {
                return null;
            }
        });
    }

    /*
     * updates any number of rows
     */
    function updateAny(UpdateQuery $query) {
        $count = $this->executeQueryAffectingRows($query);
        return $count;
    }

    /*
     * updates at least one row
     */
    function updateSome(UpdateQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpdates::throwUnlessSome($count);
            return $count;
        });
    }

    /*
     * updates exactly one row
     */
    function updateOne(UpdateQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpdates::throwUnlessOne($count);
            return $count;
        });
    }

    /*
     * updates one or more rows
     * returns the number of rows updated
     */
    function updateMaybe(UpdateQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpdates::throwUnlessMaybe($count);
            return $count;
        });
    }

    /*
     * upserts any number of rows
     */
    function upsertAny(UpsertQuery $query) {
        $count = $this->executeQueryAffectingRows($query);
        return $count;
    }

    /*
     * upserts at least one row
     */
    function upsertSome(UpsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpserts::throwUnlessSome($count);
            return $count;
        });
    }

    /*
     * upserts exactly one row
     */
    function upsertOne(UpsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpserts::throwUnlessOne($count);
            return $count;
        });
    }

    /*
     * upserts one or more rows
     * returns the number of rows upsertd
     */
    function upsertMaybe(UpsertQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfUpserts::throwUnlessMaybe($count);
            return $count;
        });
    }

    /*
     * deletes any number of rows
     */
    function deleteAny(DeleteQuery $query) {
        $count = $this->executeQueryAffectingRows($query);
        return $count;
    }

    /*
     * deletes at least one row
     */
    function deleteSome(DeleteQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfDeletes::throwUnlessSome($count);
            return $count;
        });
    }

    /*
     * deletes exactly one row
     */
    function deleteOne(DeleteQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfDeletes::throwUnlessOne($count);
            return $count;
        });
    }

    /*
     * deletes one or more rows
     * returns the number of rows deleted
     */
    function deleteMaybe(DeleteQuery $query) {
        return $this->inTransaction(function() use (&$query) {
            $count = $this->executeQueryAffectingRows($query);
            WrongNumberOfDeletes::throwUnlessMaybe($count);
            return $count;
        });
    }
}
