<?php

namespace App\Core;

use PDO;
use PDOException;

abstract class Model {
    protected static function getDB() {
        static $db = null;

        if ($db === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
                $db = new PDO($dsn, DB_USER, DB_PASS);

                // Throw an Exception when an error occurs
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
        }
        return $db;
    }
}
