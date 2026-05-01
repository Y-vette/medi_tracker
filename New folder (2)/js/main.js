// Meditracker — main.js

// Symptom chip toggle (already handled inline, this is a fallback)
document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(a => {
    if (a.classList.contains('alert-success')) {
      setTimeout(() => { a.style.transition='opacity 0.4s'; a.style.opacity='0'; setTimeout(()=>a.remove(),400); }, 4000);
    }
  });

  // Confirm dangerous actions
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  });
});
