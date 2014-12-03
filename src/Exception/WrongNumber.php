<?php
namespace PedanticQuerent\Exception;

use \Exception;

class WrongNumber extends Exception {
    function __construct($expected, $actual) {
        parent::__construct(static::messageFor($expected, $actual));
    }

    public static function messageFor($expected, $actual) {
        return "Expected $expected, got $actual";
    }

    public static function expectedOne($actual) {
        return new static("exactly one", $actual);
    }

    public static function expectedMaybe($actual) {
        return new static("no more than one", $actual);
    }

    public static function expectedSome($actual) {
        return new static("at least one", $actual);
    }

    public static function throwUnlessOne($actual) {
        if ($actual != 1) {
            throw static::expectedOne($actual);
        }
    }

    public static function throwUnlessMaybe($actual) {
        if ($actual > 1 || $actual < 0) {
            throw static::expectedMaybe($actual);
        }
    }

    public static function throwUnlessSome($actual) {
        if ($actual <= 0) {
            throw static::expectedSome($actual);
        }
    }
}
