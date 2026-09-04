<?php

class Database
{
    private static ?mysqli $instance = null;

    public static function connect(): mysqli
    {
        if (self::$instance === null) {
            $config = config('Database')->default;

            $conn = new mysqli(
                $config['hostname'],
                $config['username'],
                $config['password'],
                $config['database'],
                (int) $config['port']
            );

            if ($conn->connect_error) {
                error_log('DB Connection error: ' . $conn->connect_error);
                http_response_code(500);
                die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
            }

            $conn->set_charset($config['charset']);
            self::$instance = $conn;
        }

        return self::$instance;
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
