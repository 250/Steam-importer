<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class DatabaseFactory
{
    public function create(string $path): Connection
    {
        $connection = DriverManager::getConnection(['url' => "sqlite:///$path"]);

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS app (
                id INTEGER PRIMARY KEY NOT NULL,
                app_name TEXT NOT NULL,
                total_reviews INTEGER,
                positive_reviews INTEGER,
                negative_reviews INTEGER,
                app_type TEXT,
                release_date INTEGER,
                genre TEXT
            );'
        );

        return $connection;
    }
}
