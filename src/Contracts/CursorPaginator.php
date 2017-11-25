<?php
/**
 * Created by PhpStorm.
 * User: sune
 * Date: 24/11/2017
 * Time: 23.51
 */

namespace Westphalen\Laravel\Cursors\Contracts;

interface CursorPaginator
{
    /**
     * Get total items available in `after` direction.
     *
     * @return int|null
     */
    public function total();

    /**
     * Generate `before` url.
     *
     * @param   mixed $cursor
     * @return  string
     */
    public function beforeUrl($cursor);

    /**
     * Generate `after` url.
     *
     * @param   mixed $cursor
     * @return  string
     */
    public function afterUrl($cursor);

    /**
     * Get the items.
     *
     * @return \Illuminate\Support\Collection
     */
    public function items();

    /**
     * Get the key of the first item being paginated.
     *
     * @return int
     */
    public function firstItem();

    /**
     * Get the key of the last item being paginated.
     *
     * @return int
     */
    public function lastItem();

    /**
     * Determine how many items are being shown per page.
     *
     * @return int
     */
    public function perPage();

    /**
     * Determine if there is more items in the data store.
     *
     * @return bool
     */
    public function hasMorePages();
}
