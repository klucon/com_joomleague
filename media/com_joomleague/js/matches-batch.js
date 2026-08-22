document.addEventListener('DOMContentLoaded', () => {
  const applyBtn = document.getElementById('matches-batch-apply');
  const form = document.getElementById('adminForm');
  if (!applyBtn || !form) return;

  const venueField = document.getElementById('batch_venue_id');
  const shiftField = document.getElementById('batch_shift_days');
  const warning = document.getElementById('matches-batch-warning');

  applyBtn.addEventListener('click', () => {
    const anySelected = form.querySelectorAll('input[name="cid[]"]:checked').length > 0;
    if (!anySelected) {
      warning.textContent = warning.dataset.noneSelected;
      warning.classList.remove('d-none');
      return;
    }

    const hasVenue = venueField && venueField.value !== '';
    const hasShift = shiftField && shiftField.value.trim() !== '';
    if (!hasVenue && !hasShift) {
      warning.textContent = warning.dataset.noChanges;
      warning.classList.remove('d-none');
      return;
    }

    warning.classList.add('d-none');
    form.task.value = 'matches.batchApply';
    form.submit();
  });
});
