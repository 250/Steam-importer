<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Loop;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Collection\CountablePorterRecords;
use ScriptFUSION\Porter\Porter;

class ImportAsync
{
    private const MAX_REQUESTS = 40;

    private $porter;
    private $logger;
    private $client;
    private $activeRequests = 0;
    private $requestId = 1;

    public function __construct(Porter $porter, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->logger = $logger;
        $this->client = new DefaultClient;
        $this->client->setOption(Client::OP_MAX_REDIRECTS, 0);
    }

    public function import(string $appListPath): bool
    {
        $appList = $this->porter->import(new AppListSpecification($appListPath, 15, 14));

        Loop::run(function () use ($appList) {
            $this->scheduleRequests(null, $appList);
        });

        $this->logger->info('We did it REDDIT!');

        return true;
    }

    public function scheduleRequests(?string $watcherId, CountablePorterRecords $appList): void
    {
        $total = \count($appList);

        while ($appList->valid() && $this->activeRequests < self::MAX_REQUESTS) {
            $app = $appList->current();
            $url = "http://store.steampowered.com/app/$app[id]/?cc=us";
//        $url = 'http://example.com';

            $this->logger->debug("Importing app #$app[id] ($this->requestId/$total)...");
            $this->request($url, $app, $this->requestId, $total);

            $appList->next();
        }

        $appList->valid() && Loop::delay(100, [$this, __FUNCTION__], $appList);
    }

    private function request(string $url, array $app, int $current, int $total): void
    {
        ++$this->requestId;
        ++$this->activeRequests;

        $this->client->request($url)->onResolve(function ($error, $response) use ($app, $current, $total) {
            --$this->activeRequests;

            if ($error) {
                $this->logger->error("REQ $app[id]: $error");

                return;
            }

            $response->getBody()->onResolve(function ($error, $body) use ($app) {
                if ($error) {
                    $this->logger->error("BODY $app[id]: $error");

                    return;
                }

                file_put_contents('php://memory', $body);
//                        file_put_contents("$app[id].html", $body);
            });

//            $this->logger->debug("Completed app #$app[id] ($current/$total)... AR: $this->activeRequests");
            $this->logger->debug("Completed app #$app[id] ($current/$total)... HTTP: {$response->getStatus()}");
        });
    }
}
