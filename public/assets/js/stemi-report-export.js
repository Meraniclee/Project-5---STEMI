(function (root, factory) {
    'use strict';
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root && root.document) api.attach(root);
}(typeof window !== 'undefined' ? window : null, function () {
    'use strict';
    const PAGE = { width: 1240, height: 1754, margin: 76, top: 184, bottom: 1634 };
    const WIDTH = PAGE.width - PAGE.margin * 2;
    const LABEL_WIDTH = 408;
    const FONT = 'Arial, sans-serif';
    const clean = value => String(value == null ? '' : value).replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]/g, '');

    // Keep every word, including long unbroken notes, within the printable column.
    function wrapText(value, width, measure) {
        const lines = [];
        for (const paragraph of clean(value).replace(/\r\n?/g, '\n').split('\n')) {
            let line = '';
            for (const word of paragraph.trim().split(/\s+/).filter(Boolean)) {
                const candidate = line ? line + ' ' + word : word;
                if (measure(candidate) <= width) { line = candidate; continue; }
                if (line) { lines.push(line); line = ''; }
                for (const character of Array.from(word)) {
                    if (line && measure(line + character) > width) { lines.push(line); line = ''; }
                    line += character;
                }
            }
            lines.push(line);
        }
        return lines.length ? lines : [''];
    }

    function shorten(value, width, measure) {
        let text = clean(value).replace(/\s+/g, ' ');
        if (measure(text) <= width) return text;
        while (text && measure(text + '…') > width) text = Array.from(text).slice(0, -1).join('');
        return text + '…';
    }

    // Layout is separate from drawing so pagination and preservation of answers can be tested.
    function layoutReport(data, measureText) {
        const pages = [];
        const measure = (size, bold) => text => measureText(text, size, !!bold);
        let page, y, recordIndex = 0, sectionTitle = '';
        const text = (value, x, top, size, bold, color) => page.operations.push({
            kind: 'text', value: clean(value), x, y: top, size, bold: !!bold, color: color || '#18394f'
        });
        const rect = (x, top, width, height, color) => page.operations.push({ kind: 'rect', x, y: top, width, height, color });
        function nextPage() {
            page = { recordIndex, operations: [] };
            pages.push(page);
            rect(0, 0, PAGE.width, PAGE.height, '#ffffff');
            text('STEMI.', PAGE.margin, 55, 38, true, '#048ed0');
            text("Patient's Examination Record", PAGE.margin, 106, 29, true);
            text(shorten(data.patient.label, 660, measure(20)), PAGE.margin, 150, 20, false, '#516b7d');
            text('Record ' + (recordIndex + 1) + ' of ' + data.records.length, 962, 150, 20, false, '#516b7d');
            rect(PAGE.margin, 174, WIDTH, 2, '#bcd9e9');
            y = PAGE.top;
        }
        function heading(title, continued) {
            const lines = wrapText(title + (continued ? ' (continued)' : ''), WIDTH - 28, measure(25, true));
            const height = lines.length * 32 + 22;
            rect(PAGE.margin, y, WIDTH, height, '#e4f2fc');
            lines.forEach((line, i) => text(line, PAGE.margin + 14, y + 11 + i * 32, 25, true, '#075b89'));
            y += height + 6;
        }
        function row(item) {
            const labels = wrapText(item.label, LABEL_WIDTH - 28, measure(21, true));
            const values = wrapText(item.value, WIDTH - LABEL_WIDTH - 28, measure(23));
            const totalLines = Math.max(labels.length, values.length);
            let offset = 0;
            while (offset < totalLines) {
                let available = Math.floor((PAGE.bottom - y - 24) / 31);
                if (available < 1) { nextPage(); heading(sectionTitle, true); available = Math.floor((PAGE.bottom - y - 24) / 31); }
                const count = Math.min(totalLines - offset, available);
                const height = count * 31 + 24;
                rect(PAGE.margin, y, LABEL_WIDTH, height, '#f3f7fa');
                rect(PAGE.margin + LABEL_WIDTH, y, WIDTH - LABEL_WIDTH, height, '#fbfdff');
                for (let i = 0; i < count; i++) {
                    if (labels[offset + i]) text(labels[offset + i], PAGE.margin + 14, y + 12 + i * 31, 21, true);
                    if (values[offset + i]) text(values[offset + i], PAGE.margin + LABEL_WIDTH + 14, y + 12 + i * 31, 23, false,
                        item.value === 'Not recorded' ? '#647784' : '#18394f');
                }
                rect(PAGE.margin, y + height - 1, WIDTH, 1, '#dce8ef');
                y += height;
                offset += count;
            }
        }
        function section(title, rows) {
            sectionTitle = title;
            const headingHeight = wrapText(title, WIDTH - 28, measure(25, true)).length * 32 + 28;
            if (y + headingHeight + 100 > PAGE.bottom) nextPage();
            heading(title, false);
            rows.forEach(row);
            y += 20;
        }
        data.records.forEach((record, index) => {
            recordIndex = index;
            nextPage();
            section('Examination details', [
                { label: 'Patient', value: data.patient.label },
                { label: 'Age category / Sex', value: (data.patient.age || 'Not recorded') + ' / ' + (data.patient.sex || 'Not recorded') },
                { label: 'Examined by', value: record.doctor },
                { label: 'Examination / Review', value: '#' + record.examinationId + ' - ' + record.examinationStatus + ' / ' + record.status },
                { label: 'ECG image', value: record.image },
                { label: 'Last saved', value: record.updatedAt },
            ]);
            record.sections.forEach(item => section(item.title, item.rows));
        });
        pages.forEach((item, index) => {
            page = item;
            rect(PAGE.margin, 1665, WIDTH, 1, '#bcd9e9');
            text(shorten('Saved findings | Generated ' + data.generatedAt, 900, measure(18)), PAGE.margin, 1683, 18, false, '#516b7d');
            text('Page ' + (index + 1) + ' / ' + pages.length, 1000, 1683, 18, true, '#516b7d');
        });
        return pages;
    }

    function drawPage(canvas, page) {
        canvas.width = PAGE.width;
        canvas.height = PAGE.height;
        const context = canvas.getContext('2d');
        context.textBaseline = 'top';
        for (const op of page.operations) {
            context.fillStyle = op.color;
            if (op.kind === 'rect') context.fillRect(op.x, op.y, op.width, op.height);
            else {
                context.font = (op.bold ? 'bold ' : '') + op.size + 'px ' + FONT;
                context.fillText(op.value, op.x, op.y);
            }
        }
    }

    function blobFromCanvas(canvas, format) {
        return new Promise((resolve, reject) => canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Unable to create image.')),
            format === 'png' ? 'image/png' : 'image/jpeg', 0.94));
    }

    function filename(label) {
        return 'STEMI-' + clean(label).replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^-|-$/g, '').slice(0, 80);
    }

    // Native Excel .xlsx export: preserve saved answers as editable text cells.
    // Uses the already bundled JSZip library; no PHP, database or network changes.
    function xmlText(value) {
        const raw = clean(value);
        let result = '';
        for (const character of raw) {
            const code = character.codePointAt(0);
            if (code >= 0xD800 && code <= 0xDFFF || code === 0xFFFE || code === 0xFFFF) continue;
            if (character === '&') result += '&amp;';
            else if (character === '<') result += '&lt;';
            else if (character === '>') result += '&gt;';
            else if (character === '"') result += '&quot;';
            else if (character === "'") result += '&apos;';
            else result += character;
        }
        return result;
    }

    function excelColumn(index) {
        let name = '';
        for (let position = index + 1; position > 0; position = Math.floor((position - 1) / 26)) {
            name = String.fromCharCode(65 + (position - 1) % 26) + name;
        }
        return name;
    }

    // Excel limits one cell to 32,767 UTF-16 characters. Split very long
    // medical notes into adjacent continuation rows without losing content.
    function excelChunks(value) {
        const text = clean(value);
        const chunks = [];
        let part = '';
        for (const char of text) {
            if (part.length + char.length > 32000) {
                chunks.push(part);
                part = '';
            }
            part += char;
        }
        chunks.push(part);
        return chunks;
    }

    const excelRow = (values, style = 4, height = 21) => ({
        cells: values.map(value => ({ value, style })), height
    });

    function sheetXml(rows, widths, merged, headerRow) {
        const lastColumn = excelColumn(widths.length - 1);
        let body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        body += '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        body += '<sheetViews><sheetView workbookViewId="0">';
        body += '<pane ySplit="' + headerRow + '" topLeftCell="A' + (headerRow + 1) + '" activePane="bottomLeft" state="frozen"/>';
        body += '<selection pane="bottomLeft" activeCell="A' + (headerRow + 1) + '" sqref="A' + (headerRow + 1) + '"/>';
        body += '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="21"/>';
        body += '<cols>' + widths.map((width, index) => '<col min="' + (index + 1) + '" max="' + (index + 1) + '" width="' + width + '" customWidth="1"/>').join('') + '</cols>';
        body += '<sheetData>';
        rows.forEach((row, rowIndex) => {
            const number = rowIndex + 1;
            body += '<row r="' + number + '" ht="' + row.height + '" customHeight="1">';
            row.cells.forEach((cell, colIndex) => {
                if (cell.value == null || cell.value === '') return;
                const ref = excelColumn(colIndex) + number;
                // inlineStr keeps all data literal: notes beginning with '=' are
                // never interpreted as spreadsheet formulas.
                body += '<c r="' + ref + '" s="' + cell.style + '" t="inlineStr"><is><t xml:space="preserve">' + xmlText(cell.value) + '</t></is></c>';
            });
            body += '</row>';
        });
        body += '</sheetData>';
        if (headerRow && rows.length >= headerRow) body += '<autoFilter ref="A' + headerRow + ':' + lastColumn + rows.length + '"/>';
        if (merged) body += '<mergeCells count="1"><mergeCell ref="A1:' + lastColumn + '1"/></mergeCells>';
        body += '</worksheet>';
        return body;
    }

    function excelReportSheets(data) {
        const summary = [
            excelRow(["STEMI | Patient's Examination Record"], 1, 36),
            excelRow(['Report generated', data.generatedAt], 3),
            excelRow(['Patient ID', data.patient.label], 3),
            excelRow(['Age category', data.patient.age], 3),
            excelRow(['Sex', data.patient.sex], 3),
            excelRow(['Saved ECG records', String(data.records.length)], 3),
            excelRow([], 4),
            excelRow(['Examination records and clinical findings are available in the Findings worksheet.'], 4, 29),
            excelRow(['Patient ID', 'Examination ID', 'ECG Image', 'Doctor', 'Examination Status', 'Findings Status', 'Last Saved'], 2, 32),
        ];
        for (const record of data.records) {
            summary.push(excelRow([
                data.patient.label, record.examinationId ?? 'Not recorded', record.image,
                record.doctor, record.examinationStatus, record.status, record.updatedAt
            ], summary.length % 2 === 0 ? 5 : 4, 26));
        }

        const findings = [
            excelRow(['STEMI | Saved ECG Examination Findings'], 1, 36),
            excelRow(['Patient ID', data.patient.label, 'Generated', data.generatedAt], 3, 25),
            excelRow(['Records', String(data.records.length)], 3),
            excelRow([], 4),
            excelRow([
                'Patient ID', 'Examination ID', 'ECG Image', 'Doctor', 'Examination Status',
                'Findings Status', 'Last Saved', 'Finding Section', 'Field', 'Saved Answer'
            ], 2, 32)
        ];
        for (const record of data.records) {
            for (const section of record.sections) {
                for (const item of section.rows) {
                    const chunks = excelChunks(item.value);
                    chunks.forEach((chunk, index) => {
                        findings.push(excelRow([
                            data.patient.label, record.examinationId ?? 'Not recorded', record.image,
                            record.doctor, record.examinationStatus, record.status,
                            record.updatedAt, section.title,
                            index === 0 ? item.label : item.label + ' (continued ' + (index + 1) + ')',
                            chunk
                        ], findings.length % 2 === 0 ? 5 : 4, 33));
                    });
                }
            }
        }
        return {
            summary: sheetXml(summary, [23, 21, 35, 27, 26, 32, 27], true, 9),
            findings: sheetXml(findings, [23, 20, 35, 26, 27, 33, 27, 29, 40, 90], true, 5)
        };
    }

    function createExcelWorkbook(data, JSZipConstructor, outputType = 'blob') {
        if (!JSZipConstructor) throw new Error('Excel export library is not available.');
        const sheets = excelReportSheets(data);
        const zip = new JSZipConstructor();
        zip.file('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '</Types>');
        zip.file('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
            '</Relationships>');
        zip.file('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Summary" sheetId="1" r:id="rId1"/>' +
            '<sheet name="Findings" sheetId="2" r:id="rId2"/></sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>' +
            '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
            '</Relationships>');
        zip.file('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="3"><font><sz val="11"/><name val="Aptos"/><color rgb="FF18394F"/></font>' +
            '<font><b/><sz val="17"/><name val="Aptos Display"/><color rgb="FF0874D0"/></font>' +
            '<font><b/><sz val="11"/><name val="Aptos"/><color rgb="FFFFFFFF"/></font></fonts>' +
            '<fills count="4"><fill><patternFill patternType="none"/></fill>' +
            '<fill><patternFill patternType="gray125"/></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FF0874D0"/><bgColor indexed="64"/></patternFill></fill>' +
            '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF7FC"/><bgColor indexed="64"/></patternFill></fill></fills>' +
            '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' +
            '<border><left/><right/><top/><bottom style="hair"><color rgb="FFDCE9F2"/></bottom><diagonal/></border></borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="6">' +
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>' +
            '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>' +
            '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>' +
            '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>');
        zip.file('xl/worksheets/sheet1.xml', sheets.summary);
        zip.file('xl/worksheets/sheet2.xml', sheets.findings);
        return zip.generateAsync({ type: outputType, compression: 'DEFLATE', compressionOptions: { level: 6 } });
    }

    function attach(win) {
        const source = win.document.getElementById('examinationReportData');
        if (!source) return;
        const buttons = Array.from(win.document.querySelectorAll('[data-report-export]'));
        const status = win.document.getElementById('reportExportStatus');
        const print = win.document.querySelector('[data-report-print]');
        if (print) print.addEventListener('click', () => win.print());
        const say = message => { if (status) status.textContent = message; };
        let busy = false;
        async function exportReport(format) {
            if (busy) return;
            busy = true;
            buttons.forEach(button => { button.disabled = true; });
            let canvas;
            try {
                say('Preparing all saved examination records…');
                const data = JSON.parse(source.textContent);
                if (!data.records.length) throw new Error('There are no saved findings to export.');
                if (win.document.fonts) await win.document.fonts.ready;
                const base = filename(data.patient.label);
                if (format === 'xlsx') {
                    say('Building editable Excel workbook…');
                    const workbook = await createExcelWorkbook(data, win.JSZip);
                    const url = win.URL.createObjectURL(workbook);
                    const link = win.document.createElement('a');
                    link.href = url;
                    link.download = base + '.xlsx';
                    win.document.body.appendChild(link);
                    link.click();
                    link.remove();
                    win.setTimeout(() => win.URL.revokeObjectURL(url), 60000);
                    say(data.records.length + ' saved ECG records exported as an editable Excel workbook.');
                    return;
                }
                if (format !== 'pdf' && format !== 'png') throw new Error('Unsupported report format.');
                if (format === 'pdf' && !win.PDFLib) throw new Error('PDF export library is not available.');
                if (format === 'png' && !win.JSZip) throw new Error('PNG export library is not available.');
                canvas = win.document.createElement('canvas');
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Image export is unavailable in this browser. Use Print to save a PDF.');
                const pages = layoutReport(data, (text, size, bold) => {
                    context.font = (bold ? 'bold ' : '') + size + 'px ' + FONT;
                    return context.measureText(text).width;
                });
                const pdf = format === 'pdf' ? await win.PDFLib.PDFDocument.create() : null;
                const zip = format === 'pdf' ? null : new win.JSZip();
                if (pdf) {
                    pdf.setTitle(data.patient.label + " - Patient's Examination Record");
                    pdf.setAuthor('STEMI');
                    pdf.setSubject('Saved clinician-entered ECG examination findings');
                }
                for (let i = 0; i < pages.length; i++) {
                    say('Preparing page ' + (i + 1) + ' of ' + pages.length + '…');
                    drawPage(canvas, pages[i]);
                    const blob = await blobFromCanvas(canvas, format);
                    const bytes = new Uint8Array(await blob.arrayBuffer());
                    if (pdf) {
                        const image = await pdf.embedJpg(bytes);
                        const page = pdf.addPage([595.28, 841.89]);
                        page.drawImage(image, { x: 0, y: 0, width: 595.28, height: 841.89 });
                    } else zip.file(base + '-page-' + String(i + 1).padStart(3, '0') + '.' + format, bytes);
                }
                say('Packaging ' + pages.length + ' pages…');
                const result = pdf ? new Blob([await pdf.save()], { type: 'application/pdf' })
                    : await zip.generateAsync({ type: 'blob', compression: 'STORE' });
                const url = win.URL.createObjectURL(result);
                const link = win.document.createElement('a');
                link.href = url;
                link.download = base + (pdf ? '.pdf' : '-' + format + '.zip');
                win.document.body.appendChild(link);
                link.click();
                link.remove();
                win.setTimeout(() => win.URL.revokeObjectURL(url), 60000);
                say(pages.length + ' pages exported as ' + (pdf ? 'PDF.' : format.toUpperCase() + ' images in one ZIP file.'));
            } catch (error) {
                say('Export failed. ' + (error.message || 'Please try again, or use Print to save a PDF.'));
            } finally {
                if (canvas) { canvas.width = 1; canvas.height = 1; }
                busy = false;
                buttons.forEach(button => { button.disabled = false; });
            }
        }
        buttons.forEach(button => button.addEventListener('click', () => exportReport(button.dataset.reportExport)));
    }

    return { wrapText, layoutReport, drawPage, filename, excelReportSheets, createExcelWorkbook, PAGE, attach };
}));
