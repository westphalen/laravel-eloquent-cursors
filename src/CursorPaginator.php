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

    protected static $beforeAfterResolver;

    /**
     * All of the items being paginated.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $items;

    protected $total;

    protected $beforeKey;

    protected $afterKey;

    protected $perPage;

    protected $current;

    /**
     * The base path to assign to all URLs.
     *
     * @var string
     */
    protected $path = '/';

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

    public static function beforeAfterResolver(callable $resolver) {
        static::$beforeAfterResolver = $resolver;
    }

    public static function resolveBeforeAfter()
    {
        if (isset(static::$beforeAfterResolver)) {
            return call_user_func(static::$beforeAfterResolver);
        }

        return null;
    }

    public function items()
    {
        return $this->items;
    }

    /**
     * Set the items for the paginator.
     *
     * @param  mixed  $items
     * @return void
     */
    protected function setItems(Collection $items)
    {
        if ($this->current && ($first = $items->first())) {
            $first = $first instanceof Model ? $first->getKey() : $first;
            $this->beforeKey = $first;
        }

        if ($items->count() == $this->perPage && ($this->total === null || $this->total > $this->perPage) && ($last = $items->last())) {
            $last = $last instanceof Model ? $last->getKey() : $last;
            $this->afterKey = $last;
        }

        $this->items = $items;
    }

    public function url($key, $after = false)
    {
        if ($key === null) {
            return null;
        }

        $parameters = [$after ? 'after' : 'before' => $key];

        return $this->path
            .(Str::contains($this->path, '?') ? '&' : '?')
            .http_build_query($parameters, '', '&');
    }

    public function beforeUrl($key = null)
    {
        $key = $key ?: $this->beforeKey;
        return $this->url($key, false);
    }

    public function afterUrl($key = null)
    {
        $key = $key ?: $this->afterKey;
        return $this->url($key, true);
    }

    public function total()
    {
        return $this->total;
    }
}
