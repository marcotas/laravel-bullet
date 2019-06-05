<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Facades\DB;

trait ShowAction
{
    use CrudHelpers;

    public function show($id)
    {
        $request = $this->resolveRequestForAction('show');
        $model   = $this->getShowQuery($request)->findOrFail($id);
        $this->authorizeAction('show', $model);

        return DB::transaction(function () use ($request, $model) {
            $model = $this->beforeShow($request, $model) ?? $model;
            $model = $this->afterShow($request, $model) ?? $model;

            return $this->getModelResource()::make($model);
        });
    }

    protected function getShowQuery($request)
    {
        return $this->getFilteredQuery($request);
    }

    protected function beforeShow($request, $model)
    {
        return $model;
    }

    protected function afterShow($request, $model)
    {
        return $model;
    }
}
