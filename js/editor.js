/* Standalone editor enhancements: drag-drop and paste image uploads.
   The page works without this script (use file upload form / image URLs). */
(function () {
  'use strict';

  var zone = document.getElementById('image-drop-zone');
  var textarea = document.getElementById('content');
  if (!zone || !textarea) return;

  var fullId = zone.getAttribute('data-fullid') || '';
  var uploadUrl = zone.getAttribute('data-upload') || 'upload_image.php';
  var statusEl = document.getElementById('upload-status');
  if (!statusEl) {
    statusEl = document.createElement('p');
    statusEl.id = 'upload-status';
    statusEl.className = 'upload-status';
    statusEl.setAttribute('aria-live', 'polite');
    zone.appendChild(statusEl);
  }

  function setStatus(msg, isError) {
    statusEl.textContent = msg || '';
    statusEl.className = 'upload-status' + (isError ? ' upload-status-error' : (msg ? ' upload-status-ok' : ''));
  }

  function insertAtCursor(text) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    if (typeof start !== 'number') {
      textarea.value += (textarea.value && !/\n$/.test(textarea.value) ? '\n' : '') + text + '\n';
      return;
    }
    var before = textarea.value.substring(0, start);
    var after = textarea.value.substring(end);
    var prefix = (before && !/\n$/.test(before)) ? '\n' : '';
    var suffix = (after && !/^\n/.test(after)) ? '\n' : '';
    textarea.value = before + prefix + text + suffix + after;
    var pos = (before + prefix + text).length;
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = pos;
  }

  function uploadFile(file) {
    if (!fullId) {
      setStatus('Save the article first, then drag and drop images here. You can still paste an image URL as Markdown.', true);
      return;
    }
    if (!file || !/^image\//i.test(file.type || '')) {
      setStatus('Please drop an image file.', true);
      return;
    }
    setStatus('Uploading ' + (file.name || 'image') + '...', false);
    var fd = new FormData();
    fd.append('id', fullId);
    fd.append('image', file, file.name || 'image');
    fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        var j = res.json || {};
        if (!res.ok || !j.ok) {
          setStatus(j.error || 'Upload failed.', true);
          return;
        }
        insertAtCursor(j.markdown || ('![](' + (j.editorPath || j.path) + ')'));
        setStatus('Image uploaded.', false);
      })
      .catch(function () {
        setStatus('Upload failed. Use the upload form below (works without JavaScript).', true);
      });
  }

  function handleFiles(files) {
    if (!files || !files.length) return;
    for (var i = 0; i < files.length; i++) uploadFile(files[i]);
  }

  function prevent(e) { e.preventDefault(); e.stopPropagation(); }

  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
    zone.addEventListener(ev, prevent, false);
  });
  zone.addEventListener('dragover', function () { zone.className = zone.className.replace(/\s*drag-over/g, '') + ' drag-over'; }, false);
  ['dragleave', 'drop'].forEach(function (ev) {
    zone.addEventListener(ev, function () { zone.className = zone.className.replace(/\s*drag-over/g, ''); }, false);
  });
  zone.addEventListener('drop', function (e) {
    var dt = e.dataTransfer;
    if (dt && dt.files) handleFiles(dt.files);
  }, false);

  // Paste support (images from clipboard)
  textarea.addEventListener('paste', function (e) {
    var cd = e.clipboardData || window.clipboardData;
    if (!cd || !cd.items) return;
    var files = [];
    for (var i = 0; i < cd.items.length; i++) {
      var it = cd.items[i];
      if (it && it.kind === 'file' && /^image\//i.test(it.type || '')) {
        var f = it.getAsFile();
        if (f) files.push(f);
      }
    }
    if (files.length) {
      // Let text paste proceed if mixed; if only images, prevent default
      var hasText = false;
      for (var j = 0; j < cd.items.length; j++) { if (cd.items[j].kind === 'string') hasText = true; }
      if (!hasText) prevent(e);
      handleFiles(files);
    }
  }, false);
})();
