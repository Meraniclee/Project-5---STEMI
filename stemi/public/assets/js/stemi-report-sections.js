(function () {
    'use strict';
    const report = document.getElementById('examinationReport');
    if (!report) return;

    function setExpanded(button, expanded) {
        const panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) return;
        panel.hidden = !expanded;
        button.setAttribute('aria-expanded', String(expanded));
        button.querySelector('[data-report-toggle-label]').textContent = expanded ? 'Collapse' : 'Expand';
    }

    report.querySelectorAll('.report-section-toggle').forEach(button => {
        // Without JavaScript every answer remains visible and printable.
        setExpanded(button, false);
        button.querySelector('.report-section-indicator').hidden = false;
        button.addEventListener('click', () => setExpanded(button, button.getAttribute('aria-expanded') !== 'true'));
    });

    report.querySelectorAll('.report-section-actions').forEach(actions => {
        actions.hidden = false;
        actions.querySelectorAll('[data-report-sections]').forEach(button => {
            button.addEventListener('click', () => {
                const expanded = button.dataset.reportSections === 'expand';
                actions.closest('.report-record').querySelectorAll('.report-section-toggle').forEach(toggle => setExpanded(toggle, expanded));
            });
        });
    });
}());
