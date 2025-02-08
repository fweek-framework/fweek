<?php

namespace SQL\Process;

use PDO;
use PDOException;
use HTTP\Request\Management;
use Logger;
use Request;

define("DB_NAME", request::env("DB_NAME"));

class DB
{
    private static $pdo;
    private static $query;
    private static $paramNames = [];
    private static $queryParameters;
    private static $connection;
    private static $db;
    private static $dbName = DB_NAME;

    public function __construct()
    {
        if (self::$connection !== true) {
            self::$connection = true;
            self::connect();
        }
    }

    public static function db($db = false)
    {
        if ($db !== false) {
            self::$connection = false;
            self::$dbName = $db;
            self::connect();
        }

        return new self();
    }

    private static function connect()
    {
        try {
            $dsn = "mysql:host=" . request::env("DB_HOST") . ";dbname=" . self::$dbName . ";charset=" . request::env("DB_CHARSET") . "";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            self::$pdo = new PDO($dsn, request::env("DB_USER"), request::env("DB_PASSWORD"), $options);
        } catch (PDOException $e) {
            Logger::error('Connection failed: ' . $e->getMessage(), "DATABASE");
        }
    }

    public static function query($query, array $params = [])
    {
        if ($params !== []) {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();
        }

        return $stmt;
    }

    public static function multiQuery(array $query): array
    {
        $returnArray = [];

        foreach ($query as $key => $value) {

            if (!is_int($key)) {
                $stmt = self::$pdo->prepare($key);
                $stmt->execute($value);
            } else {
                $stmt = self::$pdo->prepare($value);
                $stmt->execute();
            }

            array_push($returnArray, $stmt);
        }

        return $returnArray;
    }

    public static function lastId()
    {
        return self::$pdo->lastInsertId();
    }

    public static function insert(string $table)
    {
        self::$query = "INSERT INTO " . $table . "";
        return new self();
    }

    public static function replace(string $table)
    {
        self::$query = "REPLACE INTO " . $table . "";
        return new self();
    }

    public static function update(string $table)
    {
        self::$query = "UPDATE " . $table . "";
        return new self();
    }

    public function data(array $data)
    {
        $sqlData = [];
        $paramNames = [];

        foreach ($data as $key => $value) {
            if (management::sqlSanitizer($key) === false) {
                return false;
            }

            $paramName = "value" . bin2hex(random_bytes(8));
            $paramNames[$paramName] = $value;
            array_push($sqlData, $key . " = :" . $paramName);
        }

        array_push(self::$paramNames, $paramNames);
        $queryParameters = implode(", ", $sqlData);

        self::$query .= " SET " . $queryParameters . "";

        return $this;
    }

    public static function delete(string $table)
    {
        self::$query = "DELETE FROM " . $table . "";
        return new self();
    }

    public static function select(array|string $column)
    {
        self::$query .= "SELECT ";

        if (is_array($column)) {
            foreach ($column as $key => $value) {
                if (is_int($key)) {
                    if (management::sqlSanitizer($value) === false) {
                        return false;
                    }

                    if (array_key_last($column) === $key) {
                        self::$query .= "" . $value . "";
                    } else {
                        self::$query .= "" . $value . ", ";
                    }
                } else {
                    if (management::sqlSanitizer($key) === false || management::sqlSanitizer($value) === false) {
                        return false;
                    }

                    if (array_key_last($column) === $key) {
                        self::$query .= "" . $key . "." . $value . "";
                    } else {
                        self::$query .= "" . $key . "." . $value . ", ";
                    }
                }
            }
        } else {
            self::$query .= $column;
        }

        return new self();
    }

    public function from(string $table)
    {
        if (management::sqlSanitizer($table) === false) {
            return false;
        }

        self::$query .= " FROM " . $table;
        return $this;
    }

    public function where(array $conditions)
    {
        self::$query .=  " WHERE " . self::parameters($conditions);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC')
    {
        $direction = strtoupper($direction);

        if ($direction != "ASC" && $direction != "DESC") {
            return false;
        } elseif (management::sqlSanitizer($column) === false) {
            return false;
        } else {
            self::$query .= " ORDER BY $column $direction";
        }

        return $this;
    }

    public function limit(int $offset, int $limit)
    {
        if ((!is_int($offset) || $offset < 0) || (!is_int($limit) || $limit < 0)) {
            return false;
        } else {
            self::$query .= " LIMIT " . $offset . ", " . $limit . "";
        }

        return $this;
    }

    public function groupBy(string $name)
    {
        if (management::sqlSanitizer($name) === false) {
            return false;
        }

        self::$query .= " GROUP BY " . $name . "";
        return $this;
    }

    public function having(array $conditions)
    {
        self::$query .=  " HAVING " . self::parameters($conditions);
        return $this;
    }

    public function innerJoin(string $tableName)
    {
        if (management::sqlSanitizer($tableName) === false) {
            return false;
        }

        self::$query .= " INNER JOIN " . $tableName;
        return $this;
    }

    public function leftJoin(string $tableName)
    {
        if (management::sqlSanitizer($tableName) === false) {
            return false;
        }

        self::$query .= " LEFT JOIN " . $tableName;
        return $this;
    }

    public function rightJoin(string $tableName)
    {
        if (management::sqlSanitizer($tableName) === false) {
            return false;
        }

        self::$query .= " RIGHT JOIN " . $tableName;
        return $this;
    }

    public function on(array $conditions)
    {
        self::$query .=  " ON " . self::parameters($conditions);;
        return $this;
    }

    public function union()
    {
        self::$query .= " UNION ";
        return $this;
    }

    public function unionAll()
    {
        self::$query .= " UNION ALL ";
        return $this;
    }

    public static function parameters(array $conditions)
    {
        $paramNames = [];
        $whereData = [];

        foreach ($conditions as $key => $value) {
            if (management::sqlSanitizer($key) === false) {
                return false;
            }

            if ($value[0] === "null" || $value[0] === "not-null") {
                if (array_key_last($conditions) == $key) {
                    $value[1] = "";
                } elseif (!isset($value[2])) {
                    $value[1] = "AND";
                }

                if (!empty($value[1])) {
                    $value[1] = " " . $value[1] . " ";
                }
            }

            if ($value[0] === "null") {
                array_push($whereData, $key . " IS NULL" . $value[1]);
            } elseif ($value[0] === "not-null") {
                array_push($whereData, $key . " IS NOT NULL" . $value[1]);
            } else {
                if (array_key_last($conditions) == $key) {
                    $value[2] = "";
                } elseif (!isset($value[2])) {
                    $value[2] = "AND";
                }

                if (!empty($value[2])) {
                    $value[2] = " " . $value[2] . " ";
                }

                if ($value[0] === "between") {
                    $firstParamName = "first" .  bin2hex(random_bytes(8));
                    $secondParamName = "second" .  bin2hex(random_bytes(8));

                    $paramNames[$firstParamName] = array_key_first($value[1]);
                    $paramNames[$secondParamName] = $value[1][array_key_first($value[1])];

                    array_push($whereData, $key . " BETWEEN :" . $firstParamName . " AND :" . $secondParamName . "" . $value[2]);
                } else {
                    $paramName = "value" .  bin2hex(random_bytes(8));
                    $paramNames[$paramName] = $value[1];
                    array_push($whereData, $key . " " . $value[0] . " :" . $paramName . "" . $value[2] . "");
                }
            }
        }

        array_push(self::$paramNames, $paramNames);

        $whereParameters = implode("", $whereData);

        return $whereParameters;
    }

    public static function pseudo(string $name, string $pseudoName)
    {
        if (management::sqlSanitizer($pseudoName) === false || management::sqlSanitizer($name) === false) {
            return false;
        }

        return $name . " AS " . $pseudoName . "";
    }

    public static function sum(string $name, $pseudoName = false)
    {
        if (management::sqlSanitizer($name) === false || (management::sqlSanitizer($pseudoName) === false && $pseudoName !== false)) {
            return false;
        }

        if ($pseudoName === false) {
            return "SUM($name)";
        } else {
            return "SUM($name) AS " . $pseudoName . "";
        }
    }

    public static function avg(string $name, string $pseudoName)
    {
        if (management::sqlSanitizer($name) === false || (management::sqlSanitizer($pseudoName) === false && $pseudoName !== false)) {
            return false;
        }

        if ($pseudoName === false) {
            return "AVG($name)";
        } else {
            return "AVG($name) AS " . $pseudoName . "";
        }
    }

    public static function count(string $name, string $pseudoName)
    {
        if (management::sqlSanitizer($name) === false || (management::sqlSanitizer($pseudoName) === false && $pseudoName !== false)) {
            return false;
        }

        if ($pseudoName === false) {
            return "COUNT($name)";
        } else {
            return "COUNT($name) AS " . $pseudoName . "";
        }
    }

    public static function min(string $name, string $pseudoName)
    {
        if (management::sqlSanitizer($name) === false || (management::sqlSanitizer($pseudoName) === false && $pseudoName !== false)) {
            return false;
        }

        if ($pseudoName === false) {
            return "MIN($name)";
        } else {
            return "MIN($name) AS " . $pseudoName . "";
        }
    }

    public static function max(string $name, string $pseudoName)
    {
        if (management::sqlSanitizer($name) === false || (management::sqlSanitizer($pseudoName) === false && $pseudoName !== false)) {
            return false;
        }

        if ($pseudoName === false) {
            return "MAX($name)";
        } else {
            return "MAX($name) AS " . $pseudoName . "";
        }
    }

    public function run()
    {
        $stmt = self::$pdo->prepare(self::$query);

        if (!empty(self::$paramNames)) {
            foreach (self::$paramNames as $key => $value) {
                foreach ($value as $name => $val) {
                    $stmt->bindValue(":$name", $val);
                }
            }
        }

        self::$paramNames = [];
        self::$query = null;

        $stmt->execute();

        return $stmt;
    }

    public function write()
    {
        print_r(self::$paramNames);

        $query = self::$query;

        self::$paramNames = [];
        self::$query = null;

        return $query;
    }

    public function getResult($fetch = PDO::FETCH_ASSOC)
    {
        $stmt = self::$pdo->prepare(self::$query);

        if (!empty(self::$paramNames)) {
            foreach (self::$paramNames as $key => $value) {
                foreach ($value as $name => $val) {
                    $stmt->bindValue(":$name", $val);
                }
            }
        }

        self::$paramNames = [];
        self::$query = null;

        $stmt->execute();

        return $stmt->fetch($fetch);
    }

    public function getAllResults($fetch = PDO::FETCH_ASSOC)
    {
        $stmt = self::$pdo->prepare(self::$query);

        if (!empty(self::$paramNames)) {
            foreach (self::$paramNames as $key => $value) {
                foreach ($value as $name => $val) {
                    $stmt->bindValue(":$name", $val);
                }
            }
        }

        self::$paramNames = [];
        self::$query = null;

        $stmt->execute();

        return $stmt->fetchAll($fetch);
    }

    public function rowCount()
    {
        $stmt = self::$pdo->prepare(self::$query);

        if (!empty(self::$paramNames)) {
            foreach (self::$paramNames as $key => $value) {
                foreach ($value as $name => $val) {
                    $stmt->bindValue(":$name", $val);
                }
            }
        }

        self::$paramNames = [];
        self::$query = null;

        $stmt->execute();

        return $stmt->rowCount();
    }
}
