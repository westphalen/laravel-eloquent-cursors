<?php
/**
 * Created by PhpStorm.
 * User: sune
 * Date: 24/11/2017
 * Time: 21.41
 */

namespace Westphalen\Laravel\Cursors\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Westphalen\Laravel\Cursors\CursorPaginator;

trait CursorPagination
{

    /**
     * Boot the cursor pagination trait for a model.
     *
     * @return void
     */
    public static function bootCursorPagination()
    {
        Builder::macro('cursorPaginate', function ($perPage = null, $orderBy = null) {
            return $this->getModel()->cursorPaginate($perPage, $orderBy);
        });
    }

    /**
     * Paginate the given cursor with the cursor  method.
     *
     * @param   int|null    $perPage
     * @param   string|null $cursorColumn
     * @param   string      $cursorDirection
     * @return  CursorPaginator
     */
    public function cursorPaginate($perPage = null, $cursorColumn = null, $cursorDirection = 'DESC')
    {
        $input = CursorPaginator::resolveBeforeAfter();

        $cursorColumn = [$cursorColumn, $cursorDirection];

        if ($after = Arr::get($input, 'after')) {
            return $this->newAfterCursorPaginator($after, $perPage, $cursorColumn);
        }   else if ($before = Arr::get($input, 'before')) {
            return $this->newBeforeCursorPaginator($before, $perPage, $cursorColumn);
        } else {
            return $this->newDefaultCursorPaginator($perPage, $cursorColumn);
        }
    }

    /**
     * Create CursorPaginator instance using `before` query.
     *
     * @param   mixed               $id
     * @param   int|null            $perPage
     * @param   string|array|null   $orderBy
     * @return  CursorPaginator
     */
    protected function newBeforeCursorPaginator($id, $perPage, $orderBy)
    {
        return $this->newCursorPaginator($id, true, $perPage, $orderBy);
    }

    /**
     * Create CursorPaginator instance using `after` query.
     *
     * @param   mixed               $id
     * @param   int|null            $perPage
     * @param   string|array|null   $orderBy
     * @return  CursorPaginator
     */
    protected function newAfterCursorPaginator($id, $perPage, $orderBy)
    {
        return $this->newCursorPaginator($id, false, $perPage, $orderBy);
    }

    /**
     * Create CursorPaginator instance from beginning of table.
     *
     * @param   int|null            $perPage
     * @param   string|array|null   $orderBy
     * @return  CursorPaginator
     */
    protected function newDefaultCursorPaginator($perPage = null, $orderBy = null)
    {
        return $this->newCursorPaginator(null, false, $perPage, $orderBy);
    }

    /**
     * Create CursorPaginator instance after applying the relevant query and retrieving data.
     *
     * @param   mixed               $id
     * @param   bool                $isBefore
     * @param   int|null            $perPage
     * @param   string|array|null   $cursorColumn
     * @param   string|null         $primaryKey
     * @return  CursorPaginator
     */
    protected function newCursorPaginator($id = null, $isBefore = false, $perPage = null, $cursorColumn = null, $primaryKey = null)
    {
        if ($isBefore) {
            $query = $this->before($id, $perPage, $cursorColumn, $primaryKey);
        } else {
            $query = $this->after($id, $perPage, $cursorColumn, $primaryKey);
        }

        $items = ($total = $query->count()) ? $query->get() : $this->newCollection();

        return new CursorPaginator($items, $id, $perPage, $total);
    }

    /**
     * Scope to only show elements before a given id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed         $before
     * @param int|null      $perPage
     * @param string|null   $orderBy
     * @param string|null   $key
     */
    public function scopeBefore($query, $before, $perPage = null, $orderBy = null, $key = null)
    {
        $this->applyScope($query, $before, '>', $perPage, $orderBy, $key);
    }

    /**
     * Scope to only show elements after a given id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed         $after
     * @param int|null      $perPage
     * @param string|null   $orderBy
     * @param string|null   $key
     */
    public function scopeAfter($query, $after, $perPage = null, $orderBy = null, $key = null)
    {
        $this->applyScope($query, $after, '<', $perPage, $orderBy, $key);
    }

    /**
     * Apply the scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed         $id
     * @param string        $operator
     * @param int|null      $perPage
     * @param string|null   $cursorColumn
     * @param string|null   $primaryKey
     */
    protected function applyScope($query, $id, $operator, $perPage = null, $cursorColumn = null, $primaryKey = null)
    {
        $perPage = $perPage ?: $this->perPage;
        if (is_array($cursorColumn)) {
            list($cursorColumn, $orderDirection) = $cursorColumn;
        }
        $cursorColumn = $cursorColumn ?? $this->getCreatedAtColumn();
        $orderDirection = $orderDirection ?? 'DESC';
        $primaryKey = $primaryKey ?: $this->getKeyName();

        if ($cursorColumn) {
            // Cache existing orders, and reapply after $cursorColumn.
            $orders = $query->getQuery()->orders ?? [];
            $query->getQuery()->orders = null;
            $query->orderBy($cursorColumn, $orderDirection);
            foreach ($orders as $order) {
                $query->orders[] = $order;
            }
        }

        if ($perPage) {
            $query->limit($perPage);
        }

        if ($id !== null) {
            if ($primaryKey == $cursorColumn) {
                // We can use a simple where clause if primaryKey is used as the cursorColumn.
                $query->where($primaryKey, $operator, $id);
            } else {
                // Use a subquery to get the cursorColumn value of the given $id.
                $query->where($cursorColumn, $operator, function ($query) use ($id, $cursorColumn, $primaryKey) {
                    /** @var \Illuminate\Database\Eloquent\Builder $query */
                    $query->select($cursorColumn)->from($this->getTable())->where($primaryKey, $id)->limit(1);
                });
            }
        }
    }
}
