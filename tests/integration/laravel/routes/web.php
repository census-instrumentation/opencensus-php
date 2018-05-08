<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', 'UsersController@index')->name('index');
Route::get('/users/store', 'UsersController@store');
Route::get('/users/{user}/update', 'UsersController@update');
Route::get('/users/{user}/delete', 'UsersController@delete');
Route::get('/users/{user}', 'UsersController@show');
