<?php

declare(strict_types=1);

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/*
|--------------------------------------------------------------------------
| Breadcrumbs for Chat module
|--------------------------------------------------------------------------
|
| Define your breadcrumbs using the Breadcrumbs::for() method.
|
| Example:
|
| Breadcrumbs::for('admin.chat.index', function (BreadcrumbTrail $trail) {
|     $trail->parent('admin.home');
|     $trail->push('Chat', route('admin.chat.index'));
| });
|
*/
Breadcrumbs::for('admin.chat.index', function (BreadcrumbTrail $trail) {
    $trail->parent('admin.home');
    $trail->push('Чаты', route('admin.chat.index'));
});
