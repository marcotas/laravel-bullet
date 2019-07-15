<?php

namespace MarcoT89\Bullet\Traits;

trait BulletMiddleware
{
    public function __construct()
    {
        $this->registerMiddleware();

        if (method_exists($this, 'registerPolicy')) {
            $this->registerPolicy();
        }
    }

    protected function registerMiddleware()
    {
        $middlewares      = [];
        $this->middleware = is_string($this->middleware) ? [$this->middleware] : $this->middleware;
        foreach ($this->middleware as $middleware => $options) {
            if (!is_string($middleware)) {
                $middleware = $options;
                $options    = [];
            }
            $middlewares[] = compact('middleware', 'options');
        }
        $this->middleware = $middlewares;
    }
}
