<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta charset="utf-8" />
    <meta name="msapplication-tap-highlight" content="no" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1" />
    <meta name="apple-mobile-web-app-capable" content="no"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="Third Coast International Audio Festival"/>
    <link rel="shortcut icon" href="/images/favicon.ico"/>

    <link rel="stylesheet" type="text/css" href="css/tciaf.css" />
    <title>Listen Live or Rebroadcast with the Chicago Public Media Web Player</title>
    <script type="text/javascript">
      // <![CDATA[

      window.bootstrap = (function(d,w,c,q,t) {
        return { 
          tag: function (src, async) {var s = d.createElement('script');s.type = 'text/javascript';s.src = src;s.async = async;return s;},
          embed: function() {for (var i=0; i < arguments.length; i++) {var s = this.tag(arguments[i], false);s.onload = this.load; q.push(s);} return this;},
          track: function (src) { for (var i=0; i < arguments.length; i++) {t.push(this.tag(arguments[i], true));} return this;},
          load: function(){ if (q.length > 0) {d.body.appendChild(q.shift()); } else { w.bootstrap.init();}},
          init: function() {w._sf_endpt = (new Date()).getTime(); c.forEach(function (f) { f.call();}); c=[]; while(t.length > 0) d.body.appendChild(t.shift());},
          queue: function(f) {if (typeof f === 'function') c.unshift(f);}
      };}(document, window, [], [], []));
      
      bootstrap.embed('/js/bloc.js');
      // ]]>
    </script>
  </head>
  <body class="unloaded">
    <header>
      <h1>Third Coast International Audio Festival</h1>
    </header>
    
    <section>
      <p>Hello.</p>
      <div id="test">
        
      </div>
      <script type="text/javascript">
        bootstrap.queue(function () {
          var s = new SVG(document.getElementById('test'), {height:500,width:500,viewbox:'100 100 0 0'});
          s.createElement('path', {
            d: 'M50,50A25,25,0,1,1,100,100',
            //rx ry x-axis-rotation large-arc-flag sweep-flag x y)+
            stroke:'#000',
            'stroke-width':10,
            fill: 'none'
          });
        });
      </script>
    </section>
    
    <footer>      
    </footer>
    
    <script type="text/javascript">
      window.bootstrap.load();
    </script>
  </body>
</html>
