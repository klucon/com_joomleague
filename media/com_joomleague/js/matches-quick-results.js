document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('#matchList');
    if (!table) return;
    table.querySelectorAll('[data-quick-result]').forEach(group => {
        const button = group.querySelector('[data-quick-save]');
        const inputs = [...group.querySelectorAll('[data-quick-participant]')];
        const message = group.querySelector('[data-quick-message]');
        inputs.forEach(input => input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); button.click(); }
        }));
        button.addEventListener('click', async () => {
            if (button.disabled || inputs.some(input => {
                input.required = true;
                const valid = input.reportValidity();
                input.required = false;
                return !valid;
            })) return;
            const row = group.closest('[data-match-id]');
            const body = new URLSearchParams({option: 'com_joomleague', task: 'matchresult.quickSave', format: 'json', match_id: row.dataset.matchId, round_id: table.dataset.roundId, revision: group.dataset.revision});
            body.set(table.dataset.csrfToken, '1');
            inputs.forEach(input => body.set(`scores[${input.dataset.quickParticipant}]`, input.value));
            button.disabled = true;
            inputs.forEach(input => { input.disabled = true; });
            message.className = 'small text-body-secondary';
            message.textContent = table.dataset.saving;
            try {
                const response = await fetch('index.php?option=com_joomleague&task=matchresult.quickSave&format=json', {method: 'POST', credentials: 'same-origin', body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const result = await response.json();
                if (!response.ok || !result.success || !/^[a-f0-9]{64}$/.test(result.data?.revision || '')) throw new Error(result.message || group.dataset.failed);
                group.dataset.revision = result.data.revision;
                message.className = 'small text-success';
                message.textContent = table.dataset.saved;
                const badge = group.querySelector('[data-quick-result-status]');
                badge.className = 'badge bg-success';
                badge.textContent = group.dataset.final;
                row.querySelectorAll('[data-schedule-field^="participant_slot_"]').forEach(select => {
                    select.disabled = true;
                    select.title = group.dataset.locked;
                    if (!select.parentElement.querySelector('.icon-lock')) {
                        const icon = document.createElement('span');
                        icon.className = 'icon-lock ms-1';
                        icon.tabIndex = 0;
                        icon.setAttribute('role', 'img');
                        icon.setAttribute('aria-label', group.dataset.locked);
                        icon.title = group.dataset.locked;
                        select.after(icon);
                    }
                });
            } catch (error) {
                message.className = 'small text-danger';
                message.textContent = error instanceof SyntaxError ? group.dataset.failed : (error.message || group.dataset.failed);
            } finally {
                button.disabled = false;
                inputs.forEach(input => { input.disabled = false; });
            }
        });
    });
});
