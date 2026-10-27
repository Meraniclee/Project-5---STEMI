# Project 5 — STEMI

Laravel 13 / PHP 8.3+ ECG image examination management system.

## Completed workflow

- **Administrator:** add and manage doctors and consultants; register and edit patients; upload JPG/PNG ECG images to private storage.
- **Doctor:** choose a patient, start an examination, edit that patient's information during the active examination, inspect 12 extracted leads, and save per-image findings.
- **Consultant:** preview doctors, their patients, ECG images, and every saved interpretation. Mutation routes are restricted to the appropriate role.
- **Lead findings:** all 11 requested fields for I, II, III, aVR, aVL, aVF, V1–V6, including optional decimal ST elevation/depression measurements.
- **Overall ECG Interpretation / Overall Diagnosis:** primary interpretation, multiple MI localisations, STEMI, NSTEMI, MI evidence, and confidence.
- **STEMI Specific Annotation:** elevation presence, involved leads, per-lead mm measurements, contiguous groups, reciprocal depression/leads, LBBB, ECG criteria, suspected occlusion, and STEMI interpretation.
- **Automatic saving:** every finding, measurement, note and crop adjustment is saved automatically, separately for each ECG image and examination. Dropdowns and checkboxes save immediately; typing is saved after a short pause. Doctors can leave any leads unfinished. Saving never requires all 12 leads. Requests are ordered and new edits remain pending until acknowledged, including edits made while a previous request is saving. Connection failures retry automatically and remain visibly unsaved until confirmed.
- **Lead progress:** finished lead cards are green; unfinished cards are soft red. A lead is finished when its nine dropdown findings are documented; the two measurement fields remain optional.
- **Answer visibility:** entered lead, overall ECG and STEMI answers turn soft blue, including measurements and selected lead groups. Unanswered fields retain their default colour. Saved answers restore their blue state when revisiting an ECG, and clearing an answer restores its default state. Answer labels supplement colour; the autosave status separately confirms whether changes have reached the server.
- **Examination completion:** the optional Complete examination action finalizes the case only after every ECG review is finished. It is separate from automatic progress saving. Completed examinations remain read-only; a new examination can be started.
- **Patient attribution:** Patients List shows the distinct examining doctors, such as “Examined by Dr Wong”, once findings have been recorded. Merely claiming a case does not label it examined.
- **Navigation:** view links, back/next actions and pagination use eye icons with readable labels.
- **Patient examination records:** Reports opens an examined-patient list, then a readable record of every saved ECG review, grouped by examination and image. All 12 leads, overall interpretation, STEMI annotations, doctor notes, and earlier-format findings are included. Unanswered fields are marked “Not recorded”, so partial reviews are available immediately. Doctors see their own examination records; administrators and consultants can preview all doctors' records.
- **Print and export:** patient examination records provide Print, Download PDF, Download PNG and Download JPG. Exports include all records on the page, paginate long answers and notes, and identify the patient, doctor, examination, ECG image and saved date. Image downloads contain all numbered pages in one ZIP. Export processing runs locally in the browser with bundled libraries and no external service.

## Update an existing installation

1. Back up the existing database and project folder.
2. Copy the updated project source over the existing `stemi` folder. **Keep your existing `.env`, private ECG files in `storage/app`, and database.** The delivery ZIP excludes environment credentials, uploaded patient files, databases, sessions, caches and logs.
3. In the installed project folder run:

   ```powershell
   php artisan optimize:clear
   php artisan migrate
   ```

   The new migration adds JSON annotation columns and a completion flag. It does not remove existing interpretations or images. Do not use `migrate:fresh` on an existing installation.
4. Restart the PHP web server if it uses an opcode cache, then refresh the browser.

The portal uses directly served CSS and JavaScript. No npm build is needed for these pages. Dependencies are included in the delivery ZIP; if `vendor` is missing, run `composer install`.

## Fresh installation with Laragon

1. Extract `stemi` into `C:\laragon\www\stemi` and create an empty MySQL database named `stemi_db`.
2. Enable PHP extensions required by Laravel, including `pdo_mysql`, `fileinfo`, `mbstring`, `openssl` and XML/DOM. For tests, enable `pdo_sqlite`. GD is used by upload test fixtures.
3. Copy `.env.example` to `.env`, set the database connection, and run:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```

4. For local demonstration accounts only, set `STEMI_DEMO_PASSWORD` in `.env` to your own unique password of at least 12 characters, then run `php artisan db:seed`. This creates `admin@stemi.local`, `doctor@stemi.local`, and `consultant@stemi.local`, using that password. The seeder only runs with `APP_ENV=local`.
5. Start Laragon and open `http://stemi.test`, or run `php artisan serve` and open its displayed local address.
6. As admin, add a patient and upload an ECG. As doctor, open the patient, start an examination, verify the lead crops, and enter findings. Changes save automatically, including partial reviews. As consultant, use Doctors or Patients for read-only review.

## Lead extraction

Extraction uses the **3-row × 4-column layout in the supplied example**:

| | Column 1 | Column 2 | Column 3 | Column 4 |
|---|---|---|---|---|
| Row 1 | I | aVR | V1 | V4 |
| Row 2 | II | aVL | V2 | V5 |
| Row 3 | III | aVF | V3 | V6 |

The browser detects the report grid and excludes the bottom rhythm strip. It creates real image crops locally; it does not send images to an external service. Open **Inspect and adjust extraction boundaries** to check the overlay. Drag a rectangle around the first three rows or adjust the crop and divider percentages, then choose **Confirm all 12 lead crops**.

Crop boundaries and confirmation are saved with the interpretation so the same crops can be reconstructed. The original image stays unchanged. Other ECG layouts, rotated images, and scans without clear grid boundaries require manual preparation or adjustment; automatic detection is not a universal lead-recognition model. Do not confirm mismatched labels. See `docs/extraction.md` for details.

Measurements are clinician-entered millimetres, not automatically inferred from image pixels. Leave a measurement blank when it cannot be measured. The STEMI elevation measurement and the same lead's elevation measurement are synchronized. Findings are not generated automatically.

## Reviewing and printing records

1. Open **Reports**, then **Patient's Examination Record**.
2. Search for a patient ID and choose **View Examination**. Patients appear after a doctor saves findings, including incomplete examinations.
3. Expand any lead, overall interpretation, STEMI annotation or notes heading to read its saved answers. Sections start collapsed; each ECG record has Expand all sections and Collapse all sections controls. Each review identifies its doctor, examination and last-saved time. Printing and downloads always include every answer, even when sections are collapsed on screen.
4. Choose **Print** for the browser print dialog (including Save as PDF), **Download PDF** for a paginated visual copy, or **Download PNG/JPG** for a ZIP of all page images. Large histories may take a little longer; the export status shows progress.

Reports show the latest saved answers for each ECG image in each examination, including previous examinations. They do not keep a separate version for every autosave keystroke. Reopen or refresh the report to include edits saved after the report page was loaded. Downloaded PDFs are visual page snapshots; use Print / Save as PDF when a PDF with selectable text is needed.

## Validation

Automated tests use an isolated SQLite database in memory and do not access the configured MySQL database:

```powershell
php vendor/phpunit/phpunit/phpunit
node tests/js/ecg-lead-extractor.test.cjs
node tests/js/stemi-autosave.test.cjs
node tests/js/stemi-report-export.test.cjs
```

Feature coverage includes role permissions, clinician creation, patient editing/assignment, private uploads, per-image persistence, multiple localisations, decimal measurements, invalid payloads, crop geometry, and completion across all images. Browser checks cover actual lead rendering, draft save/reload, image switching, crop confirmation, and the examination workflow.

## Main files

- `app/Support/EcgAnnotationSchema.php`: shared field options and validation.
- `app/Http/Requests/SaveEcgInterpretationRequest.php`: request authorization and validation.
- `app/Http/Controllers/Stemi/WorkspaceController.php`: images, exams and interpretations.
- `app/Http/Controllers/Stemi/PatientManagementController.php`: patient creation and editing.
- `app/Http/Controllers/Stemi/ConsultantDoctorController.php`: consultant doctor previews.
- `app/Http/Controllers/Stemi/ExaminationReportController.php`: scoped report list and patient records.
- `app/Support/ExaminationReport.php`: consistent field/answer formatting for screen and export.
- `database/migrations/2026_10_22_000000_add_structured_ecg_annotations.php`: additive schema update.
- `resources/views/stemi/annotation-workspace.blade.php`: complete annotation workspace.
- `public/assets/js/ecg-lead-extractor.js`: image detection, geometry and crops.
- `public/assets/js/stemi-annotations.js`: per-image forms, calibration and automatic saving.
- `public/assets/js/stemi-autosave.js`: ordered saves, revision tracking, retries and navigation flushing.
- `public/assets/css/stemi-annotations.css`: responsive annotation layout.
- `resources/views/stemi/examination-report.blade.php`: readable examination results and export controls.
- `public/assets/css/stemi-reports.css`: report layout and print styling.
- `public/assets/js/stemi-report-export.js`: paginated PDF, PNG and JPG exports.
- `public/assets/vendor/`: bundled PDF/ZIP libraries and their licenses.
