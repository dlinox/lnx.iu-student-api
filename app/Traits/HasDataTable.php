<?php

namespace App\Traits;

trait HasDataTable
{
    public function scopeSearch($query, $search, $columns = [])
    {
        $columns = $columns ?: $query->getModel()::$searchColumns ?? [];

        $query->where(function ($query) use ($search, $columns) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', "%$search%");
            }
        });

        return $query;
    }

    public function scopeFilter($query, $filters)
    {
        foreach ($filters as $filter => $value) {
            if (!is_null($value)) {
                $query->where(function ($query) use ($filter, $value) {
                    $query->where($filter, $value);
                });
            }
        }
        return $query;
    }

    public static function scopeDataTable($query, $request, $searchColumns = [])
    {
        $itemsPerPage = $request->has('pageSize') ? $request->pageSize : 10;

        // if ($request->has('filters') && is_array($request->filters)) {
        //     $query->filter($request->filters);
        // }

        if ($request->has('search')) {
            $searchColumns = $searchColumns ?: $query->getModel()->searchColumns;
            $query->search($request->search, $searchColumns);
        }

        return $query->paginate($itemsPerPage);
    }
}
