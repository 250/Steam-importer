<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Future;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Async\Throttle\DualThrottle;
use ScriptFUSION\Porter\Import\Import;
use ScriptFUSION\Porter\Porter;
use function Amp\async;

final class PlayersImporter
{
    private DualThrottle $throttle;

    public function __construct(
        private readonly Porter $porter,
        private readonly Connection $database,
        private readonly LoggerInterface $logger,
    ) {
        $this->throttle = new DualThrottle(150, 60);
    }

    public function import(): bool
    {
        $this->logger->info('Starting players import...');

        $averagePlayersIterator = $this->fetchAveragePlayers();
        $averagePlayers = array_column(iterator_to_array($averagePlayersIterator), 'average_players_1d', 'app_id');
        arsort($averagePlayers);

        $count = 0;
        $total = 300;
        foreach ($averagePlayers as $appId => $average) {
            if (++$count > $total) {
                break;
            }

            $this->logger->debug("Inserting app ID #$appId...", compact('count', 'total'));

            $this->database->executeStatement(
                'INSERT OR IGNORE INTO app_players VALUES (?, ?)',
                [$appId, round($average)]
            );
        }

        $this->logger->info('Finished :^)');

        return true;
    }

    private function fetchAveragePlayers(): \Iterator
    {
        $resource = new GetCurrentPlayers;
        $resource->setLogger($this->logger);
        $apps = $this->porter->import((new Import($resource))->setThrottle($this->throttle));

        foreach (Future::iterate(
            (function () use ($apps) {
                $cutoffDate = new \DateTimeImmutable('-1 day');
                $count = 0;

                foreach ($apps as $app) {
                    $appId = $app['app_id'];

                    yield async(function () use ($appId, &$count, $cutoffDate, $app): array {
                        $this->logger->info(
                            "Fetching app #$appId player history...",
                            ['count' => ++$count, 'total' => 2000, 'throttle' => $this->throttle]
                        );

                        [$players, $totalTime] = $this->fetchPlayersHistory($appId, $cutoffDate);

                        $this->logger->debug("App #$appId samples: " . \count($players));

                        return $app + ['average_players_1d' => $players ? array_sum($players) / $totalTime : 0];
                    });
                }
            })()
        ) as $appPlayers) {
            yield $appPlayers->await();
        }
    }

    /**
     * Fetches the number of time-weighted players for the specified app.
     *
     * @param int $appId App ID.
     * @param \DateTimeInterface $cutoffDate Cutoff date for the player history.
     *
     * @return array{int[], int} Tuple of time-weighted player counts and the total time.
     */
    private function fetchPlayersHistory(int $appId, \DateTimeInterface $cutoffDate): array
    {
        try {
            $playersHistory = iterator_to_array($this->porter->import(
                (new GetPlayersHistoryImport(new GetPlayersHistory($appId)))->setThrottle($this->throttle)
            ));
        } catch (GameUnavailableException) {
            $this->logger->error("App ID #$appId is unavailable: skipped.");

            return [[], 0];
        }

        $players = [];
        $totalTime = 0;
        foreach ($playersHistory as $i => $current) {
            if (!isset($playersHistory[$i + 1])) {
                break;
            }

            $next = $playersHistory[$i + 1];

            if ($current['date'] < $cutoffDate) {
                break;
            }

            $players[] = $current['players'] *
                ($duration = $next['date']?->getTimestamp() - $current['date']?->getTimestamp());
            $totalTime += $duration;
        }

        return [$players, $totalTime];
    }
}
