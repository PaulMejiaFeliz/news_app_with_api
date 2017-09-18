<?php

use Baka\Http\RouterCollection;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for api.
 */

$router = new RouterCollection($application);
$router->setPrefix('/api');

$router->get('/news', [
    'Newsapp\Controllers\NewsController',
    'getAllNews'
]);
            
$router->get('/news/{id}', [
    'Newsapp\Controllers\NewsController',
    'getById'
]);

$router->post('/news', [
    'Newsapp\Controllers\NewsController',
    'addNews'
]);
                

$router->put('/news/{id}', [
    'Newsapp\Controllers\NewsController',
    'editNews'
]);

$router->delete('/news/{id}', [
    'Newsapp\Controllers\NewsController',
    'deleteNews'
]);

$router->get('/news/{newsId}/comments', [
    'Newsapp\Controllers\CommentsController',
    'getAllComments'
]);

$router->get('/comments/{id}', [
    'Newsapp\Controllers\CommentsController',
    'getComment'
]);

$router->post('/comments', [
    'Newsapp\Controllers\CommentsController',
    'addComment'
]);

$router->put('/comments/{id}', [
    'Newsapp\Controllers\CommentsController',
    'editComment'
]);

$router->delete('/comments/{id}', [
    'Newsapp\Controllers\CommentsController',
    'deleteComment'
]);

$router->post('/account/login', [
    'Newsapp\Controllers\AccountController',
    'login'
]);

$router->post('/account/register', [
    'Newsapp\Controllers\AccountController',
    'register'
]);

$router->mount();

/**
 * Route not found
 */
$application->notFound(function () use ($application) {
    throw new Exception('route was not found');
});
