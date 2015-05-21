

function autoGrow () {
  if (this.scrollHeight > this.clientHeight) {
    this.style.height = this.scrollHeight + "px";
  }
}

bloc.prepare(function () {
  var stylesheet  = document.styleSheets.length - 1;

  while (stylesheet > 0 && typeof stylesheet === 'number') {
    if (document.styleSheets[stylesheet].title === 'administrator') {
      stylesheet = document.styleSheets[stylesheet];
      continue;
    }
    stylesheet--;
  }
  
  var markdown_editor = new Markdown();
  
  var elem = window.getComputedStyle(document.querySelector('.text') || document.body, null);
  var size = Math.ceil(parseFloat(elem.getPropertyValue("line-height"), 10));  
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"px' height='"+size+"px' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('form.editor .text {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ size + 'px' +' !important; }', 0);
  
  var textareas = document.querySelectorAll('textarea.text');
  for (var i = textareas.length - 1; i >= 0; i--) {
    autoGrow.call(textareas[i]);
    textareas[i].addEventListener('keyup', autoGrow.bind(textareas[i]));
    textareas[i].addEventListener('select', markdown_editor.watch());
    textareas[i].addEventListener('focus', markdown_editor.show());
    textareas[i].addEventListener('blur', markdown_editor.hide());
  }
  autoGrow(document.getElementById('description'));
});

function Markdown() {
  this.hud = document.body.appendChild(document.createElement('nav'));
  this.hud.className = 'hud';

  var list = this.hud.appendChild(document.createElement('ul'));
  list.className = 'inline';



  this.commands.forEach(function (command) {
    var li = list.appendChild(document.createElement('li'));
    li.innerHTML = command.text;
    li.id = command.name;
    li.addEventListener('click', function (evt) {
      console.log(this.id);
    }, false);
  });
  
}

Markdown.prototype = {
  buffer: null,
  position: 0,
  commands: [
    {name: 'bold',   text: '<var>**</var><strong>bold</strong><var>**</var>'},
    {name: 'italic', text: '<var>*</var><em>italic</em><var>*</var>'},
    {name: 'list',   text: '<var>-</var> list'},
    {name: 'link',   text: '<var>[</var>Link Text<var>](</var>url<var>)</var>'},
    {name: 'exclaim',text: '<var>***</var><em><strong>highlight</strong></em><var>***</var>'},
    {name: 'quote',  text: '<var>></var> quote'}
  ],
  selection: function (evt) {
    this.buffer = {element: evt.target, offsets: [evt.target.selectionStart, evt.target.selectionEnd]};
  },
  watch: function () {
    return this.selection.bind(this);
  },
  show: function (evt) {
    if (! evt) {
      return this.show.bind(this);
    }
    evt.target.parentNode.insertBefore(this.hud, evt.target);
    setTimeout(function () {
      this.hud.classList.add('visible');  
    }.bind(this), 10);
    
  },
  hide: function (evt) {
    if (! evt) {
      return this.hide.bind(this);
    }
    setTimeout(function () {
      this.hud.classList.remove('visible');
    }.bind(this), 10);
    
  }
  
};

function Upload(element) {
  this.input = element;
  this.xhr = new XMLHttpRequest();
  this.xhr.open('POST', this.input.dataset.url);
  
  this.xhr.onload = function (evt) {
    console.log('request complete!', evt, this.xhr.response);
  }.bind(this);
  
  this.xhr.upload.onprogress = function (evt) {
    if (evt.lengthComputable) {
      console.log(evt.loaded, 'of', evt.total);
    }
  };
  
  this.xhr.upload.onload = function (evt) {
    console.log('finished upload', evt);
  };
  
  this.input.addEventListener('change', function (evt) {
    var type = this.input.files[0].type.split('/')[0] || null;
    if (this.rules[type]) {
      this.rules[type].call(this, this.input.files[0]);
    }
    try {
      var fd = new FormData();
          fd.append("upload", this.input.files[0]);
      this.attach(fd);
    } catch (e) {
      window.alert(e);
    }
    
  }.bind(this), false);
}

Upload.prototype = {
  rules: {},
  allowed: ['audio', 'image'],
  states: [
    'unsent',
    'opened',
    'headers',
    'loading',
    'complete'
  ],
  addRoutine: function (type, callback) {
    this.rules[type] = callback;
  },
  attach: function (blob) {
    this.xhr.send(blob); 
  }
};

