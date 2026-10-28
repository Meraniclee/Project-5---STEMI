# Locally bundled export dependencies

These files run in the browser without contacting a CDN or external service.

- pdf-lib 1.17.1 (`pdf-lib.min.js`), MIT license in `pdf-lib.LICENSE.md`.
- JSZip 3.10.1 (`jszip.min.js`), used under the MIT license in `jszip.LICENSE.md`.

The application uses these distributions only for examination PDF downloads and ZIP archives containing paginated PNG/JPG exports. Print uses the browser's native print dialog.
