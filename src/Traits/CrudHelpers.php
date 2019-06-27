<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MarcoT89\Bullet\Exceptions\ModelNotFoundException;
use MarcoT89\Bullet\Resources\DataResource;

trait CrudHelpers
{
    protected $model;
    protected $policy;
    protected $modelColumns;
    protected $resource;

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
        if (isset($this->resource)) {
            return $this->resource ?: DataResource::class;
        }

        $resource = class_basename($this->getModel()) . 'Resource';
        $resourceClass = 'App\\Http\\Resources\\' . $resource;

        if (class_exists($resourceClass)) {
            return $resourceClass;
        }

        return DataResource::class;
    }

    protected function getModelPolicy()
    {
        if ($this->policy) {
            return $this->policy;
        }
        
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
        $capitalizedAction = ucfirst($action);
        $model             = class_basename($this->getModel());
        $modelPlural       = Str::plural($model);
        $requestsNamespace = 'App\\Http\\Requests';

        if (isset($this->requests) && Arr::has($this->requests, $action)) {
            return resolve($this->requests[$action]);
        }

        if (class_exists("$requestsNamespace\\{$modelPlural}\\{$capitalizedAction}Request")) {
            return resolve("$requestsNamespace\\{$modelPlural}\\{$capitalizedAction}Request");
        }

        if (class_exists("$requestsNamespace\\{$model}{$capitalizedAction}Request")) {
            return resolve("$requestsNamespace\\{$model}{$capitalizedAction}Request");
        }

        return resolve(Request::class);
    }

    protected function authorizeAction($action, $modelObject = null)
    {
        $ability = method_exists($this, 'resourceAbilityMap')
            ? ($this->resourceAbilityMap()[$action] ?? $action)
            : $action;

        if (class_exists($this->getModelPolicy()) && method_exists($this, 'authorize')) {
            $this->authorize($ability, $modelObject ?? $this->getModel());
        }
    }
}
