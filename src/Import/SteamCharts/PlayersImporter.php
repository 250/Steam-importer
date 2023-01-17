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
        $averagePlayers = array_column(iterator_to_array($averagePlayersIterator), 'average_players_7d', 'app_id');
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

        $cutoffDate = new \DateTimeImmutable('-7 days');
        $count = 0;

        foreach ($apps as $app) {
            $appId = $app['app_id'];

            $appsPlayers[] = async(function () use (&$count, $appId, $cutoffDate, $app) {
                $this->logger->info(
                    "Fetching app #$appId player history...",
                    ['count' => ++$count, 'total' => 2000, 'throttle' => $this->throttle]
                );

                $players = $this->fetchPlayersHistory($appId, $cutoffDate);

                return $app + ['average_players_7d' => $players ? array_sum($players) / \count($players) : 0];
            });
        }

        foreach (Future::iterate($appsPlayers ?? []) as $appPlayers) {
            yield $appPlayers->await();
        }
    }

    /**
     * @return int[]
     */
    private function fetchPlayersHistory(int $appId, \DateTimeInterface $cutoffDate): array
    {
        try {
            $playersHistory = $this->porter->import(
                (new GetPlayersHistoryImport(new GetPlayersHistory($appId)))->setThrottle($this->throttle)
            );
        } catch (GameUnavailableException) {
            $this->logger->error("App ID #$appId is unavailable: skipped.");

            return [];
        }

        $players = [];
        foreach ($playersHistory as $history) {
            if ($history['date'] < $cutoffDate) {
                break;
            }

            $players[] = $history['players'];
        }

        return $players;
    }
}
