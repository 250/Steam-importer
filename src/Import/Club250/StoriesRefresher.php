<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use Psr\Log\LoggerInterface;
use ScriptFUSION\Steam250\Storage\Storage\ReadWriteStorage;
use ScriptFUSION\Steam250\Storage\Storage\StorageRoot;

/**
 * Refreshes Club 250 stories in an already-built database snapshot without running a full import.
 *
 * Pulls a database from storage, replaces its stories with the latest from Club 250 and pushes it back,
 * replacing the original snapshot in place.
 */
final class StoriesRefresher
{
    private const DATABASE_FILENAME = 'steam.sqlite';

    public function __construct(
        private readonly ReadWriteStorage $storage,
        private readonly StoriesImporterFactory $stories,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $apiToken Club 250 API token.
     * @param string|null $snapshot Optional. Storage filespec of the snapshot to refresh, e.g.
     *     "202609/24/A3100/steam.sqlite". Defaults to the latest snapshot.
     */
    public function refresh(string $apiToken, ?string $snapshot = null): void
    {
        $workDir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'stories-refresh-' . getmypid() . '-' . bin2hex(random_bytes(4));

        if (!mkdir($workDir) && !is_dir($workDir)) {
            throw new \RuntimeException("Failed to create working directory: \"$workDir\".");
        }

        $cwd = getcwd();

        try {
            chdir($workDir);

            $vdir = $snapshot === null
                ? $this->downloadLatestSnapshot()
                : $this->downloadSnapshot($snapshot);

            $this->logger->info("Refreshing stories in \"$vdir\".");

            $importer = $this->stories->create(self::DATABASE_FILENAME);
            $importer->import($apiToken);
            // Release the SQLite lock before reading the file back for upload.
            unset($importer);

            $this->storage->upload(self::DATABASE_FILENAME, $vdir);
            $this->storage->moveUploadedFile("$vdir/" . self::DATABASE_FILENAME);

            $this->logger->info('All done :^)');
        } finally {
            $cwd !== false && chdir($cwd);

            array_map(unlink(...), glob("$workDir/*") ?: []);
            rmdir($workDir);
        }
    }

    /**
     * Downloads the specified snapshot from the read directory.
     *
     * @return string Parent directory (vdir) of the snapshot, for uploading back to.
     */
    private function downloadSnapshot(string $snapshot): string
    {
        if (!str_contains($snapshot, '/')) {
            throw new \InvalidArgumentException(
                'Snapshot must be a storage filespec including its directory, e.g. "202609/24/A3100/steam.sqlite".'
            );
        }

        $this->storage->download($snapshot, StorageRoot::READ_DIR);

        // Storage preserves the remote file name locally. Cope if it is not the expected name.
        if (!is_file(self::DATABASE_FILENAME)) {
            $matches = glob('*.sqlite') ?: [];

            if (\count($matches) !== 1) {
                throw new \RuntimeException('Downloaded database not found.');
            }

            rename($matches[0], self::DATABASE_FILENAME);
        }

        return \dirname(str_replace('\\', '/', $snapshot));
    }

    /**
     * Downloads the latest snapshot from the read directory.
     *
     * @return string Parent directory (vdir) of the snapshot, for uploading back to.
     */
    private function downloadLatestSnapshot(): string
    {
        $this->storage->downloadLastTwoSnapshots();

        $files = glob('*.steam.sqlite') ?: [];
        $latest = self::pickLatestSnapshot($files);

        if ($latest === null) {
            throw new \RuntimeException('No database snapshots found.');
        }

        foreach ($files as $file) {
            if ($file !== $latest[3]) {
                unlink($file);
            }
        }

        rename($latest[3], self::DATABASE_FILENAME);

        return "$latest[0]/$latest[1]/$latest[2]";
    }

    /**
     * Picks the latest snapshot from a list of files downloaded by downloadLastTwoSnapshots().
     * Filenames encode their remote location: "YYYYMM_DD_BUILD.steam.sqlite".
     *
     * @param string[] $files
     *
     * @return array{string, string, string, string}|null [yearMonth, day, build, file].
     */
    private static function pickLatestSnapshot(array $files): ?array
    {
        $latest = null;

        foreach ($files as $file) {
            if (!preg_match('~^(\d+)_(\d+)_([^.]+)\.steam\.sqlite$~', basename($file), $matches)) {
                continue;
            }

            [, $yearMonth, $day, $build] = $matches;

            if ($latest === null || self::compareSnapshots($yearMonth, $day, $build, $latest[0], $latest[1], $latest[2]) > 0) {
                $latest = [$yearMonth, $day, $build, $file];
            }
        }

        return $latest;
    }

    /**
     * Compares two snapshots encoded as (yearMonth, day, build), comparing any trailing build number
     * numerically so build IDs crossing a digit boundary still order correctly.
     */
    private static function compareSnapshots(
        string $yearMonth,
        string $day,
        string $build,
        string $otherYearMonth,
        string $otherDay,
        string $otherBuild,
    ): int {
        return [
            $yearMonth,
            $day,
            self::trailingNumber($build),
            $build,
        ] <=> [
            $otherYearMonth,
            $otherDay,
            self::trailingNumber($otherBuild),
            $otherBuild,
        ];
    }

    private static function trailingNumber(string $build): int
    {
        return preg_match('~(\d+)$~', $build, $matches) ? (int)$matches[1] : 0;
    }
}
