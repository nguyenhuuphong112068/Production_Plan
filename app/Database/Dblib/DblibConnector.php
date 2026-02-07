<?php

namespace App\Database\Dblib;

use PDO;
use Illuminate\Database\Connectors\Connector;

class DblibConnector extends Connector
{
    public function connect(array $config)
    {
        $dsn = sprintf(
            'dblib:host=%s:%s;dbname=%s',
            $config['host'],
            $config['port'] ?? 1433,
            $config['database']
        );

        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'] ?? []
        );

        // Fix charset
        $pdo->exec("SET NAMES 'UTF8'");

        return $pdo;
    }
}
