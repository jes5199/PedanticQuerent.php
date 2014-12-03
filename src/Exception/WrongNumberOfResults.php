<?php
namespace PedanticQuerent\Exception;

class WrongNumberOfResults extends WrongNumber {
    public static function messageFor($expected, $actual) {
        return "Expected $expected record returned, got $actual";
    }
}
