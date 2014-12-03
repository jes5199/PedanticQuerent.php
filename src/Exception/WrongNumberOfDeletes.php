<?php
namespace PedanticQuerent\Exception;

class WrongNumberOfDeletes extends WrongNumber {
    public static function messageFor($expected, $actual) {
        return "Expected $expected record deleted, got $actual";
    }
}
