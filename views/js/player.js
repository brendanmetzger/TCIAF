// _Capitalized word indicates an instance of an object


// TODO - scroll playlist.

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

  var button = controls.appendChild(document.createElement('button').attr({
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
