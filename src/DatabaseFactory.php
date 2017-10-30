<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class DatabaseFactory
{
    public function create(): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///db/games.sqlite']);

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS review (
                id INTEGER PRIMARY KEY,
                game_name TEXT,
                total_reviews INTEGER,
                positive_reviews INTEGER,
                negative_reviews INTEGER
            );'
        );

        return $connection;
    }
}
