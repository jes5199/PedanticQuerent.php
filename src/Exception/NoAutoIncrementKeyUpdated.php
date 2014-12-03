<?php
namespace PedanticQuerent\Exception;

use \Exception;

class NoAutoIncrementKeyUpdated extends Exception {
    function __construct($badID) {
        parent::__construct(static::messageFor($badID));
    }

    public static function messageFor($badID) {
        return("Expected an auto-incremented ID, got invalid value " . $badID);
    }
}
