/* Standard 3 × 4 ECG report cropping. No waveform interpretation is performed. */
(() => {
    'use strict';

    const layout = Object.freeze([
        Object.freeze(['I', 'aVR', 'V1', 'V4']),
        Object.freeze(['II', 'aVL', 'V2', 'V5']),
        Object.freeze(['III', 'aVF', 'V3', 'V6']),
    ]);
    const finite = value => typeof value === 'number' && Number.isFinite(value);
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    function validate(crop) {
        if (!crop || !['left', 'top', 'width', 'height'].every(key => finite(crop[key]))) return false;
        if (crop.left < 0 || crop.top < 0 || crop.width <= 0 || crop.height <= 0) return false;
        if (crop.left + crop.width > 1.000001 || crop.top + crop.height > 1.000001) return false;
        return [['column_breaks', 3], ['row_breaks', 2]].every(([key, count]) => {
            const values = crop[key];
            return Array.isArray(values) && values.length === count && values.every((value, index) =>
                finite(value) && value > 0 && value < 1 && (index === 0 || value > values[index - 1]));
        });
    }

    function regions(crop) {
        if (!validate(crop)) throw new Error('The ECG crop boundaries are invalid.');
        const columns = [0, ...crop.column_breaks, 1];
        const rows = [0, ...crop.row_breaks, 1];
        const result = {};
        layout.forEach((leads, row) => leads.forEach((lead, column) => {
            result[lead] = {
                left: crop.left + columns[column] * crop.width,
                top: crop.top + rows[row] * crop.height,
                width: (columns[column + 1] - columns[column]) * crop.width,
                height: (rows[row + 1] - rows[row]) * crop.height,
            };
        }));
        return result;
    }

    function imageSize(image) {
        const width = image.naturalWidth || image.width;
        const height = image.naturalHeight || image.height;
        if (!(width > 0 && height > 0)) throw new Error('Wait for the ECG image to finish loading.');
        return {width, height};
    }

    function render(image, crop, lead, canvas) {
        const {width, height} = imageSize(image);
        const region = regions(crop)[lead];
        if (!region) throw new Error('Unknown ECG lead.');
        const source = [region.left * width, region.top * height, region.width * width, region.height * height];
        // Keep the native crop resolution for zooming; avoid allocating enormous canvases.
        const scale = Math.min(1, 2400 / source[2], 1600 / source[3]);
        canvas.width = Math.max(1, Math.round(source[2] * scale));
        canvas.height = Math.max(1, Math.round(source[3] * scale));
        const context = canvas.getContext('2d');
        if (!context) throw new Error('This browser cannot display ECG lead crops.');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, ...source, 0, 0, canvas.width, canvas.height);
        return region;
    }

    function analysePixels(data, width, height) {
        // Find long pink horizontal borders, then select the largest enclosed panel.
        // A smaller, separate rhythm panel below the main ECG is deliberately ignored.
        const redRows = [];
        for (let y = 0; y < height; y++) {
            let count = 0, left = width, right = -1;
            for (let x = 0; x < width; x++) {
                const offset = (y * width + x) * 4;
                const [r, g, b] = [data[offset], data[offset + 1], data[offset + 2]];
                if (r > 150 && g > 70 && r - g > 12 && r - b > 8) {
                    count++;
                    left = Math.min(left, x);
                    right = x;
                }
            }
            if (count > width * 0.4 && right - left > width * 0.6) redRows.push({y, left, right});
        }
        const borders = [];
        redRows.forEach(row => {
            const last = borders[borders.length - 1];
            if (last && row.y - last.bottom <= 3) {
                last.bottom = row.y;
                last.left = Math.min(last.left, row.left);
                last.right = Math.max(last.right, row.right);
            } else borders.push({...row, bottom: row.y});
        });

        let panel = null;
        for (let index = 0; index < borders.length - 1; index++) {
            const first = borders[index], last = borders[index + 1];
            const left = Math.max(first.left, last.left);
            const right = Math.min(first.right, last.right);
            const top = first.bottom + 1, bottom = last.y - 1;
            const area = (right - left) * (bottom - top);
            if (bottom - top >= height * 0.27 && right - left > width * 0.6 && (!panel || area > panel.area)) {
                panel = {left, right, top, bottom, area};
            }
        }

        // Default to the same landscape report template when its pink border is absent.
        // Every result remains unconfirmed: the editor must expose adjustment and review.
        if (!panel) return {
            left: 0.03, top: 0.18, width: 0.95, height: 0.59,
            column_breaks: [0.274, 0.508, 0.742], row_breaks: [1 / 3, 2 / 3], confirmed: false,
        };

        const panelWidth = panel.right - panel.left;
        const panelHeight = panel.bottom - panel.top;
        const cropHeight = panelHeight * 0.75; // Three lead rows; fourth row is the rhythm strip.
        const breaks = [0.25, 0.5, 0.75].map(expected => {
            let bestX = null, bestScore = 0;
            const begin = Math.floor(panel.left + panelWidth * (expected - 0.065));
            const end = Math.ceil(panel.left + panelWidth * (expected + 0.065));
            for (let x = begin; x <= end; x++) {
                let dark = 0;
                for (let y = Math.floor(panel.top + cropHeight * 0.12); y < panel.top + cropHeight * 0.9; y++) {
                    const offset = (y * width + x) * 4;
                    if (data[offset] < 125 && data[offset + 1] < 125 && data[offset + 2] < 125) dark++;
                }
                const score = dark / (cropHeight * 0.78);
                if (score > bestScore) { bestScore = score; bestX = x; }
            }
            return bestScore > 0.22 ? (bestX - panel.left) / panelWidth : expected;
        });
        return {
            left: clamp(panel.left / width, 0, 1), top: clamp(panel.top / height, 0, 1),
            width: panelWidth / width, height: cropHeight / height,
            column_breaks: breaks, row_breaks: [1 / 3, 2 / 3], confirmed: false,
        };
    }

    function detect(image) {
        const source = imageSize(image);
        const scale = Math.min(1, 1000 / source.width, 1000 / source.height);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(source.width * scale));
        canvas.height = Math.max(1, Math.round(source.height * scale));
        const context = canvas.getContext('2d', {willReadFrequently: true});
        if (!context) throw new Error('This browser cannot extract ECG lead images.');
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
        return analysePixels(pixels.data, canvas.width, canvas.height);
    }

    globalThis.EcgLeadExtractor = Object.freeze({detect, regions, render, validate, layout});
})();
