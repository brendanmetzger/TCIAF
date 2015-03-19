<?php
namespace bloc;

#1. Frow where index.php is located, load the application file. This is the only mandatory part I think.
require_once  '../bloc/application.php';


#2. Create an instance of the application
$app = new application;

#3. All code is executed in this callback.
$app->queue('http-request', function($app) {
  // routes and requests
  $router  = new router(new request($_REQUEST));
  // default controller and action as arguments, in case nothin doin in the request
  $router->delegate('explore', 'index');

});


#4. Run the app. Nothing happens w/o this. Can call different stuff from the queue.
$app->run('http-request');