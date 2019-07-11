<?php

namespace MarcoT89\Bullet\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearch(Builder $query, $search)
    {
        if (is_null($search)) {
            return $query;
        }

        $fields = collect($this->searchableFields());
        if ($fields->isEmpty()) {
            return $query;
        }

        $firstField = $fields->shift();
        $driver = optional($this->getConnection())->getDriverName() ?? 'mysql';
        $like = $driver === 'pgsql' ? 'ilike' : 'like';

        $query->where($firstField, $like, '%' . strtolower($search) . '%');

        foreach ($fields as $field) {
            $query->orWhere($field, $like, '%' . strtolower($search) . '%');
        }

        return $query;
    }

    public function searchableFields()
    {
        return $this->searchableFields ?? [];
    }
}
