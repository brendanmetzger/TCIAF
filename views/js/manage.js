


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
  var size = Math.floor(parseFloat(elem.getPropertyValue("line-height"), 10));  
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"px' height='"+size+"px' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('form.editor .text {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ size + 'px' +' !important; }', 0);
  

  var textareas = document.querySelectorAll('textarea.text');
  if (textareas.length > 0) {
    var markdown_editor = new Markdown();
    for (var i = textareas.length - 1; i >= 0; i--) {
      textareas[i].addEventListener('keyup', markdown_editor.autoGrow(textareas[i]));
      textareas[i].addEventListener('select', markdown_editor.watch());
      textareas[i].addEventListener('focus', markdown_editor.show());
      textareas[i].addEventListener('blur', markdown_editor.hide());
    }
  }
});

function Markdown() {
  this.hud = document.body.appendChild(document.createElement('nav'));
  this.hud.className = 'hud';

  var list = this.hud.appendChild(document.createElement('ul'));
      list.className = 'inline';


  for (var command in this.commands) {
    var li = list.appendChild(document.createElement('li'));
        li.innerHTML = this.commands[command].format;
        li.addEventListener('click', this.command.bind(this, command), false);
  }
}

Markdown.prototype = {
  timeout: 0,
  element: null,
  position: 0,
  commands: {
    bold : {
      format: '<var>**</var><strong>bold</strong><var>**</var>',
      insert: function (message, begin, end) {
        return {value: Markdown.prototype.wrap('**', message, begin, end)};
      }
    },
    italic : {
      format: '<var>*</var><em>italic</em><var>*</var>',
      insert: function (message, begin, end) {
        return {value: Markdown.prototype.wrap('*', message, begin, end)};
      }
    },
    list   : {
      format: '<var>-</var> list',
      insert: function (message, begin, end) {
        var output = message.substring(0, begin);
        if (message.charCodeAt(begin-1) !== 13 && message.charCodeAt(begin-1) !== 10) {
          output += "\n";
        }
        
        output += '- ' + message.substring(begin, end) + "\n" + message.substring(end);
        
        return {value: output};
      }
    },
    link   : {
      format: '<var>[</var>Link Text<var>](</var>url<var>)</var>',
      insert: function (message, begin, end) {
        var url = window.prompt("Please insert a link", "http://");
        
        if (url === null) {
          return string;
        }
        
        return {value: message.substring(0, begin) + '[' + message.substring(begin, end) + '](' + url + ')' + message.substring(end)}; 
      }
    },
    exclaim: {
      format: '<var>***</var><em><strong>highlight</strong></em><var>***</var>',
      insert: function (message, begin, end) {
        return {value: Markdown.prototype.wrap('***', message, begin, end)};
      }
    },
    quote  : {
      format: '<var>></var> <q>quote</q>',
      insert: function (message, begin, end) {
        return {value: '> ' + string + "\n"};
      }
    }
  },
  wrap: function (delimiter, message, begin, end) {
    return message.substring(0, begin) + delimiter + message.substring(begin, end) +  delimiter + message.substring(end);
  },
  autoGrow: function (textarea) {
    var func = function () {
      if (textarea.scrollHeight > textarea.clientHeight) {
        textarea.style.height = textarea.scrollHeight + "px";
      }
    };
    func.call(textarea);
    return func;
  },
  selection: function (evt) {
    console.log('selecting');
  },
  command: function (command, evt) {
    clearTimeout(this.timeout);
    this.element.focus();

    var message = this.element.value;
    var begin   = this.element.selectionStart;
    var end     = this.element.selectionEnd;
    var cursor  = message.substring(begin, end);
    var swap    = null;

    if (cursor) {
      swap = this.commands[command].insert(message, begin, end);
      this.element.value = swap.value;
      this.element.setSelectionRange(begin, begin);
    } else {
      message = message.substring(0, begin) + command + message.substring(end);
      swap = this.commands[command].insert(message, begin, end + command.length);
      this.element.value = swap.value;
      this.element.setSelectionRange(begin + 1, end + command.length + 1);
    }
  },
  watch: function () {
    return this.selection.bind(this);
  },
  show: function (evt) {
    if (! evt) {
      return this.show.bind(this);
    }
    this.element = evt.target;
    evt.target.parentNode.insertBefore(this.hud, evt.target);
    setTimeout(function () {
      this.hud.classList.add('visible');  
    }.bind(this), 10);
    
  },
  hide: function (evt) {
    if (! evt) {
      return this.hide.bind(this);
    }
    this.timeout = setTimeout(function () {
      this.hud.classList.remove('visible');
    }.bind(this), 250);
    
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

