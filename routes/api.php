<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get(
    '/user',
    function (Request $request) {
        return $request->user();
    }
);

// If you don't need external API data mirroring, disconnect the "api_mirror" middlewares
Route::middleware('api_mirror')->group(
    function () {
# Users
        Route::get('/users', 'UserController@index'); // incl. ?email=
        Route::get('/users/{id}', 'UserController@show');

# Posts
        Route::get('/posts', 'PostController@index'); // incl. ?userId= / ?title=

        Route::get('/posts/{id}', 'PostController@show');

# Comments
        Route::get('/comments', 'CommentController@index'); // incl. ?postId=
        Route::get('/posts/{id}/comments', 'CommentController@indexOfPost');
    }
);
