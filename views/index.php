<?php
namespace bloc;

#1. Frow where index.php is located, load the application file. This is the only mandatory part I think.
require_once  '../bloc/application.php';


#2. Create an instance of the application
$app = new application;

#3. All code is executed in this callback.
$app->prepare('http-request', function($app) {
  $start = microtime(true);
  // Provide a namespace to load objects that can respond to controller->action
  $router  = new router('controllers', new request($_REQUEST));
  // default controller and action as arguments, in case nothin doin in the request
  $router->delegate('superintend', 'index');
  return microtime(true) - $start;
});


#4. Run the app. Nothing happens w/o this. Can call different stuff from the queue.
echo $app->execute('http-request');
