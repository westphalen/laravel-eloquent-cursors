<?php
/**
 * Created by PhpStorm.
 * User: sune
 * Date: 24/11/2017
 * Time: 22.25
 */

namespace Westphalen\Laravel\Cursors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Westphalen\Laravel\Cursors\Contracts\CursorPaginator as CursorPaginatorInterface;

class CursorPaginator implements CursorPaginatorInterface
{
    /**
     * Resolver for `before` and `after` input.
     *
     * @var \Closure
     */
    protected static $beforeAfterResolver;

    /**
     * All of the items being paginated.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $items;

    /**
     * The number of items ahead of the cursor (after), if provided.
     *
     * @var int|null
     */
    protected $total;

    /**
     * The first item, used for generating `before` cursor.
     *
     * @var mixed|null
     */
    protected $firstKey;

    /**
     * The last item, used for generating `after` cursor.
     *
     * @var mixed|null
     */
    protected $lastKey;

    /**
     * The number of items per page (limit), if provided.
     *
     * @var int|null
     */
    protected $perPage;

    /**
     * The current cursor, that was used to populate this instance through `before` or `after`.
     *
     * @var mixed|null
     */
    protected $current;

    /**
     * Cache for the count of items.
     *
     * @var int|null
     */
    protected $count = null;

    /**
     * The base path to assign to all URLs.
     *
     * @var string
     */
    protected $path = '/';

    /**
     * @var bool
     */
    protected $skipEmptyUrls = false;

    /**
     * CursorPaginator constructor.
     *
     * @param   array|Collection $items
     * @param   mixed|null $current
     * @param   int $perPage
     * @param   int|null $total
     */
    public function __construct($items, $current, $perPage = null, $total = null)
    {
        $this->total = $total;
        $this->current = $current;
        $this->perPage = $perPage;
        $this->total = $total;

        if (!$items instanceof Collection) {
            $items = collect($items);
        }

        $this->setItems($items);

        $this->path = Paginator::resolveCurrentPath($this->path);
    }

    /**
     * Set the items for the paginator.
     *
     * @param  mixed $items
     * @return void
     */
    protected function setItems(Collection $items)
    {
        $this->count = $items->count();

        if ($first = $items->first()) {
            $first = $first instanceof Model ? $first->getKey() : $first;
            $this->firstKey = $first;
        }

        if ($last = $items->last()) {
            $last = $last instanceof Model ? $last->getKey() : $last;
            $this->lastKey = $last;
        }

        $this->items = $items;
    }

    /**
     * Provide the resolver for `before` and `after` input.
     *
     * @param   callable $resolver
     * @return  void
     */
    public static function beforeAfterResolver(callable $resolver)
    {
        static::$beforeAfterResolver = $resolver;
    }

    /**
     * Resolve `before` and `after` input.
     *
     * @return array|null
     */
    public static function resolveBeforeAfter()
    {
        if (isset(static::$beforeAfterResolver)) {
            return call_user_func(static::$beforeAfterResolver);
        }

        return null;
    }

    /**
     * Determine if there are more items in the data store.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->hasPagesBefore() && $this->hasPagesAfter();
    }

    /**
     * Determine if there are more items before the current cursor.
     *
     * @return bool
     */
    public function hasPagesBefore()
    {
        return $this->current !== null;
    }

    /**
     * Determine if there are more items after the current cursor.
     *
     * @return bool
     */
    public function hasPagesAfter()
    {
        return $this->count() === $this->perPage && ($this->total === null || $this->total > $this->perPage);
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Get the items.
     *
     * @return Collection
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Generate `before` url.
     *
     * @param   mixed $cursor
     * @return  string
     */
    public function beforeUrl($cursor = null)
    {
        $cursor = $cursor ?: ($this->firstKey !== null || !$this->skipEmptyUrls ? $this->firstKey : null);
        return $this->url($cursor, true);
    }

    /**
     * Generate url.
     *
     * @param   mixed $cursor
     * @param   bool $isBefore
     * @return  string
     */
    public function url($cursor, $isBefore = false)
    {
        if ($cursor === null) {
            return null;
        }

        $parameters = [$isBefore ? 'before' : 'after' => $cursor];

        return $this->path
            . (Str::contains($this->path, '?') ? '&' : '?')
            . http_build_query($parameters, '', '&');
    }

    /**
     * Generate `after` url.
     *
     * @param   mixed $cursor
     * @return  string
     */
    public function afterUrl($cursor = null)
    {
        $cursor = $cursor ?: ($this->lastKey !== null || !$this->skipEmptyUrls ? $this->lastKey : null);
        return $this->url($cursor, false);
    }

    /**
     * Get total items available in `after` direction.
     *
     * @return int|null
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * Get the key of the first item being paginated.
     *
     * @return mixed|null
     */
    public function firstItem()
    {
        return $this->firstKey;
    }

    /**
     * Get the key of the last item being paginated.
     *
     * @return mixed|null
     */
    public function lastItem()
    {
        return $this->lastKey;
    }

    /**
     * Determine how many items are being shown per page.
     *
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * Toggle don't return urls for a direction that is not known to have more items.
     *
     * @param   bool $value
     * @return  void
     */
    public function skipEmptyUrls($value = true)
    {
        $this->skipEmptyUrls($value);
    }

    /**
     * Get the state of skipEmptyUrls.
     *
     * @return bool
     */
    public function getSkipEmptyUrls()
    {
        return $this->skipEmptyUrls;
    }
}
