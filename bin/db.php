<?php
#!/usr/bin/env php

namespace bloc;

date_default_timezone_set ('America/Chicago');

#1. Frow where index.php is located, load the application file. This is the only mandatory part I think.
require_once  '../bloc/application.php';


#2. Create an instance of the application
$app = Application::instance();


# main page deal#!/usr/bin/env php
$app->prepare('listener', function ($app) {

  $start = microtime(true);
  $doc = \models\Graph::instance()->storage;
  $xpath = new \DOMXpath($doc);
  print "Loaded XML in " . (microtime(true) - $start) . "\n";

  $sock = socket_create_listen(0);
  socket_getsockname($sock, $addr, $port);
  print "Server Listening on $addr:$port\n";



  while($c = socket_accept($sock)) {
     /* do something useful */
     socket_getpeername($c, $raddr, $rport);
     socket_write($c, "what do you want?\n");

     if ($query = trim(socket_read($c, 1024, PHP_NORMAL_READ))) {

       for ($i=0; $i < 10; $i++) {
         $msg = "{$query}, {$i}\n";
         print $msg;
         socket_write($c, $msg);
         sleep(1);
       }
       socket_close($c);
     }

     print "Received Connection from $raddr:$rport\n";
  }
  socket_close($sock);
});


$start = microtime(true);

$app->execute('listener');

print "\n\nExecuted:" . (microtime(true) - $start) . "ms. Memory Peak: " . (memory_get_peak_usage() / pow(1024, 2)). "mb\n\n";
