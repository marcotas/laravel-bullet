<?php

namespace MarcoT89\LaravelBullet\Tests;

use MarcoT89\Bullet\Bullet;
use Illuminate\Support\Facades\Route;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\TestCase;

class BulletRoutesTest extends TestCase
{
    /** @var \Illuminate\Container\Container */
    protected $app;

    public function setUpLaravelApplication()
    {
        $app = new Container();
        $app->singleton('app', Container::class);
        $app->singleton('config', Repository::class);

        $this->app = $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLaravelApplication();
    }


    /** @test */
    public function true_is_true()
    {
        $bullet = new Bullet();
        $bullet->controllers();
        dd('routes', Route::getRoutes());
        // Bullet::controllers();
        $this->assertTrue(true);
    }
}
