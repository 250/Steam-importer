<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Collection\CountablePorterRecords;
use ScriptFUSION\Porter\Porter;

class ImportAsync
{
    private $porter;
    private $logger;
    private $client;
    private $requestId = 1;
    private $throttle;

    private $requests = 0;

    public function __construct(Porter $porter, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->logger = $logger;
        $this->client = new DefaultClient;
        $this->client->setOption(Client::OP_MAX_REDIRECTS, 0);
        $this->throttle = new RequestThrottle;
    }

    public function import(string $appListPath): bool
    {
        $appList = $this->porter->import(new AppListSpecification($appListPath, 1500, 14));

        $plainResponses = [];
        Loop::run(function () use ($appList, &$plainResponses) {
            $responses = $this->scheduleRequests($appList);

            while (yield $responses->advance()) {
                $plainResponses[] = $responses->getCurrent();
            }
        });

        $this->logger->info('We did it REDDIT!');

        return true;
    }

    private function scheduleRequests(CountablePorterRecords $appList): Producer
    {
         return new Producer(function (\Closure $emit) use ($appList) {
            $total = \count($appList);

            while ($appList->valid()) {
                $app = $appList->current();
//                $url = "http://store.steampowered.com/app/$app[id]/?cc=us";
                $url = 'http://example.com';

                $this->logger->debug("Importing app #$app[id] ($this->requestId/$total)...");
                $this->throttle->registerRequest($emit($this->request($url, $app, $this->requestId, $total)));
                yield $this->throttle->await();

                $appList->next();
            }
         });
    }

    private function request(string $url, array $app, int $current, int $total): Promise
    {
        return \Amp\call(function () use ($url, $app, $current, $total) {
            ++$this->requests;
            ++$this->requestId;

            try {
                /** @var Response $response */
                $response = yield $this->client->request($url);
            } catch (\Throwable $throwable) {
                $this->logger->error("REQ $app[id]: $throwable");

                return;
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
                    . " AR: {$this->throttle->getActiveRequests()}"
            );

            return $response->getStatus();
        });
    }
}
