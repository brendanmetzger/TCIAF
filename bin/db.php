<?php
$sock = socket_create_listen(0);
socket_getsockname($sock, $addr, $port);
print "Server Listening on $addr:$port\n";
$fp = fopen('/tmp/sock-play.txt', 'w');
fwrite($fp, $port);
fclose($fp);
while($c = socket_accept($sock)) {
   /* do something useful */
   socket_getpeername($c, $raddr, $rport);
   print "Received Connection from $raddr:$rport\n";
}
socket_close($sock);
