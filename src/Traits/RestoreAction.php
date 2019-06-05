<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Support\Facades\DB;

trait RestoreAction
{
    use CrudHelpers;

    public function restore($id)
    {
        $request = $this->resolveRequestForAction('restore');
        $model   = $this->getRestoreQuery($request)->findOrFail($id);
        $this->authorizeAction('restore', $model);

        return DB::transaction(function () use ($request, $model) {
            $model = $this->beforeRestore($request, $model) ?? $model;
            $model->restore();
            $model = $this->afterRestore($request, $model) ?? $model;

            return $this->getModelResource()::make($model);
        });
    }

    protected function getRestoreQuery($request)
    {
        return $this->getModel()::withTrashed();
    }

    protected function beforeRestore($request, $model)
    {
        return $model;
    }

    protected function afterRestore($request, $model)
    {
        return $model;
    }
}
