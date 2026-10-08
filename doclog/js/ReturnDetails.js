(function () {
    if (window.doclogReturnDetailsBound) return;
    window.doclogReturnDetailsBound = true;
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a.doclog-return-details');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var dialog = document.createElement('dialog');
        if (!dialog.showModal) return; // Обикновен линк при браузър без dialog.
        event.preventDefault();
        dialog.className = 'doclog-return-dialog';
        var close = document.createElement('button');
        close.type = 'button';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Затваряне / Close');
        var frame = document.createElement('iframe');
        frame.title = link.title;
        frame.src = link.href;
        dialog.setAttribute('aria-label', link.title);
        dialog.appendChild(close);
        dialog.appendChild(frame);
        document.body.appendChild(dialog);
        close.addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
        dialog.addEventListener('close', function () { dialog.remove(); link.focus(); });
        dialog.showModal();
        close.focus();
    });
}());
