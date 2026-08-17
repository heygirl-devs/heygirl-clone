(function(){
  function wrap(ta, before, after){
    after = after || before;
    var s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.substring(s, e);
    if (sel) {
      ta.value = ta.value.substring(0,s) + before + sel + after + ta.value.substring(e);
      ta.selectionStart = s + before.length;
      ta.selectionEnd = e + before.length;
    } else {
      ta.value = ta.value.substring(0,s) + before + after + ta.value.substring(e);
      ta.selectionStart = ta.selectionEnd = s + before.length;
    }
    ta.focus();
  }
  function prefixLine(ta, prefix){
    var s = ta.selectionStart;
    var v = ta.value;
    var lineStart = v.lastIndexOf('\n', s-1) + 1;
    ta.value = v.substring(0,lineStart) + prefix + v.substring(lineStart);
    ta.selectionStart = ta.selectionEnd = s + prefix.length;
    ta.focus();
  }
  function insert(ta, text){
    var s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.substring(0,s) + text + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + text.length;
    ta.focus();
  }
  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('[data-md]');
    if (!btn) return;
    ev.preventDefault();
    var bar = btn.closest('.fh-editor');
    var ta = bar ? bar.querySelector('textarea') : null;
    if (!ta) return;
    var cmd = btn.getAttribute('data-md');
    if (cmd === 'bold')      wrap(ta, '**');
    else if (cmd === 'italic') wrap(ta, '*');
    else if (cmd === 'list')   prefixLine(ta, '- ');
    else if (cmd === 'br')     insert(ta, '\n');
    else if (cmd === 'emoji') {
      var panel = bar.querySelector('.fh-emoji-panel');
      if (panel) panel.style.display = (panel.style.display==='none'||!panel.style.display) ? 'block' : 'none';
    }
  });
  document.addEventListener('click', function(ev){
    var em = ev.target.closest('[data-emoji]');
    if (!em) return;
    ev.preventDefault();
    var bar = em.closest('.fh-editor');
    var ta = bar ? bar.querySelector('textarea') : null;
    if (ta) insert(ta, em.getAttribute('data-emoji'));
  });
})();