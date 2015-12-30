// _Capitalized word indicates an instance of an object

function Track(audio) {
  this.audio = audio;
  this.playcount = 0;
}

Track.prototype = {
  events: ['progress','ended', 'stalled', 'timeupdate', 'error','seeked','seeking','playing','waiting'],
  set element(node){
    this._element = node;
    this._element.appendChild(this.audio);
    this._element.appendChild(document.createElement('span')).textContent = this.title;

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


var Playlist = function () {
  this.tracks  = [];
  this.pointer = 0;
  this.element = document.createElement('ul');
};

Playlist.prototype = {
  select: function (index) {
    var currentTrack = this.tracks[this.pointer];
    if (! currentTrack.audio.paused) {
      currentTrack.state = 'played';
      currentTrack.audio.pause();
    }

    this.pointer = index;
    this.current.play();
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
    var track = this.tracks[this.pointer];
    track.state = 'current';
    return track.audio;
  }
};


Playlist.prototype.queue = function (_Track) {
  _Track.element  = this.element.appendChild(document.createElement('li'));
  _Track.position = (this.tracks.push(_Track) - 1);
  _Track.element.addEventListener('click', this.select.bind(this, _Track.position));

  return _Track;
}

var Player = function (container, data) {
  container.id = 'Player';
  this.container = container;
  this.elements = [];
  this.index = 0;

  var controls = container.appendChild(document.createElement('div'));
      controls.className = data.controls;

  this.playlist = new Playlist;
  this.playlist.element.className = data.playlist;

  this.container.appendChild(this.playlist.element);

  var button   = controls.appendChild(document.createElement('button'));
      button.setAttribute('type', 'button');

  this.button = new Button(button, 'play');

  var button_activate = function (evt) {
    evt.preventDefault();
    this[this.button.state].call(this);
  }.bind(this);

  this.button.getDOMButton().addEventListener('touchend', button_activate, false);
  this.button.getDOMButton().addEventListener('click', button_activate, false);

  this.meter = new Progress(controls);

  var tick = function (evt) {
    this.update((evt.theta() / 360), null, true);
  }.bind(this.meter);

  this.meter.element.addEventListener('mouseover', function () {
    this.element.addEventListener('mousemove', tick, false);
  }.bind(this.meter));

  this.meter.element.addEventListener('mouseout', function () {
    this.element.removeEventListener('mousemove', tick, false);
  }.bind(this.meter));

  this.meter.element.addEventListener('click', function (evt) {
    var audio = this.playlist.current;
    audio.currentTime = audio.duration * (evt.theta() / 360);
  }.bind(this), false);

};



Player.prototype = {
  progress: function (evt) {
    if (evt.target.buffered.length > 0) {
      this.meter.update(evt.target.buffered.end(0) / evt.target.duration, null, true);
    }
  },
  play: function () {
    this.playlist.current.play();
  },
  pause: function () {
    this.button.setState('play');
    this.playlist.current.pause();
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
    var time = Math.ceil(elem.currentTime);
    var dur  = Math.ceil(elem.duration);
    var msg = "{m}:{s}";
    this.meter.update(time / dur, msg.format(this.timecode(new Date(time*1e3))) + "<br/>"+  msg.format(this.timecode(new Date((dur-time)*1e3))));

  },
  timecode: function (timestamp) {
    return {
      h: ('00'+timestamp.getUTCHours()).slice(-2),
      m: ('00'+timestamp.getUTCMinutes()).slice(-2),
      s: ('00'+timestamp.getSeconds()).slice(-2)
    };
  },
  attach: function (audio_element) {
    var track = this.playlist.queue(new Track(audio_element));

    track.events.forEach(function (trigger) {
      audio_element.addEventListener(trigger, this[trigger].bind(this), false);
    }.bind(this));

    return track;
  },
  detach: function (audio_element) {
    delete this.elements[audio_element.dataset.index];
  }
};

function loadButtonAudio(button) {
  var selected = button.parentNode.querySelector('audio');
  var player = bloc.execute('Player');
  document.querySelectorAll('audio').forEach(function (audio) {
    var track = player.attach(audio);
    if (selected === audio) {
      // set this to be selected
      player.playlist.pointer = track.position;
    }
  });

  player.play();


  button.classList.add('queued');
}
