<?php

namespace MarcoT89\Bullet\Traits;

use MarcoT89\Bullet\Exceptions\ModelNotFoundException;
use MarcoT89\Bullet\Resources\DataResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait CrudHelpers
{
    protected $model;
    protected $modelPolicy;
    protected $modelColumns;
    protected $modelResource;

    protected function getModel()
    {
        $controller = class_basename(get_called_class());
        $model      = $this->model ?? Str::singular(str_replace('Controller', '', $controller));

        if (class_exists($model)) {
            return $this->model = $model;
        }
        if (class_exists('App\\Models\\' . $model)) {
            return $this->model = 'App\\Models\\' . $model;
        }
        if (class_exists('App\\' . $model)) {
            return $this->model = 'App\\' . $model;
        }
        throw new ModelNotFoundException("Model $model not found for controller $controller");
    }

    protected function getQuery(): Builder
    {
        return $this->getModel()::query();
    }

    protected function getFilteredQuery($request)
    {
        $query = $this->getQuery();

        if (class_exists('Spatie\QueryBuilder\QueryBuilder')) {
            $query = \Spatie\QueryBuilder\QueryBuilder::for($query)
                ->defaultSorts($this->defaultSorts())
                ->allowedFilters($this->allowedFilters())
                ->allowedIncludes($this->allowedIncludes())
                ->allowedSorts($this->allowedSorts())
                ->allowedFields($this->allowedFields())
                ->allowedAppends($this->allowedAppends());
        }

        return $query;
    }

    protected function modelHasMethod($method)
    {
        $model = $this->getModel();
        $model = new $model();

        return method_exists($model, $method);
    }

    protected function getModelUrl()
    {
        return Str::slug(Str::plural(class_basename($this->getModel())));
    }

    protected function getPluralModelVariableName()
    {
        return Str::camel(Str::plural(class_basename($this->getModel())));
    }

    protected function getSingularModelVariableName()
    {
        return Str::camel(Str::singular(class_basename($this->getModel())));
    }

    protected function getModelResource()
    {
        if (isset($this->modelResource)) {
            return $this->modelResource ?: DataResource::class;
        }

        $modelResource = class_basename($this->getModel()) . 'Resource';
        $resourceClass = 'App\\Http\\Resources\\' . $modelResource;

        if (class_exists($resourceClass)) {
            return $resourceClass;
        }

        return DataResource::class;
    }

    protected function getModelPolicy()
    {
        $model = class_basename($this->getModel());

        return "App\\Policies\\{$model}Policy";
    }

    protected function getModelColumns()
    {
        $modelClass = $this->getModel();
        $model      = new $modelClass();

        return $this->modelColumns = $this->modelColumns ?? Schema::getColumnListing($model->getTable());
    }

    protected function resolveRequestForAction($action)
    {
        $action            = ucfirst($action);
        $model             = class_basename($this->getModel());
        $modelPlural       = Str::plural($model);
        $requestsNamespace = 'App\\Http\\Requests';

        if (isset($this->requests) && Arr::has($this->requests, strtolower($action))) {
            $actionName = strtolower($action);

            return resolve($this->requests[$actionName]);
        }

        if (class_exists("$requestsNamespace\\{$modelPlural}\\{$action}Request")) {
            return resolve("$requestsNamespace\\{$modelPlural}\\{$action}Request");
        }

        if (class_exists("$requestsNamespace\\{$model}{$action}Request")) {
            return resolve("$requestsNamespace\\{$model}{$action}Request");
        }

        return resolve(Request::class);
    }

    protected function authorizeAction($action, $modelObject = null)
    {
        $ability = method_exists($this, 'resourceAbilityMap')
            ? ($this->resourceAbilityMap()[$action] ?? $action)
            : $action;
        // dd('ability', $ability, $this->getModelPolicy());

        if (class_exists($this->getModelPolicy()) && method_exists($this, 'authorize')) {
            $this->authorize($ability, $modelObject ?? $this->getModel());
        }
    }
}
