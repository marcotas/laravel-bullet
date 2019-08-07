<?php

namespace MarcoT89\Bullet\Traits;

trait IndexAction
{
    public function index()
    {
        $request = $this->resolveRequestForAction('index');
        $this->authorizeAction('index');
        $this->beforeIndex($request);

        $perPage = $request->per_page ?? $request->perPage ?? $this->defaultPerPage;
        $perPage = $perPage <= 0 ? 1 : $perPage;
        $perPage = $perPage > $this->maxPerPage ? $this->maxPerPage : $perPage;

        $query = $this->getIndexQuery($request);
        $query = $this->afterIndex($request, $query);

        $collection = $query->paginate($perPage);

        if ($request->wantsJson()) {
            return $this->getModelResource()::collection($collection);
        }

        return view($this->getModelUrl() . '.index', [
            $this->getPluralModelVariableName() => $this->getModelResource()::collection($collection)
                ->toResponse($request)->getData(true),
        ]);
    }

    protected function beforeIndex($request)
    {
    }

    protected function afterIndex($request, $builder)
    {
        return $builder;
    }

    protected function getIndexQuery($request)
    {
        $query = $this->getFilteredQuery($request);

        if ($this->searchable && $this->modelHasMethod('scopeSearch')) {
            $query->search($request->search);
        }

        return $query;
    }

    protected function defaultSorts()
    {
        return $this->defaultSorts ?? [];
    }

    protected function allowedFilters()
    {
        return $this->allowedFilters ?? [];
    }

    protected function allowedIncludes()
    {
        return $this->allowedIncludes ?? [];
    }

    protected function allowedSorts()
    {
        return $this->allowedSorts ?? $this->getModelColumns();
    }

    protected function allowedFields()
    {
        return $this->allowedFields ?? $this->getModelColumns();
    }

    protected function allowedAppends()
    {
        return $this->allowedAppends ?? [];
    }
}
