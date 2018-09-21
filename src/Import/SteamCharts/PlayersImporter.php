<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\SteamCharts;

use Amp\Iterator;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Async\Throttle\Throttle;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Specification\AsyncImportSpecification;

class PlayersImporter
{
    private $porter;

    private $database;

    private $logger;

    public function __construct(Porter $porter, Connection $database, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
    }

    public function import(): bool
    {
        $this->logger->info('Starting players import...');

        Loop::run(function (): \Generator {
            $averagePlayersIterator = $this->fetchAveragePlayers();

            $averagePlayers = [];
            while (yield $averagePlayersIterator->advance()) {
                $averagePlayersData = $averagePlayersIterator->getCurrent();

                $averagePlayers[$averagePlayersData['app_id']] = $averagePlayersData['average_players_7d'];
            }

            arsort($averagePlayers);

            $count = 0;
            $total = 250;
            foreach ($averagePlayers as $appId => $average) {
                if (++$count > $total) {
                    break;
                }

                $this->logger->debug("Inserting app ID #$appId...", compact('count', 'total'));

                $this->database->executeUpdate(
                    'INSERT OR IGNORE INTO app_players VALUES (?, ?)',
                    [$appId, round($average)]
                );
            }
        });

        $this->logger->info('Finished :^)');

        return true;
    }

    private function fetchAveragePlayers(): Iterator
    {
        return new Producer(function (\Closure $emit): \Generator {
            $throttle = new Throttle;

            $resource = new GetCurrentPlayers;
            $resource->setLogger($this->logger);
            $apps = $this->porter->importAsync(new AsyncImportSpecification($resource));

            $cutoffDate = new \DateTimeImmutable('-7 days');
            $count = 0;

            while (yield $apps->advance()) {
                $app = $apps->getCurrent();

                yield $throttle->await(
                    \Amp\call(function () use ($emit, $throttle, $app, $cutoffDate, &$count): \Generator {
                        $appId = +$app['app_id'];

                        $this->logger->info(
                            "Fetching app #$appId player history...",
                            compact('throttle') + ['count' => ++$count, 'total' => 1000]
                        );

                        try {
                            $players = yield $this->fetchPlayersHistory($appId, $cutoffDate);
                        } catch (GameUnavailableException $exception) {
                            $this->logger->error("App ID #$appId is unavailable: skipped.");

                            return;
                        }

                        yield $emit($app + ['average_players_7d' => array_sum($players) / \count($players)]);
                    })
                );
            }

            yield $throttle->finish();
        });
    }

    private function fetchPlayersHistory(int $appId, \DateTimeInterface $cutoffDate): Promise
    {
        return \Amp\call(function () use ($appId, $cutoffDate) {
            $playersHistory = $this->porter->importAsync(
                new GetPlayersHistorySpecification(new GetPlayersHistory($appId))
            );

            $players = [];
            while ((yield $playersHistory->advance())
                && ($history = $playersHistory->getCurrent())
                && $history['date'] >= $cutoffDate
            ) {
                $players[] = $history['players'];
            }

            return $players;
        });
    }
}
