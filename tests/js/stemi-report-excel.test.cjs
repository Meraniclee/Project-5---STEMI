const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const JSZip = require('../../public/assets/vendor/jszip.min.js');
const sandbox = { module: { exports: {} } };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/assets/js/stemi-report-export.js'), 'utf8'), sandbox);
const { createExcelWorkbook } = sandbox.module.exports;
(async () => {
    const longNotes = 'Abnormal ECG review. 中文 日本語 ♥ & < > "'.repeat(2500);
    const data = {
        patient: { id: 1, label: 'STEMI-100', age: 'Adult', sex: 'Female' },
        generatedAt: '22 Oct 2026 17:00 Asia/Kuala_Lumpur',
        records: [
            {
                id: 11, examinationId: 101, doctor: 'Dr Ahmad', examinationStatus: 'In Progress',
                image: 'ECG_A.jpg', status: 'Partial findings saved', updatedAt: '21 Oct 2026',
                sections: [
                    { title: 'Lead Findings - I', rows: [ { label: 'ST elevation', value: '0 mm' } ] },
                    { title: "Doctor's Notes", rows: [
                        { label: 'Clinical note', value: longNotes },
                        { label: 'Literal field', value: '=HYPERLINK("http://example.test")' }
                    ] }
                ]
            },
            {
                id: 12, examinationId: 102, doctor: 'Dr Lim', examinationStatus: 'Completed',
                image: 'ECG_B.png', status: 'All required findings recorded', updatedAt: '22 Oct 2026',
                sections: [ { title: 'Overall ECG Interpretation', rows: [
                    { label: 'MI localisation', value: 'Anterior, Lateral' }
                ] } ]
            }
        ]
    };
    const output = await createExcelWorkbook(data, JSZip, 'nodebuffer');
    const zip = await JSZip.loadAsync(output);
    const expected = ['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml',
        'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml'];
    for (const name of expected) assert.ok(zip.file(name), 'Missing ' + name);
    const summary = await zip.file('xl/worksheets/sheet1.xml').async('string');
    const findings = await zip.file('xl/worksheets/sheet2.xml').async('string');
    const workbook = await zip.file('xl/workbook.xml').async('string');
    assert.ok(workbook.includes('name="Summary"') && workbook.includes('name="Findings"'));
    assert.ok(summary.includes('ECG_A.jpg') && summary.includes('ECG_B.png'));
    assert.ok(findings.includes('0 mm') && findings.includes('Anterior, Lateral'));
    assert.ok(findings.includes('中文 日本語 ♥ &amp; &lt; &gt;'));
    assert.ok(findings.includes('=HYPERLINK(&quot;http://example.test&quot;)'));
    assert.ok(!findings.includes('<f>'), 'Saved notes must not become formulas');
    assert.ok(findings.includes('continued'), 'Clinical notes beyond Excel cell limit must be continued');
    console.log('Excel export: workbook contents, multiple records, long notes, Unicode, XML escaping and formula safety passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
