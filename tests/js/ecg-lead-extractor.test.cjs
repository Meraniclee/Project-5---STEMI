const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/ecg-lead-extractor.js'), 'utf8');
let sampledPixels;
const sandbox = {
    document: {createElement: () => ({getContext: () => ({
        drawImage() {}, getImageData: () => ({data: sampledPixels}),
    })})},
};
vm.createContext(sandbox);
vm.runInContext(source, sandbox);
const api = sandbox.EcgLeadExtractor;
const close = (actual, expected, message) => assert.ok(Math.abs(actual - expected) < 0.00001, `${message}: ${actual} != ${expected}`);
const crop = {left: 0.1, top: 0.2, width: 0.8, height: 0.6, column_breaks: [0.25, 0.5, 0.75], row_breaks: [1 / 3, 2 / 3]};
assert.equal(api.validate(crop), true);
const boxes = api.regions(crop);
assert.equal(Object.keys(boxes).length, 12);
for (const [lead, x, y] of [
    ['I', 0, 0], ['aVR', 1, 0], ['V1', 2, 0], ['V4', 3, 0],
    ['II', 0, 1], ['aVL', 1, 1], ['V2', 2, 1], ['V5', 3, 1],
    ['III', 0, 2], ['aVF', 1, 2], ['V3', 2, 2], ['V6', 3, 2],
]) {
    close(boxes[lead].left, 0.1 + x * 0.2, `${lead} column`);
    close(boxes[lead].top, 0.2 + y * 0.2, `${lead} row`);
    close(boxes[lead].width, 0.2, `${lead} width`);
    close(boxes[lead].height, 0.2, `${lead} height`);
}
for (const invalid of [null, {...crop, left: NaN}, {...crop, width: 0}, {...crop, height: 1}, {...crop, column_breaks: [0.5, 0.4, 0.75]}, {...crop, row_breaks: [0, 1]}]) {
    assert.equal(api.validate(invalid), false);
    assert.throws(() => api.regions(invalid));
}
let drawn;
const target = {getContext: () => ({fillRect() {}, drawImage: (...args) => {drawn = args;}})};
const original = {naturalWidth: 2000, naturalHeight: 1000};
api.render(original, crop, 'V6', target);
assert.equal(target.width, 400);
assert.equal(target.height, 200);
close(drawn[1], 1400, 'V6 source x');
close(drawn[2], 600, 'V6 source y');
assert.throws(() => api.render(original, crop, 'V9', target));
assert.throws(() => api.detect({naturalWidth: 0, naturalHeight: 0}));

// A report with a large four-row grid and a separate rhythm panel underneath.
const width = 800, height = 600;
sampledPixels = new Uint8ClampedArray(width * height * 4).fill(255);
function pixel(x, y, red, green, blue) {
    const offset = (y * width + x) * 4;
    sampledPixels[offset] = red; sampledPixels[offset + 1] = green; sampledPixels[offset + 2] = blue;
}
for (const y of [100, 500, 530, 585]) for (let x = 20; x <= 780; x++) pixel(x, y, 240, 160, 160);
for (const x of [230, 410, 590]) for (let y = 130; y <= 375; y++) pixel(x, y, 20, 20, 20);
const detected = api.detect({naturalWidth: width, naturalHeight: height});
assert.equal(api.validate(detected), true);
assert.equal(detected.confirmed, false);
close(detected.left, 20 / width, 'Main panel x');
close(detected.top, 101 / height, 'Header excluded');
close(detected.height, (499 - 101) * 0.75 / height, 'Rhythm row excluded');
close(detected.column_breaks[0], (230 - 20) / 760, 'First separator');
assert.ok(detected.top + detected.height < 500 / height);

sampledPixels.fill(255);
const fallback = api.detect({naturalWidth: width, naturalHeight: height});
assert.equal(api.validate(fallback), true);
assert.equal(fallback.confirmed, false);

// Optional real-image pixel fixtures made with Pillow for local smoke verification.
if (process.argv[2]) {
    const fixtures = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    fixtures.forEach(fixture => {
        sampledPixels = Buffer.from(fixture.data, 'base64');
        const result = api.detect({naturalWidth: fixture.width, naturalHeight: fixture.height});
        assert.equal(api.validate(result), true);
        assert.equal(result.confirmed, false);
        assert.ok(result.top >= 0.15 && result.top <= 0.25, fixture.name + ' grid top');
        assert.ok(result.top + result.height < 0.83, fixture.name + ' rhythm excluded');
        assert.ok(result.column_breaks[0] > 0.25 && result.column_breaks[0] < 0.30, fixture.name + ' first divider');
        console.log(fixture.name, JSON.stringify(result));
    });
}
console.log('ECG extraction tests passed: 12-lead mapping, validation, crop rendering, border detection, rhythm exclusion, and fallback.');
