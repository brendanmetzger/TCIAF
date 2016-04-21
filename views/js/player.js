// _Capitalized word indicates an instance of an object


// TODO - scroll playlist.

function Track(audio) {
  this.audio = audio;
  this.playcount = 0;
  this.trigger = function(){};
}

Track.prototype = {
  events: ['ended', 'stalled', 'timeupdate', 'error','seeked', 'seeking', 'playing', 'waiting'],
  set element(node){
    node.appendChild(document.createElement('span')).textContent = this.title;
    this._element = node;
    this._element.appendChild(this.audio);
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
  get title() {
    return this.audio.title;
  },
  set position(position) {
    this.element.dataset.position = position;
  },
  get position() {
    return parseInt(this.element.dataset.position, 10);
  }
};

var Playlist = function (container, attributes) {
  this.tracks  = [];
  this.pointer = null;
  this.element = container.appendChild(document.createElement('ul')['@'](attributes));
};

Playlist.prototype = {
  select: function (index, evt) {
    // this.element.parentNode.dataset.track = this.pointer;

    var currentTrack = this.tracks[this.pointer];
    if (! currentTrack.audio.paused) {
      currentTrack.state = 'played';
      currentTrack.audio.pause();
    }
    this.pointer = index;
    setTimeout(function () {
      this.play();
    }.bind(this.current.audio), 750);

    this.current.trigger(evt);
  },
  next: function (idx) {
    this.tracks[this.pointer].state = 'played';
    this.pointer += (this.pointer < this.length) ? 1 : this.length * -1;
    return this.current;
  },
  get length () {
    return this.tracks.length - 1;
  },
  get current() {
    if (this.pointer !== null) {
      var track = this.tracks[this.pointer];
      track.state = 'current';
      return track;
    }

  },
  enQueue: function (_Track) {
    _Track.element  = this.element.appendChild(document.createElement('li'));
    _Track.position = (this.tracks.push(_Track) - 1);
    _Track.element.addEventListener('click', this.select.bind(this, _Track.position));

    return _Track;
  },
  // not a real queue, as some elements bay be skipped
  deQueue: function (_Track) {
    // FIXME: Error occurs here sometimes
    this.element.removeChild(_Track.element);
    return this.tracks.splice(_Track.position, 1);
  },
  getUnplayed: function () {
    return this.tracks.filter(function (track) {
      return ! track.element.classList.contains('played');
    });
  },
  clear: function (list) {
    list.forEach(this.deQueue, this);
    this.tracks.map(function (track, index) {
      track.position = index;
    });
  }
};

var Player = function (container, data, message) {
  this.container = container;
  this.container.id  = 'Player';
  this.elements = [];
  this.index    = 0;

  this.container.appendChild(document.createElement('p')['@']({
    'class': 'intro h4 pad rag-left brown r'
  })).textContent = message;

  var controls = this.container.appendChild(document.createElement('div')['@']({
    'class': data.controls
  }));

  var button = controls.appendChild(document.createElement('button')['@']({
    'type': 'button'
  }));

  this.playlist = new Playlist(this.container, {'class': data.playlist});
  this.button   = new Button(button, 'play');
  this.meter    = new Progress(controls);

  this.button.press(function(evt) {
    evt.preventDefault();
    this[this.button.state].call(this);
  }.bind(this));

  this.meter.element.addEventListener(mobile ? 'touchmove' : 'mousemove', function (evt) {
    this.meter.update(evt.theta() / 360, null, true);
  }.bind(this), false);

  this.meter.element.addEventListener(mobile ? 'touchend' : 'click', function (evt) {
    var audio = this.playlist.current.audio;
    audio.currentTime = audio.duration * (evt.type == 'touchend' ? this.meter.position() : (evt.theta() / 360));
  }.bind(this), false);

};


Player.prototype = {
  cleanup: function () {
    var player = bloc.module('Player');
    player.pause();
    delete player;
  },
  play: function () {
    var current = this.playlist.current;
    this.container.dataset.position = current.position;
    current.audio.play();
    window.addEventListener('unload', Player.prototype.cleanup);
  },
  pause: function () {
    this.button.setState('play');
    this.playlist.current.audio.pause();
    window.removeEventListener('unload', Player.prototype.cleanup);
  },
  ended: function (evt) {
    this.playlist.next().play();
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
    console.log('seeked');
  },
  stalled: function (evt) {
    // this.meter.setState('waiting');
  },
  error: function (evt) {
    console.log('error', evt);
  },
  timeupdate: function (evt) {
    var elem = evt.target;
    var t = Math.ceil(elem.currentTime) * 1e3;
    var d = Math.ceil(elem.duration) * 1e3;
    var m = "{m}:{s}<br/>";
    this.meter.update(t/d, new Date(t).parse(m) + new Date(d-t).parse(m));
  },
  // Returns `new Track` instance
  attach: function (audio_element) {
    // TODO: check for track in list
    var track = this.playlist.enQueue(new Track(audio_element));

    track.events.forEach(function (trigger) {
      audio_element.addEventListener(trigger, this[trigger].bind(this), false);
    }.bind(this));

    return track;
  }
};

function loadButtonAudio(button) {
  var selected = button.parentNode.querySelector('audio');
  var player = bloc.module('Player');
  player.playlist.clear(player.playlist.getUnplayed());

  document.querySelectorAll('audio').forEach(function (audio) {
    // select the button that was responsible for playing this track
    var button = audio.parentNode.querySelector('button.listen');
    var track  = player.attach(audio);
    if (button) {
      button.addEventListener('click', player.playlist.select.bind(player.playlist, track.position));
    }

    track.trigger = function (evt) {
      navigateToPage.call({href: this.audio.dataset.ref}, evt);
    }
    if (selected === audio) {
      // set this to be selected
      player.playlist.pointer = track.position;
    }
  });

  player.play();
  button.classList.add('queued');
}












// should implement a controllable interface

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
