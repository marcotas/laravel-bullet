<?php

namespace MarcoT89\Bullet\Traits;

trait BulletPolicies
{
    use CrudHelpers;

    protected function registerPolicy()
    {
        if (class_exists($this->getModel()) &&
            class_exists($this->getModelPolicy()) &&
            $this->policy !== false) {
            Gate::policy($this->getModel(), $this->getModelPolicy());
        }
    }
}
