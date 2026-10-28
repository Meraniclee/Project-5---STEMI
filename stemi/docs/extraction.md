# ECG lead extraction

The patient record creates twelve image crops in the browser from the uploaded ECG image. It supports the supplied standard landscape report: three rows of four leads, followed by a rhythm strip. Row order is `I / aVR / V1 / V4`, `II / aVL / V2 / V5`, `III / aVF / V3 / V6`.

The initial crop detects the largest pink grid panel and excludes its fourth row. Dark vertical separator strokes refine the three column boundaries. The crop is a suggestion, always marked unconfirmed. If the grid is absent, the supplied report template provides initial bounds. Doctors must inspect every label and waveform and adjust the rectangle and dividers before confirming it. Rotated scans, other layouts, and differently placed rhythm strips require adjustment; this is not an OCR or arbitrary-layout detection system.

All bounds are normalized to the original image size, while divider coordinates are fractions within the selected rectangle. Saving these coordinates preserves the same crops without creating public copies of the image. The original image remains behind the existing authenticated image route.

The extractor only crops pixels. It does not digitize waveforms, diagnose ECG findings, or calculate millimetre measurements. Measurements and interpretation are entered by the doctor.

## Browser API

- `EcgLeadExtractor.detect(image)` returns `{left, top, width, height, column_breaks, row_breaks, confirmed: false}` after the image has loaded.
- `EcgLeadExtractor.validate(extraction)` checks finite bounds and ascending dividers.
- `EcgLeadExtractor.regions(extraction)` returns the normalized rectangle for each lead.
- `EcgLeadExtractor.render(image, extraction, lead, canvas)` draws the corresponding crop at native resolution (subject to a canvas size cap).

Run the standalone mapping and detection tests with `node tests/js/ecg-lead-extractor.test.cjs`.
