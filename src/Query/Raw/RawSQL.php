<?php
namespace PedanticQuerent\Query\Raw;

abstract class RawSQL {
    private $rawSQL;
    private $bindings;

    function __construct($rawSQL) {
        $this->rawSQL = $rawSQL;
        $this->bindings = array();
    }

    function bind($var, $value) {
        $this->bindings[$var] = $value;
        return $this;
    }

    function getSQL() {
        return $this->rawSQL;
    }

    function getBindings() {
        return $this->bindings;
    }
}
