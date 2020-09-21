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

Route::post('/v1/document', 'Document\DocumentController@create'); // Клиент делает запрос на создание документа
Route::patch('/v1/document/{id}', 'Document\DocumentController@update'); // Клиент первый раз редактирует документ
Route::get('/v1/document/{id}', 'Document\DocumentController@get'); // Клиент получает документ
Route::post('/v1/document/{id}/publish', 'Document\DocumentController@publish'); // Клиент публикует документ
Route::get('/v1/document/', 'Document\DocumentController@paginate'); // Клиент получает документы с постраничной навигацией

