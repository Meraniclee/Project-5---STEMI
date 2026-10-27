# Validation — 22 October 2026

- PHP syntax: all application controllers, models, request/schema classes, migrations, routes and feature tests passed.
- Blade templates: compiled successfully.
- PHP feature/unit suite: **31 tests passed, 244 assertions** against isolated SQLite in memory.
- JavaScript syntax: annotation workspace and extraction module passed.
- Autosave queue tests: edits during requests, stale acknowledgements, image isolation, retryable and validation failures, automatic retries, navigation flushing and field clearing passed.
- Extraction tests: correct 12-lead mapping, invalid-boundary rejection, crop rendering, grid detection, rhythm-strip exclusion, and fallback bounds passed.
- Sample verification: all five supplied JPG examples were checked against the extraction geometry.
- Browser: real cropped leads rendered without console errors; fractional 0.25 mm measurement saved and survived reload; per-image unsaved drafts survived switching; a case with an incomplete ECG was rejected; both images were then fully documented and the examination completed; completed findings became read-only.
- Autosave browser update: a single partial finding saved automatically and survived reload; notes remained isolated across two ECGs; an immediate navigation saved the latest note before leaving; completing one lead turned its card green while other cards stayed soft red; clearing a required finding turned it soft red again. The Patients List displayed “Examined by Dr Wong” and view actions displayed eye icons. No manual Save draft button remains.
- Answer highlighting browser checks: entered lead findings, overall interpretation, STEMI choices and numeric zero turned soft blue; unanswered fields remained white; clearing a measurement restored white; saved answers and cleared measurements survived reload. Lead completion colours remained separate.
- Report tests: partial cases appear immediately; claim-only patients do not; doctor reports and export payloads exclude other doctors' findings; admin/consultant access is read-only; all saved examinations/images, every lead, multiple selections, decimal and zero measurements, notes and legacy records are retained. Literal script-like notes are safely encoded.
- Export layout tests: long notes and unbroken tokens survive wrapping across multiple pages; multiple examinations start separate pages; numeric zero is retained; all text remains within page bounds; filenames are sanitized.
- Browser report checks: four saved ECG records displayed 48 lead sections and 598 field/answer rows, with examination/doctor attribution and no horizontal overflow or console warnings/errors.
- Collapsible report update: all 15 requested headings opened and closed independently in the browser; Enter and Space operated the controls; Expand all/Collapse all affected only their ECG record. Panel IDs were unique across repeated section names and all 598 answer rows remained in the document. All six report tests (64 assertions), JavaScript syntax and Blade compilation passed. Print styles explicitly reveal collapsed answer panels; export data is independent of panel visibility.
- Export browser checks: Download PDF, PNG and JPG each exported all **32 pages** of the four-record synthetic fixture. PDF was reopened and verified as a valid 32-page A4 document; all pages were rendered and visually reviewed. Both ZIP archives passed integrity checks and contained 32 valid numbered images at 1240 × 1754 pixels. Native physical printing was not invoked; Print opens the browser's print dialog with dedicated print styles.

The browser used a dedicated local test database with synthetic accounts and annotations. These fixtures are excluded from the delivery ZIP. Existing MySQL data was not migrated or modified during development. Run the additive migration on the intended installation before using the new forms.

Extraction supports the illustrated 3-by-4 report layout with manual verification. The software does not interpret waveforms or infer a diagnosis. Other layouts and clinical accuracy have not been validated by this software test process.
