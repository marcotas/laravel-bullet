<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Facades\DB;

trait EditAction
{
    use CrudHelpers;

    public function edit($id)
    {
        $request = $this->resolveRequestForAction('edit');
        $model   = $this->getEditQuery($request)->findOrFail($id);
        $this->authorizeAction('edit', $model);

        return DB::transaction(function () use ($request, $model) {
            $model = $this->beforeEdit($request, $model) ?? $model;
            $model = $this->afterEdit($request, $model) ?? $model;

            return view($this->getModelUrl() . '.edit', [
                $this->getSingularModelVariableName() => $model,
            ]);
        });
    }

    protected function getEditQuery($request)
    {
        return $this->getFilteredQuery($request);
    }

    protected function beforeEdit($request, $model)
    {
        return $model;
    }

    protected function afterEdit($request, $model)
    {
        return $model;
    }
}
