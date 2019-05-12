<?php

namespace MarcoT89\LaravelBullet;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarcoT89\LaravelBullet\Skeleton\SkeletonClass
 */
class LaravelBulletFacade extends Facade
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
