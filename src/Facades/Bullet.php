<?php

namespace MarcoT89\Bullet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarcoT89\Bullet\Skeleton\SkeletonClass
 */
class Bullet extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-bullet';
    }
}
