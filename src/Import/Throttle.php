<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;

class Throttle
{
    /**
     * Milliseconds to wait when the watch frequency crosses the threshold.
     */
    private const RETRY_DELAY = 100;

    /**
     * List of promises we're throttling.
     *
     * @var Promise[]
     */
    private $watching = [];

    /**
     * List of promises waiting to be notified when the throttle is cleared.
     *
     * @var Deferred[]
     */
    private $awaiting = [];

    private $maxConcurrency = 30;

    private $maxPerSecond = 75;

    private $total = 0;

    private $startTime;

    private $finished = false;

    public function await(Promise $promise): Promise
    {
        if ($this->finished) {
            throw new \BadMethodCallException('Cannot await: throttle has finished.');
        }

        $this->watch($promise);

        if ($this->tryFulfilPromises()) {
            return new Success;
        }

        $deferred = new Deferred;
        $this->awaiting[spl_object_hash($deferred)] = $deferred;

        return $deferred->promise();
    }

    /**
     * Finish awaiting objects and waits for all pending promises to complete.
     *
     * @return Promise
     */
    public function finish(): Promise
    {
        $this->finished = true;

        return \Amp\call(function () {
            yield $this->watching;
        });
    }

    private function watch(Promise $promise): void
    {
        $this->startTime === null && $this->startTime = self::getTime();

        $this->watching[$hash = spl_object_hash($promise)] = $promise;

        $promise->onResolve(function () use ($hash) {
            unset($this->watching[$hash]);

            $this->tryFulfilPromises();
        });

        ++$this->total;
    }

    private function isThrottled(): bool
    {
        return $this->isBelowConcurrencyThreshold() && $this->isBelowChronoThreshold();
    }

    private function isBelowConcurrencyThreshold(): bool
    {
        return $this->getActive() < $this->maxConcurrency;
    }

    private function isBelowChronoThreshold(): bool
    {
        return $this->total / max(1, self::getTime() - $this->startTime) < $this->maxPerSecond;
    }

    private function tryFulfilPromises(): bool
    {
        if ($this->isThrottled()) {
            foreach ($this->awaiting as $promise) {
                unset($this->awaiting[spl_object_hash($promise)]);
                $promise->resolve();
            }

            return true;
        }

        if (!$this->isBelowChronoThreshold()) {
            Loop::delay(
                self::RETRY_DELAY,
                function () {
                    $this->tryFulfilPromises();
                }
            );
        }

        return false;
    }

    public function getActive(): int
    {
        return \count($this->watching);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getMaxConcurrency(): int
    {
        return $this->maxConcurrency;
    }

    public function setMaxConcurrency(int $maxConcurrency): void
    {
        $this->maxConcurrency = $maxConcurrency;
    }

    public function getMaxPerSecond(): int
    {
        return $this->maxPerSecond;
    }

    public function setMaxPerSecond(int $maxPerSecond): void
    {
        $this->maxPerSecond = $maxPerSecond;
    }

    private static function getTime(): float
    {
        return microtime(true);
    }
}
