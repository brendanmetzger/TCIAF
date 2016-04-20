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
  command: {up: -1, down: 1, enter: true },
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

        if (this.menu.list.children.length >= 25) break;
      }
      this.menu.sort();
    }
  },
  checkUp: function (evt) {
    var meta  = this.command[evt.keyIdentifier.toLowerCase()];
    if (meta) return meta === true ? this.select(evt) : meta;

    this.menu.reset();
    this.input.dataset.id = '';

    if (this.input.value.length < 1) {
      this.reset();
      return;
    }
    this.processMatches();
  },
  checkDown: function (evt) {
    var meta  = this.command[evt.keyIdentifier.toLowerCase()];
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
};

Menu.prototype = {
  list: null,
  items: [],
  index: -1,
  reset: function () {
    while (this.list.firstChild) this.list.removeChild(this.list.firstChild);
    this.list.className = 'plain';
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

  svg = new SVG(this.element, {
    height: 50,
    width: 50,
    viewBox: '0 0 100 100'
  });

  svg.createElement('circle', { 'cx': 50, 'cy': 50, 'r': 35 });
  handle = svg.createElement('path', { 'd': 'M50,50', 'class': 'handle', 'transform': 'rotate(-90 50 50)'});
  path   = svg.createElement('path', { 'd': 'M50,50', 'transform': 'rotate(-90 50 50)' });


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
        eval(script.text);
      });

      var main = document.body.querySelector('main');
      main.parentNode.replaceChild(evt.target.responseXML.querySelector('main'), main);

      document.body.className = evt.target.responseXML.querySelector('body').getAttribute('class') + ' transition';
      setTimeout(function () {
        document.querySelector('#browse').scrollTop = 0;
        document.body.classList.remove('transition');
      }, 10);

      // if
      bloc.load(true);
    },
    error: function (evt) {
      // should just redirect
      // console.error('FIX this now! look at console..');
      // console.dir(evt.target);
      // console.log(this);
    }
  });

  var adjust = function (num) {
    return Math.round((parseFloat(num, 10) + (Math.cos(Math.random() * 2 * Math.PI) * 25))) + '%';
  };

  window.navigateToPage = function (evt) {
    if (evt.type != 'popstate') {
      history.pushState(null, null, this.href);
    }
    Content.get(this.href);
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

bloc.init(function () {
  window.Adjust = smoothScroll(document.querySelector('#browse'));
  window.addEventListener('popstate', navigateToPage.bind(document.location), false);
});


bloc.init(bloc.define('autoload', function () {
  document.querySelectorAll('noscript').forEach(function (elem) {
    var swap = document.createElement('div');
    elem.parentNode.replaceChild(swap, elem);
    try {
      var module = bloc.module(elem.id);
      if('call' in module) module(new window[elem.className](swap, elem.dataset));
    } catch(e) {
      console.log(e, elem.id);
    }
  });
  return this;
}), 'unshift');

bloc.define('site-search', function (instance) {
  instance.subscribers.select.push(function (dataset, evt) {
    var url = (Number(dataset.index) < 0)
            ? '/search/full?query=' + dataset.text
            : '/explore/detail/'+dataset.id;
    navigateToPage.call({href: url}, evt);
  });
});
