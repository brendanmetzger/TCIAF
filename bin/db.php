<?php
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
