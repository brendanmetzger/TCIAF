bloc.prepare('stylesheets', function () {
  var stylesheet  = document.styleSheets.length - 1;

  while (stylesheet > 0 && typeof stylesheet === 'number') {
    if (document.styleSheets[stylesheet].title === 'administrator') {
      stylesheet = document.styleSheets[stylesheet];
      continue;
    }
    stylesheet--;
  }

  var elem = window.getComputedStyle(document.querySelector('input') || document.body, null);
  var size = Math.floor(parseFloat(elem.getPropertyValue("line-height"), 10));
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"px' height='"+size+"px' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('form.editor .text {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ size + 'px' +' !important; }', stylesheet.cssRules.length);

  // show an indicator next to all editable elements
});

bloc.prepare('editables', function () {
  var edits = document.querySelectorAll('*[data-id]');

  for (var j = 0; j < edits.length; j++) {
    var url = '/manage/edit/' + edits[j].dataset.id;
    edits[j].dataset.id = null;
    var button = edits[j].appendChild(document.createElement('button'));
        button.textContent = 'Edit';
        button.title = "Edit";
        button.addEventListener('click', goto.bind(button, url), false);

  }

});


function goto(url, evt) {
  evt.preventDefault();
  if (evt.metaKey) {
    document.location.assign(url);
    return;
  }

  (new Modal.Form({
    load: function (form) {
      window.bloc.execute('stylesheets');
      form.querySelector('input').focus();
    },
    submit: function (evt) {
      // var res = evt.target.responseXML;
      this.modal.close();

      var exist = document.querySelector('main');
      new Request({
        load: function (evt) {

          setTimeout(function () {
            exist.parentNode.replaceChild(evt.target.responseXML.querySelector('main'), exist);
            window.bloc.execute('editables');
          }, 100);


        }
      }).get(window.location.href + '.xml');
    }
  })).load(url + '.xml');
}


function Markdown(container, options) {
  container.id = "Markdown";
  this.hud = container.appendChild(document.createElement('nav'));
  this.hud.className = 'hud visible';
  this.textareas = document.querySelectorAll(options.selector);

  this.textareas.forEach(function (t) {
    // this.fit(t);
    t.addEventListener('keyup', this.fit.bind(this, t));
    t.addEventListener('select', this.watch.bind(this));
    t.addEventListener('focus', this.show.bind(this));
  }, this);

  if (this.textareas.length > 0) {
    this.textareas[0].parentNode.insertBefore(this.hud, this.textareas[0]);
  }

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
  fit: function (textarea) {
    if (textarea.scrollHeight > textarea.clientHeight) {
      textarea.style.height = textarea.scrollHeight + "px";
    }
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

  },

};


function Upload(container, data) {
  this.container = container;
  this.action    = data.url;

  this.xhr = new XMLHttpRequest();

  if (this.input === null) {
    this.input = this.container.appendChild(document.createElement('input'));
    this.input.type = 'file';
    this.input.id = "_" + Date.now().toString(36);
  }

  this.input.accept = data.accept;

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
Upload.instance = null;
Upload.prototype = {
  input: null,
  progress: null,
  rules: {},
  allowed: ['audio', 'image'],
  callbacks: {
    'success': [],
    'failure': []
  },
  addTrigger: function (element) {
    element.addEventListener('click', function (evt) {
      evt.preventDefault();
      this.input.dispatchEvent(new Event('click'));
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
  this.backdrop.id = '_' + Date.now().toString(36);
  this.backdrop.className = 'backdrop';
  // this.backdrop.addEventListener('dblclick', this.close.bind(this), false);
  if (element) {
    this.addElement(element);
  } else {
    this.progress = new Progress(this.backdrop);
    this.progress.update(0.9, 'One Moment..');
    this.progress.element.classList.add('spin');
  }
}; Modal.prototype = {
  addElement: function (element) {
    this.element = element;
    this.backdrop.appendChild(this.element);
    // make closeable
    var button = document.createElement('button');
        button.className = 'close';
        button.innerHTML = '&times;';
        button.addEventListener('click', this.close.bind(this));

    this.element.insertBefore(button, this.element.firstChild);
    this.element.style.top = (document.body.scrollTop + 20) + 'px';
    bloc.execute('autoload');
    if (this.progress) {
      this.progress.remove();
    }
  },
  show: function () {
    document.body.classList.add('locked');
    this.backdrop.style.height = document.body.scrollHeight + 'px';
    this.backdrop.classList.add('viewing');
  },
  close: function (evt) {
    if (evt instanceof Event) {
      evt.preventDefault();
    }

    document.body.classList.remove('locked');
    this.backdrop.parentNode.removeChild(this.backdrop);
    this.backdrop = false;

    if (evt instanceof Function) {
      evt.call(this);
    }
  }
};


Modal.Form = function (callbacks) {
  this.modal = new Modal(null);
  this.form  = false;
  this.ajax  = new XMLHttpRequest();
  this.callbacks = {
    submit: function(){},
    load: null
  };
  // this.ajax.timeout = 3500;
  this.ajax.overrideMimeType('text/xml');
  this.ajax.addEventListener('load', this.processForm.bind(this));

  this.ajax.addEventListener('error', function () {
    alert('Unable to retrieve the form, please send word.');
    this.modal.close();
  }.bind(this));

  this.ajax.addEventListener('timeout', function (evt) {
    alert('The server is taking too long to respond, if this issue persists, please send word.');
    this.modal.close();
  }.bind(this));

  for (var key in callbacks) {
    this.addEvent(key, callbacks[key]);
  }
}; Modal.Form.prototype = {
  load: function (url) {
    this.ajax.id = "GET " + url;
    this.modal.show();
    this.ajax.open('GET', url);
    this.ajax.send();
  },
  addEvent: function (evt, callback) {
    this.callbacks[evt] = callback;
  },
  processForm: function (evt) {
    evt.target.responseXML.querySelectorAll('body script[async]').forEach(function (script) {
      document.head.appendChild(window.bloc.tag(false)).text = script.text;
    });

    // No form means we need to load up one via our ajax object
    if (!this.form) {
      this.form  = evt.target.responseXML.querySelector('form.editor');

      if (! this.form) {
       evt.target.dispatchEvent(new ProgressEvent('error'));
      }

      this.modal.addElement(this.form);

      this.form.addEventListener('submit', function (evt) {
        evt.preventDefault();
        var submit_request = new XMLHttpRequest();

       submit_request.timeout = 3500;
       submit_request.overrideMimeType('text/xml');
       submit_request.addEventListener('load', this.callbacks.submit.bind(this));
       submit_request.open("POST", this.form.action);
       submit_request.send(new FormData(this.form));

      }.bind(this));

      if (this.callbacks.load) {
        this.callbacks.load.call(this, this.form);
      }

    }
  }
};




var Edge = function (container, data) {
  container.parentNode.querySelectorAll(data.selector).forEach(function (button) {
    button.addEventListener('click', function (evt) {
      evt.preventDefault();
      var button = this;
      (new Modal.Form({
        load: function (form) {
          form.querySelector('input').focus();
        },
        submit: function (evt) {
          var elem = evt.target.responseXML.querySelector("div.edge");
          button.parentNode.appendChild(elem);

          setTimeout(function () {
            elem.classList.add('highlight');
          }, 10);

          setTimeout(function () {
            elem.classList.remove('highlight');
          }, 1000);

          // close modal
          this.modal.close(function (arg) {
            if (this.backdrop) {
              this.backdrop.parentNode.removeChild(this.backdrop);
            }
          });
        }
      })).load(this.href+'.xml');
    });
  });

  document.querySelector('fieldset.edges').addEventListener('click', function (evt) {
    if (evt.srcElement.nodeName == 'DT') {
      var priority = evt.target.parentNode.dataset.priority;
      evt.target.parentNode.dataset.priority = priority == 'low' ? 'normal' : 'low';
    }
  }, false);

  document.querySelectorAll('a.sort').forEach(function (button) {
    button.addEventListener('click', function (evt) {
      evt.preventDefault();
      var list = this.parentNode;
      list.classList.add('sorting');
      list.removeChild(this);
      sortable(list, 'div');

    }, false);
  });
};

Edge.finder = function (input) {
  input.removeAttribute('onfocus');
  var search = new Search(null, input);

  search.subscribers.select.push(function (dataset) {

    if (dataset.id) {
      this.input.form.id.value = dataset.id;
      var event = document.createEvent("HTMLEvents");
          event.initEvent("submit", false, false);
      this.input.form.dispatchEvent(event);
    } else {
      (new Modal.Form({
        submit: function (evt) {
          if (evt.target.responseXML.documentElement.querySelector('form')) {
            search.input.value      = evt.target.responseXML.querySelector('form input[name*=title]').value;
            search.input.dataset.id = evt.target.responseXML.querySelector('form input[name*=id]').value;
            search.select();
            this.modal.close();
          } else {
            console.error('Submitted twice.');
          }
        },
        load: function (form) {
          var field = form.querySelector('input[name*=title]');
          field.value = dataset.text;
          field.focus();
        }
      })).load('/manage/create/'+ dataset.topic + '.xml');
    }
  });
};




var Spectra = function(container, data) {
  var labels = document.querySelectorAll(data.selector);
  var total = labels.length;
  for (var i = 0; i < total; i++) {
    labels[i].dataset.index = (i / total);
    this.color.call(labels[i]);
    labels[i].addEventListener('input', this.color);
    labels[i].addEventListener('change', this.correlate);
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
Spectra.prototype.correlate = function (evt) {
  var ajax = new XMLHttpRequest();
  var replace = document.querySelector('fieldset.recommended .recommended');

  ajax.addEventListener('load', function (evt) {
    var elem = evt.target.responseXML.querySelector('.recommended');
    replace.parentNode.replaceChild(elem, replace);
  }, false);

  ajax.open("POST", '/manage/correlate.xml');

  ajax.send(new FormData(this.form));

};






function sortable(selector, targetname, onUpdate) {
   var dragEl;
   var rootEl = selector instanceof Element ? selector : document.querySelector(selector);
   // Making all siblings movable
   [].slice.call(rootEl.getElementsByTagName(targetname)).forEach(function (itemEl) {
       itemEl.draggable = true;
   });

   // Function responsible for sorting
   function _onDragOver(evt) {
       evt.preventDefault();
       evt.dataTransfer.dropEffect = 'move';

       var target = evt.target;

       if( target && target !== dragEl && target.nodeName == targetname.toUpperCase() ){
         // Sorting
         var rect = target.getBoundingClientRect();
         var topnext = (evt.clientY - rect.top)/(rect.bottom - rect.top) > 0.5;
         var leftnext = (evt.clientX - rect.left)/(rect.right - rect.left) > 0.5;
         rootEl.insertBefore(dragEl, (topnext && target.nextSibling) || (leftnext && target.nextSibling) || target);
       }
   }

   // End of sorting
   function _onDragEnd(evt){
       evt.preventDefault();

       dragEl.classList.remove('ghost');
       rootEl.removeEventListener('dragover', _onDragOver, false);
       rootEl.removeEventListener('dragend', _onDragEnd, false);


       // Notification about the end of sorting
       (onUpdate || function () {}).call(this, dragEl);
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
