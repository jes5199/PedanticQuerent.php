<?php
namespace PedanticQuerent;

use \PedanticQuerent\Query\SelectQuery;
use \PedanticQuerent\Query\InsertQuery;
use \PedanticQuerent\Query\UpdateQuery;
use \PedanticQuerent\Query\DeleteQuery;
use \PedanticQuerent\Query\UpsertQuery;

/*
 * If you have an object that you want to act as a Querant
 * (e.g., it would be convenient to do something like
 *  $database->selectOne($query) for some object of yours)
 *  Then give it this trait.
 *  The only requirement is that it has a function pdo()
 *  that returns a working database connection.
 */
trait QuerentDelegate {
    private $querent;

    abstract function pdo();

    function querent() {
        if (!$this->querent) {
            $this->querent = new Querent($this->pdo());
        }
        return $this->querent;
    }

    public function inTransaction($function) {
        return $this->querent()->inTransaction($function);
    }

    // I don't really want this to be public but I can't quite get rid of it yet.
    function executeSQL($query, $args) {
        return $this->querent()->executeSQL($query, $args);
    }

    function selectAny(SelectQuery $query) {
        return $this->querent()->selectAny($query);
    }

    function selectSome(SelectQuery $query) {
        return $this->querent()->selectSome($query);
    }

    function selectOne(SelectQuery $query) {
        return $this->querent()->selectOne($query);
    }

    function selectMaybe(SelectQuery $query) {
        return $this->querent()->selectMaybe($query);
    }

    function insertAny(InsertQuery $query) {
        return $this->querent()->insertAny($query);
    }

    function insertSome(InsertQuery $query) {
        return $this->querent()->insertSome($query);
    }

    function insertOne(InsertQuery $query) {
        return $this->querent()->insertOne($query);
    }

    function insertOneWithNewID(InsertQuery $query) {
        return $this->querent()->insertOneWithNewID($query);
    }

    function insertMaybe(InsertQuery $query) {
        return $this->querent()->insertMaybe($query);
    }

    function insertMaybeWithNewID(InsertQuery $query) {
        return $this->querent()->insertMaybeWithNewID($query);
    }

    function updateAny(UpdateQuery $query) {
        return $this->querent()->updateAny($query);
    }

    function updateSome(UpdateQuery $query) {
        return $this->querent()->updateSome($query);
    }

    function updateOne(UpdateQuery $query) {
        return $this->querent()->updateOne($query);
    }

    function updateMaybe(UpdateQuery $query) {
        return $this->querent()->updateMaybe($query);
    }

    function upsertAny(UpsertQuery $query) {
        return $this->querent()->upsertAny($query);
    }

    function upsertSome(UpsertQuery $query) {
        return $this->querent()->upsertSome($query);
    }

    function upsertOne(UpsertQuery $query) {
        return $this->querent()->upsertOne($query);
    }

    function upsertMaybe(UpsertQuery $query) {
        return $this->querent()->upsertMaybe($query);
    }

    function deleteAny(DeleteQuery $query) {
        return $this->querent()->deleteAny($query);
    }

    function deleteSome(DeleteQuery $query) {
        return $this->querent()->deleteSome($query);
    }

    function deleteOne(DeleteQuery $query) {
        return $this->querent()->deleteOne($query);
    }

    function deleteMaybe(DeleteQuery $query) {
        return $this->querent()->deleteMaybe($query);
    }
}
