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


# main page deal
$app->prepare('http-request', function ($app) {

  $request  = new Request($_REQUEST);
  $response = new Response($request);

  $app->setExchanges($request, $response);
  
  // Provide a namespace (also a directory) to load objects that can respond to controller->action
  $router  = new Router('controllers', $request);
  
  // default controller and action as arguments, in case nothin doin in the request
  $response->setBody($router->delegate('explore', 'index'));
  

  $app->execute('debug', $response);

  
  echo $response;
});


$app->prepare('clean-up', function ($app) {
  session_write_close();
});


$app->prepare('debug', function ($app, $response) {
  if (getenv('MODE') === 'local') {
    $app::instance()->log('Peak Memory: ' . round(memory_get_peak_usage() / pow(1024, 2), 4). "Mb");
    $app::instance()->log('Executed in: ' . round(microtime(true) - $app->benchmark, 4) . "s ");
    
    $output = $response->getBody();
    if ($output instanceof \bloc\view) {
      
      $elem = (new DOM\Element('script'))->insert($output->dom->documentElement->lastChild);
      $elem->setAttribute('type', 'text/javascript');
      $elem->appendChild($elem->ownerDocument->createTextNode("console.group('Backend notes');"));
      foreach ($app::instance()->log() as $message) {
        $elem->appendChild($elem->ownerDocument->createTextNode(sprintf("console.log(%s);", json_encode($message))));
      }
      $elem->appendChild($elem->ownerDocument->createTextNode("console.groupEnd();"));
    
    } else if ($response->type == 'html'){
      $response->setBody($output . "<pre>" . print_r($app::instance()->log(), true) . "</pre>");
    }
  }
  
  return $output;
});


#4. Run the app. Nothing happens w/o this. Can call different stuff from the queue.
$app->execute('session-start');
$app->execute('http-request');
$app->execute('clean-up');
