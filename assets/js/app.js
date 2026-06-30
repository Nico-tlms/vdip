// Type CR selector
function selectType(type, el) {
  document.getElementById('typeInput').value = type;
  document.querySelectorAll('.type-card').forEach(function(c) { c.classList.remove('active'); });
  if (el) el.classList.add('active');

  document.getElementById('form-chantier').style.display = type === 'chantier' ? '' : 'none';
  document.getElementById('form-service').style.display  = type === 'service'  ? '' : 'none';
}

// Historique accordion
function toggleHistory(i) {
  var body = document.getElementById('body-' + i);
  var chv  = document.getElementById('chv-' + i);
  var open = body.classList.toggle('open');
  if (chv) chv.classList.toggle('open', open);
}

// Init
document.addEventListener('DOMContentLoaded', function() {
  // Smooth submit button
  var form = document.getElementById('reportForm');
  if (form) {
    form.addEventListener('submit', function() {
      var btn = form.querySelector('.btn-send');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-opacity=".3"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur=".8s" repeatCount="indefinite"/></path></svg> Envoi en cours...';
      }
    });
  }
});
