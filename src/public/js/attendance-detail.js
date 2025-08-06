if (typeof window.breakIndex === 'undefined') {
    window.breakIndex = 1;
}

function isValidTimeFormat(time) {
    return /^\d{2}:\d{2}$/.test(time);
}

//if (start && end && !nextStartExists) {
function addNewBreakRow(index) {
    const tbody = document.querySelector('.attendance-detail__table tbody');
    const tr = document.createElement('tr');
    tr.className = 'attendance-detail__table-row break-row';
    tr.innerHTML = `
        <th class="detail-label">休憩${index + 1}</th>
        <td class="detail-data">
            <input type="text" class="data__input-time" name="breaks[${index + 1}][start]" oninput="maybeAddRow(${index + 1})">
            <span class="tilde">〜</span>
            <input type="text" class="data__input-time" name="breaks[${index + 1}][end]" oninput="maybeAddRow(${index + 1})">
        </td>
    `;
    const breakRows = tbody.querySelectorAll('.break-row');
    const lastBreakRow = breakRows[breakRows.length - 1];
    tbody.insertBefore(tr, lastBreakRow.nextSibling);

    //window.breakIndex++;
    console.log(`Row inserted at index ${index + 1}`);
}



function maybeAddRow(index) {
    const start = document.querySelector(`input[name="breaks[${index}][start]"]`)?.value;
    const end = document.querySelector(`input[name="breaks[${index}][end]"]`)?.value;

    const nextStart = document.querySelector(`input[name="breaks[${index} + 1][start]"]`);

    if (isValidTimeFormat(start) && isValidTimeFormat(end) && !nextStart) {
        addNewBreakRow(index + 1);
    }
}


   /*
    const startInput = document.querySelector(`input[name="breaks[${index}][start]"]`);
    const endInput = document.querySelector(`input[name="breaks[${index}][end]"]`);

    const start = startInput?.value;
    const end = endInput?.value;

    // HH:mm形式で完全に入力されたときのみ
    if (isValidTimeFormat(start) && isValidTimeFormat(end)) {
        const nextStart = document.querySelector(`input[name="breaks[${index + 1}][start]"]`);

        // 次の行がまだない場合のみ追加
        if (!nextStart) {
            addNewBreakRow(index + 1);
        }
    }
    */





window.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded triggered');

    const breakRows = document.querySelectorAll('.break-row');
    const lastIndex = breakRows.length - 1;

    //const lastIndex = window.breakIndex - 1;
    const start = document.querySelector(`input[name="breaks[${lastIndex}][start]"]`)?.value;
    const end = document.querySelector(`input[name="breaks[${lastIndex}][end]"]`)?.value;
    const nextExists = document.querySelector(`input[name="breaks[${lastIndex + 1}][start]"]`);

    console.log(`DOMContentLoaded check: breaks[${lastIndex}] → start: ${start}, end: ${end}`);

    if (start && end && !nextExists) {
        console.log('Adding new row');
        maybeAddRow(lastIndex);
    }
});