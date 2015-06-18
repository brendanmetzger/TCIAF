bloc.prepare(function () {
  var stylesheet  = document.styleSheets.length - 1;

  while (stylesheet > 0 && typeof stylesheet === 'number') {
    if (document.styleSheets[stylesheet].title === 'administrator') {
      stylesheet = document.styleSheets[stylesheet];
      continue;
    }
    stylesheet--;
  }
  
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('change', imploreSave, false);
  });



  var elem = window.getComputedStyle(document.querySelector('.text') || document.body, null);
  var size = Math.floor(parseFloat(elem.getPropertyValue("line-height"), 10));
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"px' height='"+size+"px' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('form.editor .text {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ size + 'px' +' !important; }', stylesheet.cssRules.length);

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
  
  
  
  // show an indicator next to all editable elements
  function goto(url, evt) {
    evt.preventDefault();
    window.location.href = url;
  }
  var edits = document.querySelectorAll('*[data-id]');
  for (var j = 0; j < edits.length; j++) {
    var url = '/manage/edit/' + edits[j].dataset.id;
    var button = edits[j].appendChild(document.createElement('button'));
        button.textContent = 'Edit';
        button.title = "Edit";        
        button.addEventListener('click', goto.bind(button, url), false);
  }
});

function imploreSave(evt) {
  document.body.querySelector('nav.dashboard').style.backgroundColor = '#5B9B98';
  console.log(evt);
}

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

function Upload(destination_url) {
  this.action = destination_url;  
  this.input = document.body.appendChild(document.createElement('input'));
  this.input.accept = 'image/jpeg, image/jpg, audio/mp3, audio/mp4';
  this.input.name = 'uploader';
  this.input.type = 'file';

  this.xhr = new XMLHttpRequest();
  
  this.xhr.addEventListener('loadstart', function (evt) {
    this.status = 'Uploading';
  }.bind(this));
  this.xhr.addEventListener('load', this.invoke.bind(this), false);
  
  this.xhr.upload.onprogress = function (evt) {
    if (evt.lengthComputable && this.progress) {
      this.progress.update(evt.loaded/evt.total, 'Uploading');
    }
  }.bind(this);
  
  this.xhr.upload.onload = function (evt) {
    this.progress.update(0.9, 'Compressing');
    this.progress.element.classList.add('spin');
  }.bind(this);
  
  
  this.input.addEventListener('change', function (evt) {
    if (this.input.files.length < 1) return;
    var type = this.input.files[0].type.split('/')[0] || null;
    
    try {
      if (this.rules[type]) {
        this.rules[type].call(this, this.input.files[0]);
      }
      var fd = new FormData();
          fd.append("upload", this.input.files[0]);
      this.attach(fd);
    } catch (e) {
      console.error(e);
    }
    
  }.bind(this), false);
}

Upload.prototype = {
  progress: null,
  rules: {},
  allowed: ['audio', 'image'],
  callbacks: {
    'success': [],
    'failure': []
  },
  addTrigger: function (element, callback) {
    element.addEventListener('click', function (evt) {
      evt.preventDefault();
      if ((callback || function () { return true;}).call(evt.target, this)) {
        this.input.dispatchEvent(new Event('click'));
      }
    }.bind(this), false);
  },
  invoke: function (evt) {
    var status = (evt.target.status < 300) ? 'success' : 'failure';
    this.callbacks[status].forEach(function (callback) {
      callback.call(this, evt.target);
    }, this);
    
  },
  addEvent: function (type, callback) {
    this.callbacks[type].push(callback);
  },
  addRoutine: function (type, callback) {
    this.rules[type] = callback;
  },
  attach: function (blob) {
    this.xhr.open('POST', this.action);
    this.xhr.send(blob); 
  }
};

var Modal = function (element) {
  this.backdrop = document.body.appendChild(document.createElement('div'));
  this.backdrop.className = 'backdrop';
  if (element) {
    this.addElement(element);
  }
};


Modal.prototype = {
  addElement: function (element) {
    this.element = element;
    this.backdrop.appendChild(this.element);
    // make closeable
    var button = document.createElement('button');
        button.className = 'close action';
        button.innerHTML = '&times;';
        button.addEventListener('click', this.close.bind(this));
  
    this.element.insertBefore(button, this.element.firstChild);
    
  },
  show: function () {
    document.body.classList.add('locked');
    this.backdrop.style.height = document.body.scrollHeight + 'px';
    // this.element.style.top = document.body.scrollTop + (window.innerHeight / 4) + 'px';
    this.backdrop.classList.add('viewing');
  },
  close: function (evt) {
    if (evt instanceof Event) {
      evt.preventDefault();
    }
    
    document.body.classList.remove('locked');
    this.backdrop.classList.remove('viewing');

    if (evt instanceof Function) {
      evt.call(this);
    }
  }
};


Modal.Form = function (url, opts, callback) {
  this.options = opts;
  this.modal = new Modal(null);
  this.ajax = new XMLHttpRequest();
  this.ajax.overrideMimeType('text/xml');
  this.ajax.addEventListener('load', this.processForm.bind(this));
  this.ajax.open('GET', url);
  this.ajax.send();
  
  // the callback is what is called when the form completes the entire dialog
  this.callback = callback;
  this.modal.show();
};

Modal.Form.prototype = {
  options: {},
  modal: null,
  form: null,
  ajax: null,
  processForm: function (evt) {
    
    
    evt.target.responseXML.documentElement.querySelectorAll('body script[async]').forEach(function (script) {
      document.head.appendChild(window.bloc.tag(false)).text = script.text;
    });
    
    if (this.form === null) {
      this.form  = evt.target.responseXML.documentElement.querySelector('form.editor');
      this.modal.addElement(this.form);
      
      this.form.addEventListener('submit', function (evt) {
        evt.preventDefault();

        this.ajax.open("POST", this.form.action);
        this.ajax.send(new FormData(this.form));
      
      }.bind(this));
      
      for (var option in this.options) {
        var input = this.form.querySelector('input[name*='+option+']');
            input.value = this.options[option];
            input.focus();
      }

      
    } else if (this.callback){
      this.callback.call(this, evt.target.responseXML.documentElement);
    }
  }
};



var Spectra = function(labels) {
  var total = labels.length;
  for (var i = 0; i < total; i++) {
    labels[i].dataset.index = (i / total);
    this.color.call(labels[i]);
    labels[i].addEventListener('input', this.color);
  }
};

Spectra.prototype.color = function () {
  var value = parseInt(this.value, 10);
  var color = {
    h: Math.round(parseFloat(this.dataset.index, 10) * 255), 
    s: Math.round((Math.abs(50 - value) / 100) * 200) + '%',
    l: Math.round(((Math.abs(100 - value) / 100) * 50) + 40) + '%'
  };
  this.parentNode.style.backgroundColor = 'hsla({h}, {s}, {l}, 0.35)'.format(color);
};












function sortable(rootEl, onUpdate) {
   var dragEl;

   // Making all siblings movable
   [].slice.call(rootEl.children).forEach(function (itemEl) {
       itemEl.draggable = true;
   });

   // Function responsible for sorting
   function _onDragOver(evt) {
       evt.preventDefault();
       evt.dataTransfer.dropEffect = 'move';

       var target = evt.target;
       
       if( target && target !== dragEl && target.nodeName == 'DL' ){
         // Sorting         
         var rect = target.getBoundingClientRect();
         var next = (evt.clientY - rect.top)/(rect.bottom - rect.top) > 0.5;
         rootEl.insertBefore(dragEl, next && target.nextSibling || target);
       }
   }

   // End of sorting
   function _onDragEnd(evt){
       evt.preventDefault();

       dragEl.classList.remove('ghost');
       rootEl.removeEventListener('dragover', _onDragOver, false);
       rootEl.removeEventListener('dragend', _onDragEnd, false);


       // Notification about the end of sorting
       onUpdate(dragEl);
   }

   // Sorting starts
   rootEl.addEventListener('dragstart', function (evt){
       dragEl = evt.target; // Remembering an element that will be moved

       // Limiting the movement type
       evt.dataTransfer.effectAllowed = 'move';
       evt.dataTransfer.setData('Text', dragEl.textContent);


       // Subscribing to the events at dnd
       rootEl.addEventListener('dragover', _onDragOver, false);
       rootEl.addEventListener('dragend', _onDragEnd, false);


       setTimeout(function () {
           // If this action is performed without setTimeout, then
           // the moved object will be of this class.
           dragEl.classList.add('ghost');
       }, 0);
   }, false);
}
