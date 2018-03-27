<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;

class RequestThrottle
{
    /**
     * The number of milliseconds to wait when the request frequency crosses the threshold.
     */
    private const RETRY_DELAY = 100;

    /**
     * @var Promise[]
     */
    private $requests = [];

    /**
     * @var Deferred[]
     */
    private $promises = [];

    private $maxConcurrentRequests = 20;

    private $maxRequestsPerSecond = 200;

    private $totalRequests = 0;

    private $startTime;

    public function await(): Promise
    {
        $promise = new Deferred;
        $this->promises[spl_object_hash($promise)] = $promise;

        $this->tryFulfilPromises();

        return $promise->promise();
    }

    public function registerRequest(Promise $request): void
    {
        $this->startTime === null && $this->startTime = self::getTime();

        $this->requests[$hash = spl_object_hash($request)] = $request;

        $request->onResolve(function () use ($hash) {
            unset($this->requests[$hash]);

            $this->tryFulfilPromises();
        });

        ++$this->totalRequests;
    }

    private function canRequest(): bool
    {
        return $this->isBelowConcurrentRequestsThreshold() && $this->isBelowRequestsPerSecondThreshold();
    }

    private function isBelowConcurrentRequestsThreshold(): bool
    {
        return $this->getActiveRequests() < $this->maxConcurrentRequests;
    }

    private function isBelowRequestsPerSecondThreshold(): bool
    {
        return $this->totalRequests / max(1, self::getTime() - $this->startTime) < $this->maxRequestsPerSecond;
    }

    private function tryFulfilPromises(): void
    {
        if ($this->canRequest()) {
            foreach ($this->promises as $promise) {
                $promise->resolve();
                unset($this->promises[spl_object_hash($promise)]);
            }
        }

        if (!$this->isBelowRequestsPerSecondThreshold()) {
            Loop::delay(
                self::RETRY_DELAY,
                function () {
                    $this->tryFulfilPromises();
                }
            );
        }
    }

    public function getActiveRequests(): int
    {
        return \count($this->requests);
    }

    public function getTotalRequests(): int
    {
        return $this->totalRequests;
    }

    public function getMaxConcurrentRequests(): int
    {
        return $this->maxConcurrentRequests;
    }

    public function setMaxConcurrentRequests(int $maxConcurrentRequests): void
    {
        $this->maxConcurrentRequests = $maxConcurrentRequests;
    }

    public function getMaxRequestsPerSecond(): int
    {
        return $this->maxRequestsPerSecond;
    }

    public function setMaxRequestsPerSecond(int $maxRequestsPerSecond): void
    {
        $this->maxRequestsPerSecond = $maxRequestsPerSecond;
    }

    private static function getTime(): float
    {
        return microtime(true);
    }
}
