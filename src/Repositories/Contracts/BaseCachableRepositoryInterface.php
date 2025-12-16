<?php

namespace Adobrovolsky97\LaravelRepositoryServicePattern\Repositories\Contracts;

/**
 * Interface BaseCachableRepositoryInterface
 */
interface BaseCachableRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Clear get all & paginated cache keys
     *
     * @return void
     */
    public function flushListsCaches(): void;
}
