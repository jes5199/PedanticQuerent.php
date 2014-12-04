<?php
namespace PedanticQuerent;

trait QueryDelegate {
    abstract function getQuery();

    function getSQL() {
        return $this->getQuery()->getSQL();
    }

    function getBindings() {
        return $this->getQuery()->getBindings();
    }
}

