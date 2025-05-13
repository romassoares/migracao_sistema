<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../vars.php';

class DB
{
    private static $mysqli;

    public function connect($database)
    {
        $db_vars = db_vars($database);

        if (!isset($db_vars)) {
            throw new Exception("Database '$database' not found.");
        }

        $config = $db_vars;
        $host = $config['host'];
        $user = $config['user'];
        $pass = $config['pass'];
        $name = $config['name'];

        try {
            if (is_null(self::$mysqli)) {
                self::$mysqli = new mysqli($host, $user, $pass, $name);
                if (self::$mysqli->connect_errno) {
                    throw new Exception("Failed to connect to MySQL: " . self::$mysqli->connect_error);
                }
            } else {
                self::$mysqli->close();
                self::$mysqli = new mysqli($host, $user, $pass, $name);
                if (self::$mysqli->connect_errno) {
                    throw new Exception("Failed to connect to MySQL: " . self::$mysqli->connect_error);
                }
            }
            self::$mysqli->query("SET NAMES 'utf8'");
            return self::$mysqli;
        } catch (mysqli_sql_exception $e) {
            throw new Exception('Database Connection Error: ' . $e->getMessage());
        }
    }

    public static function beginTransaction()
    {
        self::$mysqli->begin_transaction();
    }

    public static function commit()
    {
        self::$mysqli->commit();
    }

    public static function rollBack()
    {
        self::$mysqli->rollback();
    }
}
