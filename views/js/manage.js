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

// var editor = new Editor();
