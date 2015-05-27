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

// this is a simple animation rigamarole;
var transition = function (begin, callback) {
  if (callback.call(this, begin)) {
    requestAnimationFrame(transition.bind(this, begin, callback));
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



var Player = function (element) {
  if (element.nodeName === "AUDIO") {
    this.element = element;
    this.element.removeAttribute('controls');

    var container = this.element.parentNode.insertBefore(document.createElement('div'), this.element);
        container.id = "player";
    var button = container.appendChild(document.createElement('button'));
        button.setAttribute('type', 'button');
    this.button = new Button(button, 'play');
    
    var button_activate = function (evt) {
      evt.preventDefault();
      console.log(this.button.state);
      this[this.button.state].call(this);
    }.bind(this);

    this.button.getDOMButton().addEventListener('touchend', button_activate, false);
    this.button.getDOMButton().addEventListener('click', button_activate, false);
  }
  
  // need a title, artist, description
  // need a scrubber
};

Player.attach = function (audio_element) {
  window.player = new this(audio_element);
};

Player.prototype = {
  element: null,
  button: null,
  play: function () {
    this.button.setState('pause');
    this.element.play();
  },
  pause: function () {
    this.button.setState('play');
    this.element.pause();
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
    play:  'M11,7.5l0,30l12.5,-8l0-14l-12.5,-8m12.5,8l0,14l12.5,-7l0,0  z',
    pause: 'M11.5,10 l0,25l10,0   l0-25l-10,0   m12,0  l0,25l10,0 l0,-25z',
    error: 'M16,10 l10,0l-3,20  l-3,0l-3,-20  m3,22  l4,0 l0,4    l-4,0 z',
    wait:  'M521.5,21.5A500,500 0 1 1 427.0084971874736,-271.39262614623664'
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


var Meter = function (audio, meter_element) {
  this.audio   = audio;
  this.element = meter_element;
  
  this.element.querySelectorAll('button').forEach(function (button) {
    button.addEventListener('click', function (evt) {
      this.input.focus();
      var seektime = Math.ceil(parseFloat(this.input.value) + (this.audio.duration * parseFloat(button.value, 10)));
      this.input.value = seektime;
      this.audio.currentTime = seektime;
      this.input.blur();
    }.bind(this), false);
  }, this);
  
  this.input = document.getElementById('scrubber');

  this.input.addEventListener('change', function (evt) {
    this.audio.currentTime = parseInt(this.input.value, 10);    
    this.input.blur();
  }.bind(this), false);
  
  this.input.addEventListener('input', function (evt) {
    this.elapsed.text("{h}:{m}:{s}".format(this.timecode(this.input.value)));
    this.duration.text("{h}:{m}:{s}".format(this.timecode(this.audio.duration - this.input.value)));
  }.bind(this));
      
  this.input.addEventListener('mousedown', function (evt) {
    this.dispatchEvent(new Event('focus'));
  }, false);
  
  this.input.addEventListener('touchstart', function (evt) {
    this.dispatchEvent(new Event('focus'));
  }, false);
 
  this.input.addEventListener('mouseup', function (evt) {
    this.dispatchEvent(new Event('blur'));
  }, false);
  
  this.input.addEventListener('touchend', function (evt) {
    this.dispatchEvent(new Event('blur'));
  }, false);
  
  this.input.addEventListener('focus', function (evt) {
    this.updating = true;
  }.bind(this), false);
  
  this.input.addEventListener('blur', function (evt) {
    this.updating = false;
  }.bind(this), false);

  var container = this.element.querySelector('div');
  
  this.elapsed  = new Elem('span').insert(container).text('0:00');
  this.duration = new Elem('span').insert(container).text('0:00');
};



Meter.prototype = {
  updating:false,
  shown: false,
  timecode: function (time) {
    var date = new Date(time * 1000);
    return {
      h: ('00'+date.getUTCHours()).slice(-2),
      m: ('00'+date.getUTCMinutes()).slice(-2),
      s: ('00'+date.getSeconds()).slice(-2)
    };
  },
  update: function () {
    this.input.value = this.audio.currentTime;

    var deplete = 100 - Math.ceil((this.audio.currentTime / this.audio.duration) * 100);

    this.input.style.backgroundImage ='linear-gradient(to left, rgba(0,0,0,0.75) '+deplete+'%, rgba(0,0,0,0) '+(deplete + 5)+'%)';

    this.elapsed.text("{h}:{m}:{s}".format(this.timecode(this.audio.currentTime)));
    this.duration.text("{h}:{m}:{s}".format(this.timecode(this.audio.duration - this.audio.currentTime)));
    
  }
};