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
    public function total();

    public function beforeUrl();

    public function afterUrl();

    public function items();
}
