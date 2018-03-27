<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Loop;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Collection\CountablePorterRecords;
use ScriptFUSION\Porter\Porter;

class ImportAsync
{
    private const MAX_REQUESTS = 40;
    private const REQ_PER_SEC = 20;

    private $porter;
    private $logger;
    private $client;
    private $requestId = 1;
    private $throttle;

    // Concurrency limit.
    private $activeRequests = 0;
    private $requests = 0;

    // Rate limit.
    private $startTime;

    public function __construct(Porter $porter, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->logger = $logger;
        $this->client = new DefaultClient;
        $this->client->setOption(Client::OP_MAX_REDIRECTS, 0);
        $this->throttle = new Throttle;
    }

    public function import(string $appListPath): bool
    {
        $appList = $this->porter->import(new AppListSpecification($appListPath, 1, 1));

        Loop::run(function () use ($appList) {
            $this->scheduleRequests($appList);
        });

        $this->logger->info('We did it REDDIT!');

        return true;
    }

    public function scheduleRequests(CountablePorterRecords $appList): void
    {
        $this->startTime = time();

        $this->scheduleNextRequests($appList);
    }

    private function scheduleNextRequests(CountablePorterRecords $appList): void
    {
        \Amp\call(function () use ($appList) {
            $total = \count($appList);

            while ($appList->valid()) {
                $app = $appList->current();
                $url = "http://store.steampowered.com/app/$app[id]/?cc=us";
//                $url = 'http://example.com';

                $this->logger->debug("Importing app #$app[id] ($this->requestId/$total)...");
                $this->throttle->registerRequest($this->request($url, $app, $this->requestId, $total));
                yield $this->throttle->await();

                $appList->next();
            }

            $appList->valid() && Loop::delay(
                100,
                function () use ($appList) {
                    $this->scheduleNextRequests($appList);
                }
            );
        });
    }

    private function request(string $url, array $app, int $current, int $total): Promise
    {
        return \Amp\call(function () use ($url, $app, $current, $total) {
            ++$this->requests;
            ++$this->requestId;
            ++$this->activeRequests;

            try {
                /** @var Response $response */
                $response = yield $this->client->request($url);
            } catch (\Throwable $throwable) {
                $this->logger->error("REQ $app[id]: $throwable");

                return;
            } finally {
                --$this->activeRequests;
            }

            try {
                $body = yield $response->getBody();
            } catch (\Throwable $throwable) {
                $this->logger->error("BODY $app[id]: $throwable");

                return;
            }

//            file_put_contents('php://memory', $body);
//            file_put_contents("$app[id].html", $body);

            $this->logger->debug(
                "Completed app #$app[id] ($current/$total)... HTTP: {$response->getStatus()}"
                    . " AR: $this->activeRequests"
            );
        });
    }

    private function canRequest(): bool
    {
        #return $this->requests / max(1, time() - $this->startTime) < self::REQ_PER_SEC;
        return $this->activeRequests < self::MAX_REQUESTS;
    }
}
