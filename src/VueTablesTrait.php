<?php

namespace Grashoper20\VueTables;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Trait that when included on a model helps build pagination results.
 */
trait VueTablesTrait
{

    protected $vueTablesSearchFields = [];

    /**
     * Simple pagination results.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function vueTables(Request $request)
    {
        return $this->buildVueTablesResult($this->query(), $request);
    }

    /**
     * Build pagination result from a query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function buildVueTablesResult(Builder $query, Request $request)
    {
        $searchQuery = $request->query('query', '');
        if ($searchQuery) {
            $request->query('byColumn', '') == 1 ?
              $this->filterByColumn($query, $searchQuery) :
              $this->filterFields($query, $searchQuery);
        }
        if ($request->query('orderBy')) {
            $query->orderBy(
              $request->query('orderBy'),
              $request->query('ascending', true) ? 'asc' : 'desc'
            );
        }

        return $query->paginate($request->query('limit', 10));
    }

    /**
     * Helper method to search by columns.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *   The query builder
     * @param array $queries
     *   Yo dawg, a list of queries from the query query argument.
     * @return \Illuminate\Database\Query\Builder $queryBuilder
     */
    private function filterByColumn(Builder $query, array $queries)
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
     * @param \Illuminate\Database\Query\Builder $query
     *   The query builder
     * @param array $searchQuery
     *   Search query.
     * @return \Illuminate\Database\Query\Builder $queryBuilder
     */
    private function filterFields(Builder $query, $searchQuery)
    {
        $query->where(function (Builder $query) use ($searchQuery) {
            $search = '%'.$searchQuery.'%';
            foreach ($this->vueTablesSearchFields as $i => $field) {
                $query->orWhere($field, 'LIKE', $search);
            }
        });
    }

}