<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Facades\Gate;

trait BulletPolicies
{
    protected function registerPolicy()
    {
        $policy = $this->policy ?? null;

        if (class_exists($this->getModel()) &&
            class_exists($this->getModelPolicy()) &&
            $policy !== false) {
            Gate::policy($this->getModel(), $this->getModelPolicy());
        }
    }
}
