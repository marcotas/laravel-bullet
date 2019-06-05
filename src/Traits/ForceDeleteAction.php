<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

trait ForceDeleteAction
{
    use CrudHelpers;

    public function forceDelete($id)
    {
        $request = $this->resolveRequestForAction('forceDelete');
        $model   = $this->getForceDeleteQuery($request)->findOrFail($id);
        $this->authorizeAction('forceDelete', $model);

        return DB::transaction(function () use ($request, $model) {
            $model = $this->beforeForceDelete($request, $model) ?? $model;
            $model->forceDelete();
            $model = $this->afterForceDelete($request, $model) ?? $model;

            return response()->json(null, Response::HTTP_NO_CONTENT);
        });
    }

    protected function getForceDeleteQuery($request)
    {
        return $this->getModel()::withTrashed();
    }

    protected function beforeForceDelete($request, $model)
    {
        return $model;
    }

    protected function afterForceDelete($request, $model)
    {
        return $model;
    }
}
