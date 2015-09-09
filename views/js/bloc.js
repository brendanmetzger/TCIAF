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
  var theta = Math.atan2((this.offsetX || this.layerX) - (rect.width / 2), (rect.height / 2) - (this.offsetY || this.layerY)) * (180 / Math.PI);
  return theta < 0 ? 360 + theta : theta;
};

if (!Element.prototype.matches && Element.prototype.msMatchesSelector) {
  Element.prototype.matches = Element.prototype.msMatchesSelector;
}




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
        // console.log(this.timer);
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

window.Adjust = function () {
  var scroller = Animate(function (element) {
    var ratio = Math.min(1, 1 - Math.pow(1 - (Date.now() - this.start) / this.duration, 5)); // float % anim complete 

    var y = ratio >= 1 ? this.to : ( ratio * ( this.to - this.from ) ) + this.from;
  
  
    // element.setAttribute('x', x);
    element.scrollTo(0,y);
    return (ratio < 1);
  
  });
  
  return {
    scroll: function (end, seconds) {
      scroller.start(window, {
        from: window.pageYOffset,
        to: end,
        duration: seconds
      });
    }
  };
}();


var Request = function (callbacks) {
  this.request = new XMLHttpRequest();
  for (var action in callbacks) {
    this.request.addEventListener(action, callbacks[action].bind(this), false);
  }
  return this;
};

Request.prototype = {
  get: function (url) {
    this.request.open('GET', url);
    this.request.send();
  },
  post: function (url) {
    this.request.open('POST', url);
    this.request.send();
  }
};


/* Quick way to create an SVG element with and a prototypal method
 * to create children elements. Used in Progress and Player.Button
 */ 
var SVG = function (node, options) {
  options['xmlns:xlink'] = 'http://www.w3.org/1999/xlink';
  options.xmlns = 'http://www.w3.org/2000/svg';
  options.version = 1.1;
  this.element = this.createElement('svg', options, node);
};

SVG.prototype.createElement = function(name, opt, parent) {
  var node = document.createElementNS('http://www.w3.org/2000/svg', name);
  for (var key in opt) {
    node.setAttribute(key, opt[key]);
  }
  if (parent === null) {
    return node;
  }
  return (parent || this.element).appendChild(node);
};

SVG.prototype.b64url = function (styles) {
  var wrapper     = document.createElement('div');
  var clone       = wrapper.appendChild(this.element.cloneNode(true));
  var style = this.createElement('style', null, clone);
      style.textContent = styles;
  return 'url(data:image/svg+xml;base64,'+btoa(wrapper.innerHTML)+')';
};



var Player = function (container, data) {
  container.id = 'Player';
  
  var button = container.appendChild(document.createElement('button'));
      button.setAttribute('type', 'button');

  this.button = new Button(button, 'play');

  var button_activate = function (evt) {
    evt.preventDefault();
    this[this.button.state].call(this);
  }.bind(this);

  this.button.getDOMButton().addEventListener('touchend', button_activate, false);
  this.button.getDOMButton().addEventListener('click', button_activate, false);
  
  this.meter = new Progress(container);

  var tick = function (evt) {
    this.update((evt.theta() / 360), null, true);
  }.bind(this.meter);
  
  this.meter.element.addEventListener('mouseover', function () {
    this.addEventListener('mousemove', tick, false);
  }.bind(this.meter.element));
  
  this.meter.element.addEventListener('mouseout', function () {
    this.removeEventListener('mousemove', tick, false);
  }.bind(this.meter.element));
  
  
  
  this.meter.element.addEventListener('click', function (evt) {
    var audio = this.elements[this.index];
    audio.currentTime = audio.duration * (evt.theta() / 360);
  }.bind(this), false);
  
  
  
  this.display = container.appendChild(document.createElement('section'));
  this.display.className = "display";

  // this.display.title  = display.appendChild(document.createElement('h2'));
  // this.display.byline = display.appendChild(document.createElement('p'));
};



Player.prototype = {
  elements: [],
  display: {},
  index: 0,
  button: null,
  setDisplay: function (value) {
    this.display.innerHTML = value;
  },
  progress: function (evt) {
    if (evt.target.paused) {
      this.meter.update(evt.target.buffered.end(0) / evt.target.duration, null, true);
    }
  },
  play: function () {
    this.button.setState('pause');
    this.elements[this.index].play();
  },
  pause: function () {
    this.button.setState('play');
    this.elements[this.index].pause();
  },
  playTrack: function (index) {
    this.pause();
    this.index = index;
    this.play();
  },
  ended: function (evt) {
    var next = this.index+1;
    if (next < this.elements.length) {
      this.playTrack(next);
    }
  },
  playing: function (evt) {
    console.log('playing');
  },
  waiting: function (evt) {
    console.log('waiting', evt);
  },
  seeking: function (evt) {
    console.log('seeking');
  },
  seeked: function (evt) {
    console.log('seeked');
  },
  stalled: function (evt) {
    console.log('stalled', evt);
  },
  error: function (evt) {
    console.log('error', evt);
  },
  timeupdate: function (evt) {
    var elem = evt.target;
    var time = Math.ceil(elem.currentTime);
    var dur  = Math.ceil(elem.duration);
    var msg = "<span>{m}:{s}</span>";
    
    this.meter.update(time / dur, msg.format(this.timecode(new Date(time*1e3))) + msg.format(this.timecode(new Date((dur-time)*1e3))));

  },
  timecode: function (timestamp) {
    return {
      h: ('00'+timestamp.getUTCHours()).slice(-2),
      m: ('00'+timestamp.getUTCMinutes()).slice(-2),
      s: ('00'+timestamp.getSeconds()).slice(-2)
    };
  },
  attach: function (audio_element) {
    if (audio_element.nodeName === "AUDIO") {
      document.body.appendChild(audio_element);
      audio_element.dataset.index = this.elements.push(audio_element) - 1;
      audio_element.removeAttribute('controls');
      ['progress','ended', 'stalled', 'timeupdate', 'error','seeked','seeking','playing','waiting'].forEach(function (trigger) {
        audio_element.addEventListener(trigger, this[trigger].bind(this), false);
      }.bind(this));
      this.timeupdate({target: audio_element});
    }
  },
  detach: function (audio_element) {
    delete this.elements[audio_element.dataset.index];
  }
};


// should implement a controllable interface

var Button = function (button, state) {
  var svg, indicator, animate, states, scale, g;
  this.state = state || 'play';
  
  svg = new SVG(button, {
    height: 50,
    width: 50,
    viewBox: '0 0 45 45'
  });
  
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

  this.getDOMButton = function () {
    return button;
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


var Search = function (container, data) {
  
  this.input = document.getElementById(data.id);
  if (!this.input) {
    console.error("MUST fix the search going blank when reloading content");
    return;
  }
  this.input.addEventListener('keyup',   this.checkUp.bind(this),   false);
  this.input.addEventListener('keydown', this.checkDown.bind(this), false);
  

  this.ajax = new XMLHttpRequest();
  this.ajax.addEventListener('load', this.processIndices.bind(this), false);
  this.ajax.addEventListener('loadstart', this.reset.bind(this), false);

  this.menu = new Menu(this.input.parentNode.insertBefore(document.createElement('ul'), this.input.nextSibling));
  this.menu.list.addEventListener('click', function (evt) {
    if (evt.srcElement.nodeName === 'LI') {
      this.input.value       = evt.target.textContent;
      this.input.dataset.id  = evt.target.id;
      this.select(evt);
      
    }
  }.bind(this), false);
  
  this.subscribers = {
    'select': []
  };
};



Search.instance = null;
Search.prototype = {
  ajax: null,
  results: null,
  indices: {},
  find: function (path, topic, letter) {
    this.ajax.open('GET', '/' + path +'/' + topic + '/' + letter + '.json?ask=' + (new Date()).getTime() );
    this.ajax.send();
  },
  reset: function () {
    this.indices = {};
    this.menu.reset();
  },
  select: function (evt) {
    if (evt) {
      evt.preventDefault();
      evt.stopPropagation();
    }

    this.reset();
  
    this.subscribers.select.forEach(function (item) {
      item.call(this, this.input.dataset, evt);
    }, this);
  },
  processIndices: function (evt) {
    (JSON.parse(evt.target.responseText) || []).forEach(function (item) {
      var key = item[1].toLowerCase().replace(/[^a-z0-9]/g, '');
      this[key] = {
        id: item[0],
        name: item[1]
      };
    }, this.indices);
    
  },
  checkUp: function (evt) {
    var meta = evt.keyIdentifier.toLowerCase();

    if (meta === 'down' || meta == 'up') return;

    if (meta === 'enter') {
      this.select(evt);
      return;
    }
  
    this.menu.reset();
    this.input.dataset.id = '';
  
    if (this.input.value.length < 1) return;
    
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
          matches ? matches.length : 0
        );
        
        if (++this.menu.position >= 25) break;
      }
      
      this.menu.sort();
    }
  },
  checkDown: function (evt) {
    var letter = String.fromCharCode(evt.keyCode);
    var meta   = evt.keyIdentifier.toLowerCase();
    if (meta == 'enter') {
      evt.preventDefault();
      return;
    }
    if (this.menu.items.length > 0 && (meta === 'down' || meta == 'up')) {            
      evt.preventDefault();
      var current = this.menu.cycle(meta == 'down' ? 1 : -1);
      this.input.value       = current.textContent;
      this.input.dataset.id  = current.id;
      return;
    }

    if (this.input.value.length === 0 && /[a-z0-9]{1}/i.test(letter)) {
      this.find(this.input.dataset.path, this.input.dataset.topic, letter);
    }
  }
};


var Menu = function (list) {
  this.list = list;
};

Menu.prototype = {
  list: null,
  items: [],
  index: -1,
  position: 0,
  reset: function () {
    while (this.list.firstChild) {
      this.list.removeChild(this.list.firstChild);
    }    
    this.items = [];
    this.position = 0;
  },
  addItem: function (id, html, weight) {
    var li = this.list.appendChild(document.createElement('li'));
        li.innerHTML = html;
        li.weight = weight;
        li.id     = id;
  },
  sort: function () {
    
    this.items = Array.prototype.slice.call(this.list.querySelectorAll('li'), 0).sort(function (a, b) {
      return b.weight - a.weight;
    });
    
    this.items.forEach(function (item) {
      this.list.appendChild(item);
    }, this);
  },
  tick: function (direction) {
    direction = direction < 1 ? (this.position - 1) : 1;
    this.index = Math.abs((this.index + direction) % this.position);
    return this.index;
  },
  cycle: function (direction) {
    if (this.position > 0) {    

      if (this.index >= 0) {
        this.items[this.index].classList.remove('highlight');
      }
    
      var current = this.items[this.tick(direction)];
          current.classList.add('highlight');
      
      return current;
    }
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
  
  
  message = this.element.appendChild(document.createElement('strong'));
  

  svg = new SVG(this.element, {
    height: 50,
    width: 50,
    viewBox: '0 0 100 100'
  });
  
  svg.createElement('circle', {
    'cx': 50,
    'cy': 50,
    'r': 35
  });
  
  handle = svg.createElement('path', {
    'd': 'M50,50',
    'class': 'handle',
    'transform': 'rotate(-90 50 50)'
  });
  

  path = svg.createElement('path', {
    'd': 'M50,50',
    'transform': 'rotate(-90 50 50)'
  });
  
  
  this.update = function(percentage, text, mouseover) {
    message.innerHTML = text || message.innerHTML;
    
    var radian = (2 * Math.PI) * percentage;
    var x = (Math.cos(radian) * 35) + 50;
    var y = (Math.sin(radian) * 35) + 50;
    
    var data = "M85,50A35,35 0 " + (y < 50 ? 1 : 0) + "1 " + x + "," + y;
    if (mouseover) {
      handle.setAttribute('d', data);
    } else {
      path.setAttribute('d', data);
    }
  };
  
  

  return this;
};


if (window.history.pushState) {
  
  var Content = new Request({
    load: function (evt) {
      
      if (! evt.target.responseXML) {
        evt.target.dispatchEvent(new ProgressEvent('error'));
      }
      
      document.querySelectorAll('head title, head style').forEach(function(node) {
        document.head.removeChild(node);
      });
      
      evt.target.responseXML.querySelectorAll('head title, head style').forEach(function (node) {
        document.head.appendChild(node);
      });
      
      
      evt.target.responseXML.documentElement.querySelectorAll('body script[async]').forEach(function (script) {
        document.head.appendChild(window.bloc.tag(false)).text = script.text;
      });
      
      var main = document.body.querySelector('main');
      main.parentNode.replaceChild(evt.target.responseXML.querySelector('main'), main);
      
      
      document.body.className = evt.target.responseXML.querySelector('body').getAttribute('class');
      // if
      window.bloc.execute('autoload');
      window.bloc.execute('editables');
    },
    error: function (evt) {
      // should just redirect
      alert('FIX this now! look at console..');
      console.dir(evt.target);
      console.log(this);
      
    }
  });

  
  window.navigateToPage = function (evt) {
    window.Adjust.scroll(0, 500);
    
    if (evt.type != 'popstate') {
      history.pushState(null, null, this.href);
    }
    
    Content.get(this.href + '.xml');
    document.body.classList.add('transition');
  };
  
  document.body.addEventListener('click', function (evt) {
    if (evt.target.nodeName.toLowerCase() === 'a') {
      evt.preventDefault();
      if (evt.target.matches("a:not(.button)[href^='/']")) {
        navigateToPage.call(evt.target, evt);
      }
    }
  }, true);
} else {
  window.navigateToPage = function (evt) {
    window.location.href = this.href;
  };
}

bloc.prepare('onload', function () {
  window.addEventListener('popstate', navigateToPage.bind(document.location), false);
});