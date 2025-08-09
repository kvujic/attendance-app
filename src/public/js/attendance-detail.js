if (typeof window.breakIndex === 'undefined') {
    window.breakIndex = 1;
}

function isValidTimeFormat(time) {
    return /^\d{2}:\d{2}$/.test(time);
}

//if (start && end && !nextStartExists) {
function addNewBreakRow(index) {

    //not add the new row when it's pending
    if (window.isPending) return;

    //not add if same name of input exists
    if (document.getElementsByName(`breaks[${index}][requested_break_start]`).length > 0) {
        return;
    }

    const tbody = document.querySelector('.attendance-detail__table tbody');
    const tr = document.createElement('tr');

    tr.className = 'attendance-detail__table-row break-row';
    tr.innerHTML = `
        <th class="detail-label">休憩${index + 1}</th>
        <td class="detail-data">
            <input type="text" class="data__input-time" name="breaks[${index}][requested_break_start]" oninput="maybeAddRow(${index})">
            <span class="tilde">〜</span>
            <input type="text" class="data__input-time" name="breaks[${index}][requested_break_end]" oninput="maybeAddRow(${index})">
        </td>
    `;

    const breakRows = tbody.querySelectorAll('.break-row');
    const lastBreakRow = breakRows[breakRows.length - 1];
    tbody.insertBefore(tr, lastBreakRow ? lastBreakRow.nextSibling : null);

    // continue auto-addition only before application
    if (!window.isPending) {
        const StartEl = tr.querySelector(`input[name="breaks[${index}][requested_break_start]"]`);
        const endEl = tr.querySelector(`input[name="breaks[${index}][requested_break_end]"]`);
        const handler = () => maybeAddRow(index);
        StartEl.addEventListener('input', handler);
        endEl.addEventListener('input', handler);
    }

    // update index
    window.breakIndex += 1;

    console.log(`Row inserted at index ${index}`);
}

function maybeAddRow(index) {

    // waiting for approval will not add 
    if (window.isPending) return;

    const start = document.querySelector(`input[name="breaks[${index}][requested_break_start]"]`)?.value;
    const end = document.querySelector(`input[name="breaks[${index}][requested_break_end]"]`)?.value;

    const nextExists = document.getElementsByName(`breaks[${index + 1}][requested_break_start]`).length > 0;

    if (isValidTimeFormat(start) && isValidTimeFormat(end) && !nextExists) {
        addNewBreakRow(index + 1);
        window.breakIndex = index + 2;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded triggered');

    if (window.isPending) return;

    const tbody = document.getElementById('break-rows') || document.querySelector('.attendance-detail__table tbody');
    if (!tbody) return;

    const breakRows = document.querySelectorAll('.break-row');
    const lastIndex = breakRows.length ? breakRows.length - 1 : 0;

    //const lastIndex = window.breakIndex - 1;
    const start = document.querySelector(`input[name="breaks[${lastIndex}][requested_break_start]"]`)?.value;
    const end = document.querySelector(`input[name="breaks[${lastIndex}][requested_break_end]"]`)?.value;
    const nextExists = document.getElementsByName(`breaks[${lastIndex + 1}][requested_break_start]`).length > 0;

    console.log(`DOMContentLoaded check: breaks[${lastIndex}] -> $requested_break_start: ${start}, requested_break_end: ${end}`);

    if (start && end && !nextExists) {
        console.log('Adding new row');
        addNewBreakRow(lastIndex + 1);
        window.breakIndex = lastIndex + 2;
    }
});