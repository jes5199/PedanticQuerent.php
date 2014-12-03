<?php
namespace PedanticQuerent\Exception;

use \Exception;

class PDOError extends Exception {
    public static function messageFromPDOErrorInfo($errorInfo) {
        $message = $errorInfo[0] . "-" . $errorInfo[1] . ": " . $errorInfo[2];
        return $message;
    }

    public static function createFromPDOErrorInfo($errorInfo) {
        $message = static::messageFromPDOErrorInfo($errorInfo);
        return new static($message);
    }

    public static function createFromPDOErrorInfoAndSQL($errorInfo, $sql) {
        $message = static::messageFromPDOErrorInfo($errorInfo) . " [$sql]";
        return new static($message);
    }
}
