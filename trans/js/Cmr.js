/**
 * Всяко ЧМР копие се побира на A4, включително при дълги стойности.
 * @copyright 2026 Experta EOOD
 */
(function () {
    function fitCmrCopies() {
        document.querySelectorAll('.printing .cmr-holder').forEach(function (holder) {
            var page = holder.closest('.document');
            if (!page) return;

            holder.closest('.printing').classList.add('cmr-printing');
            page.classList.add('cmr-print-page');
            page.style.setProperty('--cmr-print-scale', '1');
            var rect = page.getBoundingClientRect();
            // A4 с полета 10 mm и резерв за закръгляне при печат.
            var scale = Math.min(1, 710 / Math.max(840, page.scrollWidth, rect.width),
                1030 / Math.max(page.scrollHeight, rect.height));
            page.style.setProperty('--cmr-print-scale', Math.floor(scale * 1000) / 1000);
        });
    }

    window.addEventListener('load', fitCmrCopies);
    window.addEventListener('beforeprint', fitCmrCopies);
    if (document.readyState === 'complete') fitCmrCopies();
})();
