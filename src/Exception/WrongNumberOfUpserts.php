<?php
namespace PedanticQuerent\Exception;

class WrongNumberOfUpserts extends WrongNumber {
    public static function messageFor($expected, $actual) {
        return "Expected $expected record updated/inserted, got $actual";
    }
}
