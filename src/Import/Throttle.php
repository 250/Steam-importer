<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import;

use Amp\Deferred;
use Amp\Promise;

class Throttle
{
    private const MAX_REQUESTS = 25;

    private $requests = [];

    /** @var Deferred[] */
    private $promises = [];

    public function await(): Promise
    {
        $promise = new Deferred;
        $this->promises[spl_object_hash($promise)] = $promise;

        $this->tryFulfilPromises();

        return $promise->promise();
    }

    public function canRequest(): bool
    {
        return $this->getActiveRequests() < self::MAX_REQUESTS;
    }

    public function registerRequest(Promise $request): void
    {
        $this->requests[spl_object_hash($request)] = $request;

        $request->onResolve(function () use ($request) {
            unset($this->requests[spl_object_hash($request)]);

            $this->tryFulfilPromises();
        });
    }

    public function getActiveRequests(): int
    {
        return \count($this->requests);
    }

    private function tryFulfilPromises(): void
    {
        if ($this->canRequest()) {
            foreach ($this->promises as $promise) {
                $promise->resolve();
                unset($this->promises[spl_object_hash($promise)]);
            }
        }
    }
}
