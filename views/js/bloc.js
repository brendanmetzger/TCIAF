/* A format method to string objects. Will replace all {key}
 * with the corresponding arguments. If key is an integer, then String.format
 * becomes variadic. If the key is text, then the function can use an object
 * provided the first argument is an object
 */

String.prototype.format = function() {
  var args = typeof arguments[0] === 'object' ? arguments[0] : arguments;
  return this.replace(/{((?:\d+)|(?:[a-z]+))}/g, function(match, key) {
    return typeof args[key] != 'undefined' ? args[key] : match;
  });
};

Event.prototype.theta = function () {
  var rect  = this.target.getBoundingClientRect();

  if (this.type.substring(0, 5) == 'touch') {
    var x = (this.touches[0].clientX - rect.left) - (rect.width / 2);
    var y = (rect.height / 2) - (this.touches[0].clientY - rect.top);
  } else {
    var x = (this.offsetX || this.layerX) - (rect.width / 2);
    var y = (rect.height / 2) - (this.offsetY || this.layerY);
  }
  var theta = Math.atan2(x, y) * (180 / Math.PI);
  return theta < 0 ? 360 + theta : theta;
};

if (!Element.prototype.matches && Element.prototype.msMatchesSelector) {
  Element.prototype.matches = Element.prototype.msMatchesSelector;
}

Date.prototype.parse = function (pattern) {
  var code = {
    h: ('0'+this.getUTCHours()).slice(-1),
    m: ('00'+this.getUTCMinutes()).slice(-2),
    s: ('00'+this.getSeconds()).slice(-2)
  };
  return pattern ? pattern.format(code) : code;
};


Element.prototype['@'] = function (obj) {
  for (var prop in obj) {
    this.setAttribute(prop, obj[prop]);
  }
  return this;
};

/* Allow looping through NodeLists akin to arrays.
 * (Nodelists are returned from querySelectorAll and other DOM stuff)
 */
NodeList.prototype.forEach = Array.prototype.forEach;


/* Animation function
 */
var Animate = function (callback) {
  return {
    animations: [],
    tween: function (element, idx) {
      if (callback.call(this.animations[idx], element)) {
        requestAnimationFrame(this.tween.bind(this, element, idx));
      } else {
        if (this.animations[idx].hasOwnProperty('finish')) {
          this.animations[idx].finish(element);
        }
      }
    },
    start: function (element, timer) {
      timer.start = Date.now();
      var idx = this.animations.push(timer) - 1;
      this.tween(element, idx);
      return {
        stop: function () {
          this.animations[idx].duration = 0;
        }.bind(this)
      };
    }
  };
};

var smoothScroll = function (elem) {
  var scrolling = {stop: new Function()};
  var scroller = Animate(function (element) {
    var ratio = Math.min(1, 1 - Math.pow(1 - (Date.now() - this.start) / this.duration, 5)); // float % anim complete
    var y = ratio >= 1 ? this.to : ( ratio * ( this.to - this.from ) ) + this.from;
    element.scrollTop =  y;
    return (ratio < 1);
  });
  var cancelTransition = function (evt) {
    scrolling.stop();
    document.body.removeEventListener('touchmove', cancelTransition, false);
  };
  return {
    scroll: function (end, seconds) {
      // document.body.addEventListener('mousemove', cancelTransition, false);
      scrolling = scroller.start(elem, {
        from: elem.scrollTop,
        to: end,
        duration: seconds,
        finish: function (something) {
          // document.body.removeEventListener('touchmove', cancelTransition, false);
        }
      });
    }
  };
};


var Request = function (callbacks, timeout) {
  this.request = new XMLHttpRequest();
  this.request.overrideMimeType('text/xml');
  this.request.timeout = timeout || 5000;
  for (var action in callbacks) {
    this.request.addEventListener(action, callbacks[action].bind(this), false);
  }
  return this;
};

Request.prototype = {
  get: function (url) {
    this.make('GET', url);
  },
  post: function (url) {
    this.make('POST', url);
  },
  make: function (type, url) {
    this.url = url;
    this.request.open(type, url);
    this.request.send();
    this.benchmark = performance.now();
  }
};


var SVG = function (node, width, height) {
  this.element = this.createElement('svg', {
    'xmlns:xlink': 'http://www.w3.org/1999/xlink',
    'xmlns': 'http://www.w3.org/2000/svg',
    'version': 1.1,
    'viewBox': '0 0 ' + width + ' ' + height
  }, node);
  this.point = this.element.createSVGPoint();
};

SVG.prototype.createElement = function(name, opt, parent) {
  var node = document.createElementNS('http://www.w3.org/2000/svg', name);
  for (var key in opt) node.setAttribute(key, opt[key]);
  return parent === null ? node : (parent || this.element).appendChild(node);
};

// Get point in global SVG space
SVG.prototype.cursorPoint = function(evt){
  this.point.x = evt.clientX; this.point.y = evt.clientY;
  return this.point.matrixTransform(this.element.getScreenCTM().inverse());
}





var Search = function (container, data) {

  this.input = document.getElementById(data.id);
  if (!this.input) {
    console.error("No valid search input");
    return;
  }

  this.menu = new Menu(this.input.parentNode.insertBefore(document.createElement('ul'), this.input.nextSibling));
  this.menu.list.addEventListener('click', function (evt) {
    if (evt.target.nodeName.toLowerCase() === 'li') {
      this.menu.index = 1;
      this.input.value       = evt.target.textContent;
      this.input.dataset.id  = evt.target.id;
      this.select(evt);
    }
  }.bind(this), false);

  this.subscribers = {
    select: []
  };

  this.input.addEventListener('keyup',   this.checkUp.bind(this),   false);
  this.input.addEventListener('keydown', this.checkDown.bind(this), false);
};


Search.instance = null;
Search.prototype = {
  results: null,
  indices: {},
  command: {up: -1, down: 1, enter: 'select', arrowdown:1, arrowup: -1, escape: 'reset' },
  request: function (path, topics, callback) {

    return topics.map(function (topic) {
      var req = new XMLHttpRequest;
      req.addEventListener('load', callback.bind(this, topic), false);
      req.open('GET', path.format(topic));
      req.send();
      return req;
    }, this);
  },
  reset: function (evt) {
    this.input.value = '';
    this.input.blur();
    this.indices = {};
    this.menu.reset();
  },
  select: function (evt) {
    if (evt) {
      evt.preventDefault();
      evt.stopPropagation();
    }

    this.input.dataset.text  = this.input.value;
    this.input.dataset.index = this.menu.index;

    this.subscribers.select.forEach(function (item) {
      item.call(this, this.input.dataset, evt);
    }, this);
    this.reset();
  },
  processIndices: function (group, evt) {
    (JSON.parse(evt.target.responseText) || []).forEach(function (item) {
      var key = item[1].toLowerCase().replace(/[^a-z0-9]/g, '');
      this[key] = {
        id: item[0],
        name: item[1],
        group: group
      };
    }, this.indices);

    this.processMatches();
  },
  processMatches: function () {
    var term = this.input.value.replace(/\s(?=[a-z0-9]{1,})/ig, '|\\b').replace(/\s[a-z0-9]?/ig, '');
    // var term     = this.input.value;
    var match_re = new RegExp(term.toLowerCase().replace(/[&+]/g, 'and').replace(/[.,"':?#\[\]\(\)\-]*/g, ''), 'i');
    var item_re  = new RegExp("("+term+")", 'ig');
    for (var key in this.indices) {

      if (match_re.test(key)) {
        var matches = this.indices[key].name.match(item_re);
        this.menu.addItem(
          this.indices[key].id,
          this.indices[key].name.replace(item_re, "<strong>$1</strong>"),
          (matches ? matches.length + (matches.join().length / this.indices[key].name.length): 0),
          this.indices[key].group
        );

        if (this.menu.list.children.length >= 25) break;
      }
      this.menu.sort();
    }
  },
  checkUp: function (evt) {
    var key = evt.key || evt.keyIdentifier;
    var meta  = this.command[key.toLowerCase()];
    if (meta) {
      if (isNaN(meta)) {
        this[meta](evt);
      }
      return
    }

    this.menu.reset();
    this.input.dataset.id = '';

    if (this.input.value.length < 1) {
      this.menu.reset();
      return;
    }
    this.processMatches();
  },
  checkDown: function (evt) {
    var key = evt.key || evt.keyIdentifier;
    var meta  = this.command[key.toLowerCase()];

    if (meta) {
      evt.preventDefault();
      // Cycle through list if up/down key is hit
      if (this.menu.items.length > 0 && Math.abs(meta) === 1) {
        var current_highlight    = this.menu.cycle(meta);
        this.input.value         = current_highlight.textContent;
        this.input.dataset.id    = current_highlight.id;
        this.input.dataset.group = current_highlight.classList.item(0);
      }
      return;
    }
    // else check to see if we should find search data
    var letter = String.fromCharCode(evt.keyCode);
    if (this.input.value.length === 0 && /[a-z0-9]{1}/i.test(letter)) {
      var path = '/{0}/{1}/{2}.json?q={3}'.format(this.input.dataset.path, '{0}', letter, Date.now());
      this.request(path, this.input.dataset.topic.split(/,\s*/), this.processIndices.bind(this));
    }
  }
};


var Menu = function (list) {
  this.list = list;
  this.list.classList.add('plain');
};

Menu.prototype = {
  list: null,
  items: [],
  index: -1,
  reset: function () {
    while (this.list.firstChild) this.list.removeChild(this.list.firstChild);
    this.items = [];
    this.index = -1;
  },
  addItem: function (id, html, weight, group) {
    var li = this.list.appendChild(document.createElement('li'));
        li.innerHTML = html;
        li.weight    = weight;
        li.id        = id;
        li.className = group;
  },
  sort: function () {
    this.items = [].slice.call(this.list.children, 0).sort(function (a, b) {
      return b.weight - a.weight;
    }).map(function (item) {
      return this.list.appendChild(item);
    }, this);
  },
  cycle: function (direction) {
    if (this.index >= 0) this.items[this.index].classList.remove('highlight');

    this.index = (this.index + this.items.length + direction) % this.items.length;

    var current = this.items[this.index];
        current.classList.add('highlight');

    return current;
  }
};


// Progress/Patience
var Progress = function(container) {
  var svg, path, handle, message;
  this.element = document.createElement('div');
  this.element.className = 'progress';
  if (container) {
    container.appendChild(this.element);
    this.remove = function () {
      container.removeChild(this.element);
    };
  }
  message = this.element.appendChild(document.createElement('span'));
  svg = new SVG(this.element, 100, 100);
  svg.createElement('circle', { 'cx': 50, 'cy': 50, 'r': 40 });
  path   = svg.createElement('path', { 'd': 'M50,50', 'class': 'status', 'transform': 'rotate(-90 50 50)' });
  handle = svg.createElement('path', { 'd': 'M50,50', 'class': 'handle', 'transform': 'rotate(-90 50 50)'});
  grab   = svg.createElement('circle', { 'cx': 50, 'cy': 50, 'r': 5, 'class': 'grab', 'transform': 'rotate(-90 50 50)'});

  this.update = function (percentage, text, scrub) {
    message.innerHTML = text || message.innerHTML;
    var radian = (2 * Math.PI) * percentage;
    var x = (Math.cos(radian) * 40) + 50;
    var y = (Math.sin(radian) * 40) + 50;

    var data = "M90,50A40,40 0 " + (y < 50 ? 1 : 0) + "1 " + x + "," + y;

    if (scrub) {
      handle.setAttribute('d', data + 'L50,50z');
      handle.position = percentage;
      grab.setAttribute('cx', x);
      grab.setAttribute('cy', y);
    } else {
      path.setAttribute('d', data);
    }
  };

  this.position = function () {
    return handle.position;
  };

  this.setState = function (state) {
    this.element.dataset.state = state;
    if (state == 'waiting') {
      message.innerHTML = "...one<br/>moment";
    }
  };

  return this;
};


if (window.history.pushState) {

  var Content = new Request({
    load: function (evt) {
      var doc = evt.target.responseXML;
      if (! doc) {
        var parser = new DOMParser();
        doc = parser.parseFromString(evt.target.responseText, "application/xml");
        if (! doc) {
          evt.target.dispatchEvent(new ProgressEvent('error'));
        }
      }

      document.querySelectorAll('head title, head style[type]').forEach(function(node) {
        document.head.removeChild(node);
      });

      doc.querySelectorAll('head title, head style').forEach(function (node) {
        document.head.appendChild(node);
      });

      doc.documentElement.querySelectorAll('body script[async]').forEach(function (script) {
        eval(script.text);
      });

      var main = document.body.querySelector('main');
      main.parentNode.replaceChild(doc.querySelector('main'), main);

      document.body.className = doc.querySelector('body').getAttribute('class') + ' transition';
      setTimeout(function () {
        document.body.classList.remove('transition');
        [].forEach.call(document.querySelectorAll('main a[href="'+window.location.pathname+'"]'), function (a) {
          a.classList.add('selected');
        });
      }, 10);

      // track page timings in analytics
      ga('send', 'timing', 'XHR Request', 'load', Math.round(performance.now() - this.benchmark));
      // if
      bloc.load(true);
    },
    timeout: function (evt) {
      alert('This page is taking a bit long... either our server is struggling or your internet connection is. Please try again!');
      ga('send', 'event', 'Error', 'timeout', this.url.replace(/.*\.org/, ''));
      document.body.classList.remove('transition');
    },
    error: function (evt) {
      // should just redirect
      console.error('FIX this now! look at console..', navigator.onLine, evt);
      var message = "Your browser is offline.";

      if (navigator.onLine) {
        message = "We were unable to fulfill that request. Please try again.";
      }
      alert(message);
      document.body.classList.remove('transition');
      // console.dir(evt.target);
      // console.log(this);
    }
  }, 8500);

  var adjust = function (num) {
    return Math.round((parseFloat(num, 10) + (Math.cos(Math.random() * 2 * Math.PI) * 25))) + '%';
  };

  window.navigateToPage = function (evt) {

    var append = this.href.match(/\?/) ? '' : '?ref='+btoa(window.location.pathname);

    if (evt.type != 'popstate') {
      setTimeout(function () {
        document.body.scrollTop = 0;
        document.querySelector('#browse').scrollTop = 0;
      }, 150);
      history.pushState(null, null, this.href);
    } else if (evt.timeStamp > window.dataLayer[0].start && evt.timeStamp - window.dataLayer[0].start < 1000) {
      // Safari consistently fires this event on load, which refreshes the page.
      // This checks the event time vs. the recorded start and avoid it... hack.
      return;
    };


    Content.get(this.href + append);
    ga('send', 'pageview');
    document.body.classList.add('transition');
    var style = getComputedStyle(document.body);
    document.body.style.backgroundSize = style.backgroundSize.match(/([0-9\-\.]+)/g).map(adjust).join(', ');
    var pos = style.backgroundPosition.match(/([0-9\-\.]+)/g).map(adjust);
    document.body.style.backgroundPosition = [pos[0] + ' ' + pos[1],pos[2] +' ' + pos[3]].join(', ');
  };

  document.body.addEventListener('click', function (evt) {
    if (evt.target.nodeName.toLowerCase() === 'a') {
      if (evt.target.hash) {
        evt.preventDefault();
        var elem = document.getElementById(evt.target.hash.substr(1));
        if (elem) {
          window.Adjust.scroll(+elem.offsetTop, 500);
        }
      } else if (evt.target.matches("a:not(.button)[href^='/']")) {
        evt.preventDefault();


        navigateToPage.call(evt.target, evt);
      } else if (evt.target.matches("a[href^='http']")) {
        evt.preventDefault();
        window.open(evt.target.href);
      }
    }
  }, true);
} else {
  window.navigateToPage = function (evt) {
    document.location.assign(this.href);
  };
}


function toggleStatus(evt) {
  document.body.dataset.status = evt.type;
}

function quickPlay(active, evt) {
  active.style.opacity = 0.15;
  evt.preventDefault();
  var button = document.querySelector('span[class] > button.listen');
  console.log(button);
  var click = document.createEvent('MouseEvents');
  click.initEvent('click', true, true);
  button.dispatchEvent(click);
}

var reveal = function () {
  if (this.classList.contains('proxy')) {
    this.parentNode.style.backgroundImage = `url(${this.src})`;
    this.remove();
  } else {
    this.removeAttribute('data-src');
  }
}

bloc.init(function () {
  var browse = document.querySelector('#browse');
  window.Adjust = smoothScroll(browse);
  window.lazyload = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.addEventListener('load', reveal);
        entry.target.src = entry.target.dataset.src;
        window.lazyload.unobserve(entry.target);
      }
    });
  }, {
    rootMargin: '0px',
    root: browse,
    threshold: [0, 0.5, 1]
  }) : false;
  
  window.addEventListener('popstate', navigateToPage.bind(document.location), false);
  window.addEventListener('offline', toggleStatus);
  window.addEventListener('online', toggleStatus);
});


bloc.init(bloc.define('autoload', function () {

  document.querySelectorAll('noscript').forEach(function (elem) {
    var swap = document.createElement('div');
    elem.parentNode.replaceChild(swap, elem);
    try {
      var module = bloc.module(elem.id);
      if('call' in module) module(new window[elem.className](swap, elem.dataset, elem.textContent));
    } catch(e) {
      console.log(e, elem.id);
    }
  });
  
  return this;
}), 'unshift');

bloc.define('site-search', function (instance) {
  instance.subscribers.select.push(function (dataset, evt) {
    document.body.dataset.view = 'browse';

    if (Number(dataset.index) < 0) {
      url = '/search/full?q=' + dataset.text;
      type = 'full';
    } else {
      url = '/explore/detail/'+dataset.id;
      type = 'filter';
    }
    ga('send', 'event', 'Search', type, dataset.text);
    navigateToPage.call({href: url}, evt);
  });
});

// _Capitalized word indicates an instance of an object


function Track(config) {
  this.config = config;
  this.callback = function(){};
}

Track.prototype = {
  set element(node){
    node.appendChild(document.createElement('span')).textContent = this.title;
    this._element = node;
  },
  set state(state) {
    this.element.className = state;
  },
  get state() {
    return this.element.className;
  },
  get element() {
    return this._element || document.createElement('li');
  },
  get id() {
    return this.config.id;
  },
  get src() {
    return this.config.src;
  },
  get title() {
    return this.config.title;
  }
};

var Playlist = function (player, attributes) {
  this.tracks  = {};
  this.pointer = null;
  this.player  = player;
  this.element = player.container.appendChild(document.createElement('ul')['@'](attributes));
  this.element.addEventListener('click', this.select.bind(this));
  this.scroller = smoothScroll(this.element);
};

Playlist.prototype = {
  select: function (evt) {
    // if we are on the ceurrent event
    console.log(evt.target.id);
    if (evt.target.id === this.pointer) {
      // if we are clicking the button that is playing, toggle play/pause
      this.player[this.player.audio.paused ? 'play' : 'pause']();
      return;
    }

    if (! this.player.audio.paused) {
      this.current.state = 'played';
      this.player.pause();
    }

    this.pointer = evt.target.id;
    this.player.play();
    this.current.callback(evt);
  },
  next: function (idx) {
    var track = this.tracks[this.pointer];
    track.state = 'played';
    if (track.element.nextSibling) {
      console.info('Attempting to play next track');
      this.pointer = track.element.nextSibling.id;
      return true;
    }
    this.pointer = this.element.firstElementChild.id;
    return false;
  },
  get current() {
    if (this.pointer) {
      return this.tracks[this.pointer];
    } else {
      console.info("Playlist pointer is empty.");
    }
  },
  add: function (track) {
    if (! this.tracks.hasOwnProperty(track.id)) {
      this.tracks[track.id] = track;
      track.element = this.element.appendChild(document.createElement('li')['@']({id: track.id}));
    }
  },
  // not a real queue, as some elements bay be skipped
  remove: function (track) {
    try {
      this.element.removeChild(track.element);
      delete this.tracks[track.id];
    } catch (e) {
      console.error(e, this.element, track);
    }
  },
  clearUnplayed: function () {
    for (var track in this.tracks) {
      if (this.tracks[track].element.classList.contains('played')) {
        this.remove(this.tracks[track]);
      };
    }
  }
};

var Player = function (container, data, message) {
  this.container = container;
  this.container.id  = 'Player';

  this.elements = [];
  this.index    = 0;

  var toggle = this.container.appendChild(document.createElement('button')['@']({
    'class': 'toggle',
  }));

  toggle.textContent = '✕';

  toggle.addEventListener('click', function (evt) {
    document.body.dataset.view = document.body.dataset.view == 'browse' ? 'media' : 'browse';
  });

  var controls = this.container.appendChild(document.createElement('div')['@']({
    'class': data.controls
  }));

  var button = controls.appendChild(document.createElement('button')['@']({
    'type': 'button'
  }));

  this.playlist = new Playlist(this, {'class': data.playlist});
  this.button   = new Button(button, 'play');
  this.meter    = new Progress(controls);

  this.audio    = controls.appendChild(document.createElement('audio'));

  ['ended','timeupdate','error','seeked','seeking','playing','waiting'].forEach(function (event) {
    this.audio.addEventListener(event, this[event].bind(this), false);
  }, this);

  this.button.press(function(evt) {
    evt.preventDefault();
    this[this.button.state].call(this);
  }.bind(this));

  this.meter.element.addEventListener(mobile ? 'touchstart' : 'mouseover', function (evt) {
    this.meter.element.classList.add('hover');
    this.meter.update(evt.theta() / 360, null, true);
    document.documentElement.classList.add('lock');
  }.bind(this), mobile ? {passive: true} : false);

  this.meter.element.addEventListener(mobile ? 'touchend' : 'mouseout', function (evt) {
    this.meter.element.classList.remove('hover');
    document.documentElement.classList.remove('lock');
  }.bind(this), mobile ? {passive: true} : false);

  this.meter.element.addEventListener(mobile ? 'touchmove' : 'mousemove', function (evt) {
    evt.preventDefault();
    evt.stopPropagation();
    var p  = evt.theta() / 360;
    var d = this.audio.duration * 1e3;
    var t = d * (1 - p);
    var m = "<time>{h}:{m}:{s}</time>";
    this.meter.update(p, new Date(d-t).parse(m) + new Date(t).parse(m), true);
  }.bind(this), mobile ? {passive: true} : false);

  this.meter.element.addEventListener(mobile ? 'touchend' : 'click', function (evt) {
    ga('send', 'event', 'Audio', 'scrub', this.playlist.current.id);
    this.meter.element.classList.remove('hover');
    this.audio.currentTime = this.audio.duration * (evt.type == 'touchend' ? this.meter.position() : (evt.theta() / 360));
  }.bind(this), mobile ? {passive: true} : false);

};


Player.prototype = {
  stylesheet: null,
  cleanup: function () {
    var player = bloc.module('Player');
    player.pause();
    delete player;
  },
  play: function () {
    var track = this.playlist.current;
    if (this.audio.src != track.src) {
      this.audio.src = track.src;
      this.container.dataset.position = track.position;
      this.playlist.scroller.scroll(track.element.offsetTop - this.playlist.element.offsetTop, 1000);
      this.pause();
    }
    this.css = document.head.appendChild(document.createElement('style'))
    this.css.sheet.insertRule('.'+track.id+' button {background-position:50% 87.5%;cursor:default;opacity:0.9;box-shadow:none;background-color:rgba(255,255,255,0.75) !important;border-color:#fff;mix-blend-mode:normal !important;}', 0);
    this.css.sheet.insertRule('li#'+track.id+' {border-color:#5B9B98;background-color:#5B9B98;color:#fff;}', 1);

    this.audio.play();
    ga('send', 'event', 'Audio', 'play', track.id);
  },
  pause: function () {
    if (this.css) {
      this.css.parentNode.removeChild(this.css);
      this.css = null;
    }
    this.button.setState('play');
    this.audio.pause();
    ga('send', 'event', 'Audio', 'pause', this.playlist.current.id);
  },
  ended: function (evt) {
    this.pause();
    ga('send', 'event', 'Audio', 'finished', this.playlist.current.id);
    if (this.playlist.next()) {
      this.play();
    }

  },
  playing: function (evt) {
    this.button.setState('pause');
    this.meter.setState('playing');
  },
  waiting: function (evt) {
    this.meter.setState('waiting');
  },
  seeking: function (evt) {
    this.meter.setState('waiting');
  },
  seeked: function (evt) {
    // animate here
    this.button.setState('pause');
    this.meter.setState('playing');
  },
  error: function (evt) {
    ga('send', 'event', 'Audio', 'error', this.playlist.current.id);
    console.log('error', evt);
  },
  timeupdate: function (evt) {
    if (this.meter.element.classList.contains('hover')) return;
    var elem = evt.target;
    var t = Math.ceil(elem.currentTime) * 1e3;
    var d = Math.ceil(elem.duration) * 1e3;
    var m = "<time>{h}:{m}:{s}</time>";
    this.meter.update(t/d, new Date(t).parse(m) + new Date(d-t).parse(m));
  },
};


function loadButtonAudio(button, evt) {
  evt.preventDefault();
  var player = bloc.module('Player');

  // a trick, if the button has a border color of white, it's active, don't load it.
  if (window.getComputedStyle(button).getPropertyValue('opacity') < 1) {
    player.pause();
    console.info('TODO: if on a new page and beginning the play/pause, make sure after pausing we do not reload the same track if it is in the queue');
    return false;
  }


  var selected = button.parentNode.querySelector('audio');
  player.playlist.clearUnplayed();

  document.querySelectorAll('main audio').forEach(function (audio) {
    // select the button that was responsible for playing this track
    var aux_button = audio.parentNode.querySelector('button.listen');
    aux_button.classList.add('parsed');

    var track = new Track({
      id: audio.parentNode.className,
      src: audio.src,
      title: audio.title
    });

    // add to the playlist: figure out what to
    // do if in the playlist vs not in the playlist.
    player.playlist.add(track);

    // this probably isn't important anymore, elim. condition
    // perhaps set callback as part of config to track obj.
    if (aux_button && aux_button != button) {
      track.callback = function (evt) {
        // proxy a click to the playlist.
        navigateToPage.call({href: audio.dataset.ref}, evt);
      };
      aux_button.removeAttribute('onclick');
      aux_button.addEventListener('click', player.playlist.select.bind(player.playlist, {target: {id: track.id}}));
    }


    if (selected === audio) {
      // set this to be selected
      player.playlist.pointer = track.id;
    }

    audio.parentNode.removeChild(audio);

  });

  player.play();
  return false;
}


var Button = function (button, state) {
  var svg, indicator, animate, states, scale, g;
  this.state = state || 'play';

  svg = new SVG(button, 45, 45);

  states = {
    play:  'M11,7.5 l0,30 l12.5,-8 l0-14 l-12.5,-8 m12.5,8 l0,14 l12.5,-7 l0,0  z',
    pause: 'M11.5,10 l0,25l10,0 l0-25l-10,0   m12,0  l0,25l10,0 l0,-25z',
    error: 'M16,10 l10,0l-3,20  l-3,0l-3,-20  m3,22  l4,0 l0,4    l-4,0 z',
    wait:  'M521.5,21.5A500,500 0 1 1 427.0084971874736,-271.39262614623664'
  };

  st2 = {
    play: [['m',1,7.5],['l',0,30], ['l', 12.5,-8],['l',0,-14],['l',-12.5,-8],['m', 12.5, 8 ], ['l',0,14 ],['l',12.5,-7], ['l',0,0], ['z']],
    pause: [],
    error: [],
    wait: []
  };

  this.factor = 1;

  this.press = function (callback) {
    button.addEventListener(mobile ? 'touchend' : 'click', callback);
  };

  // states match the d
  this.setState = function (state, e) {
    if (state === this.state) {
      return;
    }
    if (state != 'wait') {
      indicator.setAttribute('d', states[this.state]);


      animate.setAttribute('from', states[this.state]);
      animate.setAttribute('to', states[state]);

      animate.beginElement();

      this.state = state;
      if (this.factor !== 1) {
        this.zoom(1, this.factor);
        this.factor = 1;
      }
    } else if (this.factor !== 0.2) {
      this.zoom(0.02, this.factor);
      this.factor = 0.02;
    }

    // Delay this, just for appearance
    setTimeout(function () {
      button.className = state;
    }, 150);

  };

  g = svg.createElement('g', {
    transform: 'scale(1) translate(0,0)'
  });

  indicator = svg.createElement('path', {
    'd': states[this.state],
    'class': 'indicator'
  }, g);

  svg.createElement('path', {
    'd': states.wait,
    'stroke': '#000',
    'stroke-width':35,
    'class': 'wait'
  }, g);



  animate = svg.createElement('animate', {
    attributeName: 'd',
    attributeType: 'XML',
    from: states.play,
    to: states.pause,
    dur: '0.25s',
    begin: 'indefinite',
    fill: 'freeze'
  }, indicator);

  if (! animate.beginElement) {
    animate.beginElement = function () {
      indicator.setAttribute('d', animate.getAttribute('to'));
    };
  }

  this.zoom = function (from, to) {
    requestAnimationFrame(transition.bind(g, Date.now(), function (begin) {
      var ratio = (Date.now() - begin) / 500; // float % animation complete
      var scale = ratio >= 1 ? from : Math.pow(ratio * (from - to), 5) + to;
      var translate = (22.5 / scale) - 22.5;
      this.setAttribute('transform', 'scale({0}) translate({1}, {1})'.format(scale, translate));
      return (ratio < 1);
    }));
  };
  this.setState(this.state);
};
