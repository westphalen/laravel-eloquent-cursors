<?php
/**
 * Created by PhpStorm.
 * User: sune
 * Date: 25/11/2017
 * Time: 21.37
 */

namespace Westphalen\Laravel\Cursors\Providers;

use Illuminate\Support\ServiceProvider;
use Westphalen\Laravel\Cursors\CursorPaginator;

class CursorPaginationServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        CursorPaginator::beforeAfterResolver(function ($beforeKey = 'before', $afterKey = 'after') {
            return [
                'before' => $this->app['request']->input($beforeKey),
                'after' => $this->app['request']->input($afterKey),
            ];
        });
    }
}
