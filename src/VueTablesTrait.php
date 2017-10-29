<?php

namespace Grashoper20\VueTables;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait that when included on a model helps build pagination results.
 */
trait VueTablesTrait
{

    protected static $vueTablesSearchFields = [];

    /**
     * Simple pagination results.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function vueTables(Request $request)
    {
        return static::buildVueTablesResult(static::query(), $request);
    }

    /**
     * Build pagination result from a query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function buildVueTablesResult(Builder $query, Request $request)
    {
        $searchQuery = $request->query('query', '');
        if ($searchQuery) {
            $request->query('byColumn', '') == 1 ?
              static::filterByColumn($query, $searchQuery) :
              static::filterFields($query, $searchQuery);
        }
        if ($request->query('orderBy')) {
            $query->orderBy(
              $request->query('orderBy'),
              $request->query('ascending', true) ? 'asc' : 'desc'
            );
        }

        $pagination = $query->paginate($request->query('limit', 10));

        return [
          'count' => $pagination->total(),
          'data' => $pagination->items(),
        ];
    }

    /**
     * Helper method to search by columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *   The query builder
     * @param array $queries
     *   Yo dawg, a list of queries from the query query argument.
     */
    private static function filterByColumn(Builder $query, array $queries)
    {
        foreach (array_filter($queries) as $field => $searchQuery) {
            if (is_scalar($searchQuery)) {
                $query->where($field, 'LIKE', "%{$searchQuery}%");
            } else {
                $query->whereBetween($field, [
                  Carbon::createFromFormat('Y-m-d', $searchQuery['start'])
                    ->startOfDay(),
                  Carbon::createFromFormat('Y-m-d', $searchQuery['end'])
                    ->endOfDay(),
                ]);
            }
        }
    }

    /**
     * Helper method to search by columns.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *   The query builder
     * @param array $searchQuery
     *   Search query.
     */
    private static function filterFields(Builder $query, $searchQuery)
    {
        $query->where(function (Builder $query) use ($searchQuery) {
            $search = '%'.$searchQuery.'%';
            foreach (static::$vueTablesSearchFields as $i => $field) {
                $query->orWhere($field, 'LIKE', $search);
            }
        });
    }

}