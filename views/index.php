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
  
  print $app->execute('debug', $view);
  
  return true;
});

$app->prepare('clean-up', function ($app) {
  session_write_close();
});

$app->prepare('debug', function ($app, $view) {
  if (getenv('MODE') === 'development') {
    $app::instance()->log('Peak Memory: ' . round(memory_get_peak_usage() / pow(1024, 2), 4). "Mb");
    $app::instance()->log('Executed in: ' . round(microtime(true) - $app->benchmark, 4) . "s ");
    
    if ($view instanceof \bloc\view) {

      $elem = (new DOM\Element('script'))->insert($view->dom->documentElement->lastChild);
      $elem->setAttribute('type', 'text/javascript');
      $elem->appendChild($elem->ownerDocument->createTextNode("console.group('Backend notes');"));
      foreach (array_reverse($app::instance()->log()) as $message) {
        $elem->appendChild($elem->ownerDocument->createTextNode(sprintf("console.log(%s);", json_encode($message))));
      }
      $elem->appendChild($elem->ownerDocument->createTextNode("console.groupEnd();"));
    
    } else {
      echo "<pre>";
      print_r($app::instance()->log());
      echo "</pre>";
    }
  }
  
  return $view;
});


#4. Run the app. Nothing happens w/o this. Can call different stuff from the queue.
$app->execute('session-start');
$app->execute('before-output');
$app->execute('http-request');
$app->execute('clean-up');
