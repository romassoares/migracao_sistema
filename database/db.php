<?php

date_default_timezone_set('America/Sao_Paulo');

class DB
{
    private static $mysqli;

    public function connect($database)
    {
        if ($database == 'migracao') {
            $host = DB_HOST_MIGRACAO;
            $user = DB_USERNAME_MIGRACAO;
            $pass = DB_PASSWORD_MIGRACAO;
            $name = DB_DATABASE_MIGRACAO;
        } else {
            // $host = DB_HOST_MIGRACAO;
            // $user = DB_USERNAME_MIGRACAO;
            // $pass = DB_PASSWORD_MIGRACAO;
            // $name = DB_DATABASE_MIGRACAO;
        }

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

    public function beginTransaction($database)
    {
        $this->connect($database)->begin_transaction();
    }

    public function commit($database)
    {
        $this->connect($database)->commit();
    }

    public function rollBack($database)
    {
        $this->connect($database)->rollback();
    }

    // public function paginate($offset)
    // {
    //     $offset = $limit * ($pagina - 1);
    //     return " LIMIT " . $limit . " OFFSET " . $offset;
    // }
}
