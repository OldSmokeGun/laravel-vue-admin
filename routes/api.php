<?php

Route::namespace('Auth')->group(function (){
    Route::post('/login', 'LoginController@login');
    Route::post('/logout', 'LoginController@logout');

    Route::get('/admins', 'AdminController@list');
    Route::post('/admins/create', 'AdminController@create');
    Route::post('/admins/update', 'AdminController@update');
    Route::post('/admins/delete', 'AdminController@delete');
    Route::post('/admins/edit', 'AdminController@edit');
    Route::post('/admins/upload', 'AdminController@upload');
    Route::get('/admins/info', 'AdminController@info');
    Route::get('/admins/roles', 'AdminController@roles');

    Route::get('/roles', 'RoleController@list');
    Route::post('/roles/create', 'RoleController@create');
    Route::post('/roles/update', 'RoleController@update');
    Route::post('/roles/delete', 'RoleController@delete');
    Route::post('/roles/edit', 'RoleController@edit');
    Route::get('/roles/permissions', 'RoleController@permissions');

    Route::get('/permissions', 'PermissionController@list');
    Route::post('/permissions/create', 'PermissionController@create');
    Route::post('/permissions/update', 'PermissionController@update');
    Route::post('/permissions/delete', 'PermissionController@delete');
    Route::post('/permissions/edit', 'PermissionController@edit');
    Route::get('/permissions/trees', 'PermissionController@trees');
});

