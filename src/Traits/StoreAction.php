<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait StoreAction
{
    use CrudHelpers;

    public function store()
    {
        $request = $this->resolveRequestForAction('store');
        $this->authorizeAction('store');

        $attributes = method_exists($request, 'validated')
            ? ($request->validated())
            : ($request->all());

        return DB::transaction(function () use ($attributes, $request) {
            $attributes = $this->beforeStore($request, $attributes) ?? $attributes;
            $modelObject = $this->getStoreQuery($request)->create($attributes);

            $modelObject = $this->afterStore($request, $modelObject) ?? $modelObject;

            return $this->getModelResource()::make($modelObject);
        });
    }

    protected function getStoreQuery($request)
    {
        return $this->getModel()::query();
    }

    protected function beforeStore($request, $attributes): array
    {
        return $attributes;
    }

    protected function afterStore($request, $model)
    {
        return $model;
    }
}
