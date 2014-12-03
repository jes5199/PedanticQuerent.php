<?php
namespace PedanticQuerent\Exception;

class WrongNumberOfInserts extends WrongNumber {
    public static function messageFor($expected, $actual) {
        return "Expected $expected record inserted, got $actual";
    }
}
