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

bloc.prepare(function () {
  var stylesheet  = document.styleSheets.length - 1;

  while (stylesheet > 0 && typeof stylesheet === 'number') {
    if (document.styleSheets[stylesheet].title === 'administrator') {
      stylesheet = document.styleSheets[stylesheet];
      continue;
    }
    stylesheet--;
  }
  
  var size = window.getComputedStyle(document.querySelector('.text'), null).getPropertyValue("line-height");
  var bg   = btoa("<svg xmlns='http://www.w3.org/2000/svg' width='"+size+"' height='"+size+"' viewBox='0 0 50 50'><line x1='0' y1='50' x2='50' y2='50' stroke='#9DD1EF' fill='none'/></svg>");
  stylesheet.insertRule('.text:focus {background: transparent url(data:image/svg+xml;base64,'+bg+') repeat 0 '+ Math.floor((parseFloat(size) - 10) / 2) + 'px' +' !important; }', 0);
});

function Upload(url) {
  this.xhr = new XMLHttpRequest();
  this.xhr.open('POST', url);
  
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
}

Upload.prototype = {
  states: [
    'unsent',
    'opened',
    'headers',
    'loading',
    'complete'
  ],
  attach: function (blob) {
    this.xhr.send(blob); 
  }
};

