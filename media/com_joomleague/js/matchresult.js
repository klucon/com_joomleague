document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.id !== 'adminForm') return;
    const task = event.submitter?.dataset.resultTask;
    if (!['matchresult.addSegment', 'matchresult.removeSegment'].includes(task)) return;
    const field = form.querySelector('input[type="hidden"][name="task"]');
    if (field) field.value = task;
});
