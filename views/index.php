<?php
namespace bloc;

date_default_timezone_set ('America/Chicago');

#1. Frow where index.php is located, load the application file. This is the only mandatory part I think.
require  '../bloc/application.php';



#2. Create an instance of the application
$app = Application::instance(['mode' => getenv('MODE') ?: 'production']);


// this is non functional, but indicates some meta programming potential.
$app->prepare('session-start', function ($app) {
  $app::session('TCIAF');
});

$app->prepare('before-output', function ($app) {
  $needle = 'HTTP_X_REQUESTED_WITH';
  if (array_key_exists($needle, $_SERVER) && $_SERVER[$needle] == 'XMLHttpRequest' ) {
    View::addRenderer('preflight', function ($view) {
      $view->context = $view->dom->documentElement->lastChild;
      header('Content-Type: application/xml; charset=utf-8');
    });
  }
});

# main page deal
$app->prepare('http-request', function ($app) {
  
  // Provide a namespace (also a directory) to load objects that can respond to controller->action
  $router  = new router('controllers', new request($_REQUEST));
  
  // default controller and action as arguments, in case nothin doin in the request
  $view = $router->delegate('manage', 'index');

  (new DOM\Element('pre', microtime(true) - $app->benchmark))->insert($view->dom->documentElement->lastChild)->setAttribute('class', 'console');


  print $view;
  
  return true;
});

$app->prepare('clean-up', function ($app) {
  session_write_close();
});


#4. Run the app. Nothing happens w/o this. Can call different stuff from the queue.
$app->execute('session-start');
$app->execute('before-output');
$app->execute('http-request');
$app->execute('clean-up');
