<?php

Route::namespace('Render')->group(function (){
    Route::get('/{any}', 'AppController@index')->where('any', '.*');
});


