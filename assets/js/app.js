// Sélection du type de CR
function selectType(type) {
  document.getElementById('typeInput').value = type;

  document.querySelectorAll('.type-card').forEach(function(card) {
    card.classList.remove('active');
  });
  event.currentTarget.classList.add('active');

  if (type === 'chantier') {
    document.getElementById('form-chantier').style.display = '';
    document.getElementById('form-service').style.display  = 'none';
  } else {
    document.getElementById('form-chantier').style.display = 'none';
    document.getElementById('form-service').style.display  = '';
  }
}

// Init au chargement
document.addEventListener('DOMContentLoaded', function() {
  var type = document.getElementById('typeInput');
  if (type && type.value === 'service') {
    document.getElementById('form-chantier').style.display = 'none';
    document.getElementById('form-service').style.display  = '';
  }
});
