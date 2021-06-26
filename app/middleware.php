<?php /** @noinspection PhpRedundantClosingTagInspection */

use app\middleware\After;
use app\middleware\Auth;

return [
    Auth::class,
    After::class,
];