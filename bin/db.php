<?php
$sock = socket_create_listen(0);
socket_getsockname($sock, $addr, $port);
print "Server Listening on $addr:$port\n";

while($c = socket_accept($sock)) {
   /* do something useful */
   socket_getpeername($c, $raddr, $rport);
   for ($i=0; $i < 10; $i++) {
     sleep(1);
     socket_write($c, "Hello, {$i}");
   }
   socket_close($c);
   print "Received Connection from $raddr:$rport\n";
}
socket_close($sock);
