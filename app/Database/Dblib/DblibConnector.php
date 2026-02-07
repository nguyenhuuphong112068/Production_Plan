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

        return new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
    }
}
