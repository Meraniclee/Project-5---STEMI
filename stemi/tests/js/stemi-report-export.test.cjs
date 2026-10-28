const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const sandbox = { module: { exports: {} } };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/assets/js/stemi-report-export.js'), 'utf8'), sandbox);
const { wrapText, layoutReport, filename, PAGE } = sandbox.module.exports;
const measure = (text, size) => Array.from(text).length * size * 0.55;
const note = Array.from({ length: 1800 }, (_, i) => 'note' + i).join(' ');
const token = 'continuous'.repeat(400);
for (const value of [token, 'Multiple regions / I, II, aVR', 'Unicode 中文 日本語']) {
    const lines = wrapText(value, 100, s => measure(s, 20));
    assert.equal(lines.join('').replace(/\s/g, ''), value.replace(/\s/g, ''));
    assert.ok(lines.every(line => measure(line, 20) <= 100));
}
const base = {
    patient: { id: 1, label: 'Patient TEST', age: 'Adult', sex: 'Male' }, generatedAt: '22 Oct 2026',
    records: [{ id: 1, doctor: 'Dr Wong', examinationId: 12, examinationStatus: 'In progress', image: 'ECG.png',
        updatedAt: '22 Oct 2026', status: 'Draft', sections: [
            { title: 'Lead Findings - I', rows: [{ label: 'ST elevation measurement', value: '0 mm' }, { label: 'Signal quality', value: 'Adequate' }] },
            { title: "Doctor's notes", rows: [{ label: 'Notes', value: note + '\n' + token }] },
        ] }]
};
base.records.push({ ...base.records[0], id: 2, examinationId: 15, sections: [{ title: 'Latest record', rows: [{ label: 'Answer', value: 'Last-answer-marker' }] }] });
const pages = layoutReport(base, measure);
assert.ok(pages.length > 5, 'Long notes should span multiple pages');
const text = pages.flatMap(page => page.operations).filter(op => op.kind === 'text').map(op => op.value);
const content = text.join(' ');
assert.ok(content.includes('0 mm'));
assert.ok(content.includes('Adequate'));
assert.ok(content.includes('Last-answer-marker'));
for (const word of note.split(' ')) assert.equal(text.filter(line => (' ' + line + ' ').includes(' ' + word + ' ')).length, 1, 'Preserve ' + word);
const noteText = pages.filter(p => p.recordIndex === 0).flatMap(p => p.operations)
    .filter(op => op.kind === 'text' && op.x === PAGE.margin + 408 + 14 && op.size === 23).map(op => op.value).join('').replace(/\s/g, '');
assert.ok(noteText.includes(token), 'Unbroken long notes should survive page boundaries');
const record2 = pages.find(p => p.recordIndex === 1);
assert.ok(record2.operations.some(op => op.value === 'Examination details'), 'Each record starts its own page');
for (const [index, page] of pages.entries()) {
    assert.ok(page.operations.some(op => op.value === `Page ${index + 1} / ${pages.length}`));
    for (const op of page.operations) {
        assert.ok(op.y >= 0 && op.y < PAGE.height, 'Operation stays inside page');
        if (op.kind === 'text') assert.ok(op.x + measure(op.value, op.size) <= PAGE.width - 35, 'Text stays inside right edge: ' + op.value);
    }
}
assert.equal(layoutReport({ ...base, records: [] }, measure).length, 0);
assert.ok(!filename('../Patient:/123').includes('/'));
console.log('Report exports: wrapping, long notes, multiple records, numeric zero, page bounds and safe filenames passed.');
