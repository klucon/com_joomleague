document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('matchList');
  if (!table) return;

  const token = document.querySelector('#adminForm input[type="hidden"][value="1"]');
  const requests = new Map();

  const save = async (row) => {
    if (!token || row.dataset.editable !== '1') return;
    requests.get(row.dataset.matchId)?.abort();
    const controller = new AbortController();
    requests.set(row.dataset.matchId, controller);
    const status = row.querySelector('[data-save-status]');
    status.className = 'small text-body-secondary text-nowrap';
    status.textContent = table.dataset.saving;

    const data = new FormData();
    data.append(token.name, '1');
    data.append('match_id', row.dataset.matchId);
    data.append('round_id', table.dataset.roundId);
    row.querySelectorAll('[data-schedule-field]').forEach((field) => data.append(`schedule[${field.dataset.scheduleField}]`, field.value));

    try {
      const response = await fetch('index.php?option=com_joomleague&task=matches.saveInline&format=json', {
        method: 'POST', body: data, signal: controller.signal, headers: {'X-Requested-With': 'XMLHttpRequest'}
      });
      const payload = await response.json();
      if (!response.ok || payload.success === false) throw new Error(payload.message || table.dataset.failed);
      status.className = 'small text-success text-nowrap';
      status.textContent = table.dataset.saved;
    } catch (error) {
      if (error.name === 'AbortError') return;
      status.className = 'small text-danger';
      status.textContent = error.message || table.dataset.failed;
    } finally {
      if (requests.get(row.dataset.matchId) === controller) requests.delete(row.dataset.matchId);
    }
  };

  table.addEventListener('change', (event) => {
    const field = event.target.closest('select[data-schedule-field], input[type="date"][data-schedule-field], input[type="time"][data-schedule-field]');
    if (field) save(field.closest('tr'));
  });
  table.addEventListener('focusout', (event) => {
    const field = event.target.closest('input[data-schedule-field]');
    if (field && field.dataset.initialValue !== field.value) {
      field.dataset.initialValue = field.value;
      save(field.closest('tr'));
    }
  });
});
