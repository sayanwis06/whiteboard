<?php
Route::middleware(['auth'])->prefix('external-apps/whiteboard')->group(function () {
    Route::get('/session/{courseId}', 'Controllers\WhiteboardController@session');
    Route::post('/broadcast', 'Controllers\WhiteboardController@broadcast');
    Route::post('/toggle-collab', 'Controllers\WhiteboardController@toggleCollab');
    Route::get('/check-access/{courseId}', 'Controllers\WhiteboardController@checkAccess');
    Route::get('/test', 'Controllers\WhiteboardController@test');
    Route::post('/save-snapshot', 'Controllers\WhiteboardController@saveSnapshot');
    Route::get('/dashboard', 'Controllers\WhiteboardController@dashboard');
    Route::delete('/snapshot/{id}', 'Controllers\WhiteboardController@deleteSnapshot');
    Route::get('/snapshot/{id}/download', 'Controllers\WhiteboardController@downloadSnapshot');
});
?>
