<?php

namespace SQL\Process;

use SQL\Process\DB;

use InvalidArgumentException;

class TF extends DB
{

    private static $db = false;

    private static function filter(string $value)
    {
        return preg_match("/^[a-zA-Z0-9_]+$/", $value);
    }

    public static function db($db = false)
    {
        if ($db !== false) {
            self::$db = $db;
        }

        return new self();
    }

    public static function createDatabase(string $name)
    {
        if (self::filter($name)) {
            $query = "CREATE DATABASE IF NOT EXISTS `$name`";
            DB::query($query);

            return true;
        } else {
            return false;
        }
    }

    public static function dropDatabase(string $name)
    {
        if (self::filter($name)) {
            $query = "DROP DATABASE IF EXISTS `$name`";
            DB::query($query);

            return true;
        } else {
            return false;
        }
    }

    private static function buildColumn(array $columns)
    {

        $values = [];

        foreach ($columns as $key => $value) {
            if (self::filter($key)) {
                $first = array_key_first($value);

                if (!is_int($first)) {
                    $query = $key . " $first";
                    $query .= "(" . $value[$first] . ")";
                } else {
                    $query = $key . " " . $value[0];
                }

                if (@$value["null"] === true) {
                    $query .= " NULL";
                } elseif (@$value["null"] === false) {
                    $query .= " NOT NULL";
                } elseif (!isset($value["null"])) {
                    $query .= "";
                }

                $query .= !isset($value["default"]) || $value["default"] === false ? "" : " DEFAULT " . $value["default"];

                if (@$value["ai"] === true) {
                    $query .= " AUTO_INCREMENT PRIMARY KEY";
                } elseif (@$value["ai"] === false) {
                    $query .= "";
                } elseif (!isset($value["ai"])) {
                    $query .= "";
                }

                $query .= !isset($value["comment"]) || $value["comment"] === false ? "" : " COMMENT " . '' . $value["comment"] . '';
                $query = "    " . $query;

                array_push($values, $query);
            } else {
                return false;
            }
        }

        $query = implode(",\n", $values);

        return $query;
    }

    public static function createTable(string $tableName, array $columns, $db = false)
    {

        if (self::filter($tableName)) {
            $unique = [];
            $foreign = [];

            foreach ($columns as $key => $value) {
                if (isset($value["unique"]) && $value["unique"] !== false) {
                    array_push($unique, $key);
                    $uniqStatus = true;
                } else {
                    $uniqStatus = false;
                }

                if (isset($value["foreign"]) && $value["foreign"] !== false) {
                    $query = "FOREIGN KEY (" . $key . ") REFERENCES " . array_key_first($value["foreign"]) . "(" . $value["foreign"][array_key_first($value["foreign"])] . ")";
                    array_push($foreign, $query);
                }
            }

            $uniq = "    " . "UNIQUE (";
            $uniq .= implode(",", $unique) . ")";

            $query = "CREATE TABLE " . $tableName . " (\n";
            $query .= self::buildColumn($columns) . "\n";

            if ($uniqStatus === true) {
                $query .= $uniq . "\n";
            }

            foreach ($foreign as $key => $value) {
                $query .= "    " . $value . "\n";
            }

            $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            DB::db($db)->query($query);

            return true;
        } else {
            return false;
        }
    }

    public static function dropTable(string $tableName, $db = false)
    {
        if (self::filter($tableName)) {
            $query = "DROP TABLE IF EXISTS `$tableName`";
            DB::db($db)->query($query);

            return true;
        } else {
            return false;
        }
    }

    public static function dropColumn(string $tableName, string $columnName, $db = false)
    {
        if (self::filter($tableName) && self::filter($columnName)) {
            $query = "ALTER TABLE " . $tableName . " DROP COLUMN IF EXISTS " . $columnName . ";";
            DB::db($db)->query($query);

            return true;
        } else {
            return false;
        }
    }

    public static function modifyColumn($tableName, array $column, $afterTable, $db = false)
    {
        if (self::filter($tableName)) {
            $query = "ALTER TABLE " . $tableName . " MODIFY COLUMN " . self::buildColumn($column) . " AFTER " . $afterTable . ";";
            DB::db($db)->query($query);

            return true;
        } else {
            return false;
        }
    }
}
