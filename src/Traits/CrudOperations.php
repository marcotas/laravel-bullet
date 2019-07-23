<?php

namespace MarcoT89\Bullet\Traits;

trait CrudOperations
{
    use IndexAction,
        StoreAction,
        UpdateAction,
        DestroyAction,
        ShowAction,
        EditAction,
        ForceDeleteAction,
        RestoreAction,
        BulletMiddleware,
        BulletPolicies,
        CrudHelpers;

    // Properties used for index action
    protected $defaultSorts    = null;
    protected $allowedFilters  = null;
    protected $allowedIncludes = null;
    protected $allowedSorts    = null;
    protected $allowedFields   = null;
    protected $allowedAppends  = null;
    protected $defaultPerPage  = 15;
    protected $maxPerPage      = 500;
    protected $searchable      = true;

    // Properties used by all resource actions
    protected $model;
    protected $policy;
    protected $modelColumns;
    protected $resource;
    protected $only;
    protected $except;
}
