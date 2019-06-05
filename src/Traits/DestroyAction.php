<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

trait DestroyAction
{
    use CrudHelpers;

    public function destroy($id)
    {
        $request = $this->resolveRequestForAction('destroy');
        $model   = $this->getDestroyQuery($request)->findOrFail($id);
        $this->authorizeAction('destroy', $model);

        return DB::transaction(function () use ($request, $model) {
            $model = $this->beforeDestroy($request, $model) ?? $model;
            $model->delete();
            $model = $this->afterDestroy($request, $model) ?? $model;

            return response()->json(null, Response::HTTP_NO_CONTENT);
        });
    }

    protected function getDestroyQuery($request)
    {
        return $this->getModel()::query();
    }

    protected function beforeDestroy($request, $model)
    {
        return $model;
    }

    protected function afterDestroy($request, $model)
    {
        return $model;
    }
}
