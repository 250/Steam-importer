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
                total_reviews INTEGER NOT NULL,
                positive_reviews INTEGER NOT NULL,
                negative_reviews INTEGER NOT NULL,
                app_type TEXT,
                release_date INTEGER,
                genre TEXT
            );
            CREATE TABLE IF NOT EXISTS rank (
                id INTEGER NOT NULL,
                algorithm TEXT NOT NULL,
                rank INTEGER NOT NULL,
                PRIMARY KEY(id, algorithm)
            );'
        );

        return $connection;
    }
}
