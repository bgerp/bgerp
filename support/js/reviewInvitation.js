function supportReviewInvitation(options) {
    var result = document.getElementById('supportReviewResult');
    if (!result) return;
    result.addEventListener('click', function (event) {
        var copyButton = event.target.closest('[data-review-copy]');
        if (!copyButton) return;
        var text = document.getElementById('supportReviewText');
        var message = result.querySelector('[data-review-message]');
        function manual() {
            text.focus();
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents(text);
            selection.removeAllRanges();
            selection.addRange(range);
            copyButton.classList.remove('is-copied');
            copyButton.querySelector('[data-review-copy-icon]').hidden = false;
            copyButton.querySelector('[data-review-check]').hidden = true;
            copyButton.querySelector('[data-review-copy-label]').textContent = options.copy;
            message.classList.add('is-manual');
            message.textContent = options.manual;
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text.textContent).then(function () {
                copyButton.classList.add('is-copied');
                copyButton.querySelector('[data-review-copy-icon]').hidden = true;
                copyButton.querySelector('[data-review-check]').hidden = false;
                copyButton.querySelector('[data-review-copy-label]').textContent = options.copied;
                message.classList.remove('is-manual');
                message.textContent = options.paste;
            }, manual);
        } else {
            manual();
        }
    });
    var attempts = 0;
    function poll() {
        if (++attempts > 35) { result.textContent = ''; return; }
        fetch(options.url, {credentials: 'same-origin', cache: 'no-store'})
            .then(function (response) {
                if (!response.ok) throw new Error('Unavailable');
                return response.json();
            }).then(function (data) {
                if (data.pending) { setTimeout(poll, 2000); return; }
                result.innerHTML = data.html;
            }).catch(function () { result.textContent = ''; });
    }
    if (options.pending) setTimeout(poll, 2000);
}
