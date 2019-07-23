<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Facades\DB;

trait UpdateAction
{
    public function update($id)
    {
        $request = $this->resolveRequestForAction('update');
        $model   = $this->getUpdateQuery($request)->findOrFail($id);
        $this->authorizeAction('update', $model);

        $attributes = method_exists($request, 'validated')
            ? ($request->validated())
            : ($request->all());

        return DB::transaction(function () use ($attributes, $request, $model) {
            $attributes = $this->beforeUpdate($request, $attributes, $model) ?? $attributes;
            $model->update($attributes);
            $model = $this->afterUpdate($request, $model) ?? $model;

            return $this->getModelResource()::make($model);
        });
    }

    protected function getUpdateQuery($request)
    {
        return $this->getModel()::query();
    }

    protected function beforeUpdate($request, $attributes, $model): array
    {
        return $attributes;
    }

    protected function afterUpdate($request, $model)
    {
        return $model;
    }
}
