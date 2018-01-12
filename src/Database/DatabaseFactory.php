<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class DatabaseFactory
{
    public function create(string $path = 'steam.sqlite'): Connection
    {
        $connection = DriverManager::getConnection(['url' => "sqlite:///$path"]);

        $connection->exec(
            'CREATE TABLE IF NOT EXISTS app (
                id INTEGER PRIMARY KEY NOT NULL,
                name TEXT NOT NULL,
                type TEXT,
                total_reviews INTEGER,
                positive_reviews INTEGER,
                negative_reviews INTEGER,
                release_date INTEGER,
                platforms INTEGER,
                discount INTEGER,
                free INTEGER,
                players INTEGER,
                players_2w INTEGER
            );
            CREATE TABLE IF NOT EXISTS app_tag (
                app_id INTEGER NOT NULL,
                tag TEXT NOT NULL,
                `index` INTEGER NOT NULL,
                PRIMARY KEY(app_id, tag, `index`)
            );
            CREATE TABLE IF NOT EXISTS patron_review (
                app_id INTEGER NOT NULL,
                profile_id TEXT NOT NULL,
                positive INTEGER NOT NULL,
                PRIMARY KEY(app_id, profile_id)
            );
            CREATE TABLE IF NOT EXISTS steam_profile (
                profile_id TEXT PRIMARY KEY NOT NULL,
                avatar_url TEXT NOT NULL
            );
        ');

        return $connection;
    }
}
