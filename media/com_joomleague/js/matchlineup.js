document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.id !== 'adminForm') return;
    const task = event.submitter?.dataset.lineupTask;
    if (!['matchlineup.addSubstitution', 'matchlineup.removeSubstitution'].includes(task)) return;
    const field = form.querySelector('input[type="hidden"][name="task"]');
    if (field) field.value = task;
});
