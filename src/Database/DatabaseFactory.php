<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

final class DatabaseFactory
{
    public function create(string $path = 'steam.sqlite', bool $overwrite = false): Connection
    {
        // Truncate existing file. unlink() always fails.
        $overwrite && is_writable($path) && file_put_contents($path, '');

        $connection = DriverManager::getConnection(['url' => "sqlite:///$path"]);

        // NB: Any tables added or removed should probably also be reflected in the Stitcher.
        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS app (
                id INTEGER PRIMARY KEY NOT NULL,
                name TEXT NOT NULL,
                type TEXT,
                total_reviews INTEGER,
                positive_reviews INTEGER,
                negative_reviews INTEGER,
                steam_reviews INTEGER,
                release_date INTEGER,
                platforms INTEGER,
                price INTEGER,
                discount_price INTEGER,
                discount INTEGER,
                vrx INTEGER,
                free INTEGER,
                ea INTEGER,
                adult INTEGER,
                videos TEXT,
                video_manifest_hashes TEXT,
                steam_deck INTEGER,
                owners INTEGER,
                parent_id INTEGER,
                alias INTEGER,
                demo_id INTEGER,
                capsule_hash TEXT,
                capsule_alt INTEGER
            );
            CREATE TABLE IF NOT EXISTS tag (
                id INTEGER PRIMARY KEY NOT NULL,
                name TEXT NOT NULL,
                category TEXT
            );
            CREATE TABLE IF NOT EXISTS tag_cat (
                id INTEGER PRIMARY KEY NOT NULL,
                name TEXT NOT NULL,
                short_name TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS app_tag (
                app_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                votes INTEGER NOT NULL,
                PRIMARY KEY(app_id, tag_id)
            );
            CREATE TABLE IF NOT EXISTS app_developer (
                app_id INTEGER NOT NULL,
                id INTEGER,
                name TEXT NOT NULL,
                PRIMARY KEY(app_id, name)
            );
            CREATE TABLE IF NOT EXISTS app_publisher (
                app_id INTEGER NOT NULL,
                id INTEGER,
                name TEXT NOT NULL,
                PRIMARY KEY(app_id, name)
            );
            CREATE TABLE IF NOT EXISTS app_players (
                app_id INTEGER PRIMARY KEY NOT NULL,
                average_players_7d INTEGER NOT NULL
            );
            CREATE TABLE IF NOT EXISTS c250_ranking (
                list_id TEXT NOT NULL,
                rank INTEGER NOT NULL,
                app_id INTEGER NOT NULL,
                PRIMARY KEY(list_id, rank)
            );'
        );

        return $connection;
    }
}
