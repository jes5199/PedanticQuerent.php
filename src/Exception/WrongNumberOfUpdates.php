<?php
namespace PedanticQuerent\Exception;

class WrongNumberOfUpdates extends WrongNumber {
    public static function messageFor($expected, $actual) {
        return "Expected $expected record updated, got $actual";
    }
}
