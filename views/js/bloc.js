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

Date.prototype.parse = function (pattern) {
  var code = {
    h: ('00'+this.getUTCHours()).slice(-2),
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

Element.prototype.insert = function (container) {
  container.appendChild(this);
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

window.Adjust = function () {
  var scrolling = {stop: new Function()};
  var scroller = Animate(function (element) {
    var ratio = Math.min(1, 1 - Math.pow(1 - (Date.now() - this.start) / this.duration, 5)); // float % anim complete
    var y = ratio >= 1 ? this.to : ( ratio * ( this.to - this.from ) ) + this.from;
    element.scroll(0, y);
    return (ratio < 1);

  });


  var cancelTransition = function (evt) {
    scrolling.stop();
    document.body.removeEventListener('touchmove', cancelTransition, false);
  };

  return {
    scroll: function (end, seconds) {
      // document.body.addEventListener('mousemove', cancelTransition, false);
      scrolling = scroller.start(window, {
        from: window.pageYOffset,
        to: end,
        duration: seconds,
        finish: function (something) {
          // document.body.removeEventListener('touchmove', cancelTransition, false);
        }
      });
    }
  };
}();

// window.addEventListener('scroll', function (evt) {
//   console.dir(evt);
// });


var Request = function (callbacks) {
  this.request = new XMLHttpRequest();
  this.request.overrideMimeType('text/xml');
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






var Search = function (container, data) {

  this.input = document.getElementById(data.id);
  if (!this.input) {
    console.error("No valid search input");
    return;
  }

  this.menu = new Menu(this.input.parentNode.insertBefore(document.createElement('ul'), this.input.nextSibling));
  this.menu.list.addEventListener('click', function (evt) {
    if (evt.target.nodeName === 'LI') {
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
    this.indices = {};
    this.menu.list.classList.add('fade');
    this.menu.reset();
  },
  select: function (evt) {

    if (evt) {
      evt.preventDefault();
      evt.stopPropagation();
    }

    this.input.dataset.text = this.input.value;
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

        if (++this.menu.position >= 25) break;
      }

      this.menu.sort();
    }
  },
  checkUp: function (evt) {
    this.delay = 0;
    var meta = evt.keyIdentifier.toLowerCase();

    if (meta === 'down' || meta == 'up') return;

    if (meta === 'enter') {
      this.select(evt);
      return;
    }

    this.menu.reset();
    this.input.dataset.id = '';

    if (this.input.value.length < 1) { this.reset(); return};

    this.processMatches();
  },
  checkDown: function (evt) {
    var letter = String.fromCharCode(evt.keyCode),
        meta   = evt.keyIdentifier.toLowerCase(),
        data   = this.input.dataset;

    if (meta == 'enter') {
      evt.preventDefault();
      return;
    }

    if (this.menu.items.length > 0 && (meta == 'down' || meta == 'up')) {
      evt.preventDefault();
      var current      = this.menu.cycle(meta == 'down' ? 1 : -1);
      this.input.value = current.textContent;
      data.id          = current.id;
      data.group       = current.classList.item(0);
      return;
    }
    if (this.input.value.length === 0 && /[a-z0-9]{1}/i.test(letter)) {
      var path = '/'+data.path+'/{0}/' + letter +'.json?q=' + Date.now();
      this.request(path, data.topic.split(/,\s*/), this.processIndices.bind(this));
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
    this.list.className = 'plain';
    this.items = [];
    this.position = 0;
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
  message = this.element.appendChild(document.createElement('span'));


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

  this.setState = function (state) {
    this.element.dataset.state = state;
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

      document.body.className = evt.target.responseXML.querySelector('body').getAttribute('class') + ' transition';
      setTimeout(function () {
        document.body.classList.remove('transition');
        window.scrollTo(0, document.body.dataset.top);
      }, 100);
      // if
      window.bloc.execute('autoload');
      window.bloc.execute('editables');
    },
    error: function (evt) {
      // should just redirect
      // console.error('FIX this now! look at console..');
      // console.dir(evt.target);
      // console.log(this);
    }
  });


  window.navigateToPage = function (evt) {

    if (evt.type != 'popstate') {
      history.pushState(null, null, this.href);
    }
    Content.get(this.href);
    document.body.classList.add('transition');
    var A, B, C, D;
    A = (75 + Math.random() * 25) + '%' + (75 + (Math.random() * 25)) + '%';
    B = (75 + Math.random() * 25) + '%' + (75 + (Math.random() * 25)) + '%';
    if (window.tablet) {
      C = 'auto ' + Math.max(Math.random() * 75, 50) + (Math.cos(Math.random() * Math.PI) * 25) + '%';
      D = 'auto ' + Math.max(Math.random() * 75, 50) + (Math.cos(Math.random() * Math.PI) * 25) + '%';
    } else {
      C = Math.max(Math.random() * 100, 75) + (Math.cos(Math.random() * Math.PI) * 25) + '%';
      D = Math.max(Math.random() * 100, 75) + (Math.cos(Math.random() * Math.PI) * 25) + '%';

    }
    // TODO: recalculate these based on current position. it's a bit jarring now, esp. in mobile.
    document.body.style.backgroundPosition = A + ', ' + B;
    document.body.style.backgroundSize = C + ', ' + D;
  };

  document.body.addEventListener('click', function (evt) {
    if (evt.target.nodeName.toLowerCase() === 'a') {
      if (evt.target.hash) {
        evt.preventDefault();
        var elem = document.getElementById(evt.target.hash.substr(1));
        if (elem) {
          window.Adjust.scroll(+document.body.dataset.top + elem.offsetTop - 50, 1500);
        }
      } else if (evt.target.matches("a:not(.button)[href^='/']")) {
        evt.preventDefault();
        document.querySelector('main').className = 'wee';
        navigateToPage.call(evt.target, evt);
      } else {
        if (! evt.target.classList.contains('button')) {
          if (evt.target.matches("a[href^='http'])")) {
            evt.preventDefault();
            window.open(evt.target.href);
          }
        }
      }
    }
  }, true);
} else {
  window.navigateToPage = function (evt) {
    document.location.assign(this.href);
  };
}

function processLayout(body, throttle) {
  var timeout = 0, offset = body.dataset.top, engaged = false;

  function operation() {
    if (! engaged && body.scrollTop > offset) {
      engaged = body.dataset.engage = true;
    } else if (engaged && body.scrollTop < offset){
      engaged = false;
      delete body.dataset.engage;
    }
  }

  return function (evt) {
    clearTimeout(timeout);
    timeout = setTimeout(operation, throttle);
  }
};

function setBanner(timeout) {
  var header = document.body.firstElementChild;
      header.removeAttribute('style');
  document.body.dataset.top = header.offsetHeight;
  header.style.height = header.offsetHeight + 'px';
  return setBanner;
}

bloc.prepare('onload', function () {
  window.addEventListener('resize', setBanner());
  window.addEventListener('popstate', navigateToPage.bind(document.location), false);
  window.addEventListener('scroll', processLayout(document.body, 50), false);
});
