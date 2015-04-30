function Editor() {
  document.body.addEventListener('dblclick', function (evt) {
    var elem = evt.srcElement;
    var button = document.createElement('button');
    button.textContent = "try to save";
    elem.parentNode.insertBefore(button, elem);
    elem.setAttribute('contentEditable', true);
    elem.addEventListener('blur', function (evt) {
      console.log(this);
    }, false);
    elem.focus();
  }, false);
}

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
  
  var elem = window.getComputedStyle(document.querySelector('.text') || document.body, null);
  var size = Math.ceil(parseFloat(elem.getPropertyValue("line-height"), 10));  
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"px' height='"+size+"px' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('form.editor .text {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ size + 'px' +' !important; }', 0);
  
  var textareas = document.querySelectorAll('textarea.text');
  for (var i = textareas.length - 1; i >= 0; i--) {
    autoGrow.call(textareas[i]);
    textareas[i].addEventListener('keyup', autoGrow.bind(textareas[i]));
  }
  autoGrow(document.getElementById('description'));
});

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

