function photopeaInit() {
    var frame = document.getElementById('photopea-editor');
    var status = document.getElementById('photopea-status');
    var origin = 'https://www.photopea.com';
    var state = 'waiting';
    var timer = setTimeout(fail, 60000);

    function fail() {
        state = 'failed';
        clearTimeout(timer);
        window.removeEventListener('message', receive);
        status.textContent = status.getAttribute('data-error');
    }

    function receive(event) {
        if (event.origin !== origin || event.source !== frame.contentWindow || event.data !== 'done') {
            return;
        }
        if (state === 'opening') {
            clearTimeout(timer);
            window.removeEventListener('message', receive);
            status.textContent = '';
            state = 'ready';
        } else if (state === 'waiting') {
            state = 'loading';
            fetch(frame.getAttribute('data-file-url'), {credentials: 'same-origin'})
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Download failed');
                    }
                    return response.arrayBuffer();
                })
                .then(function (buffer) {
                    if (state !== 'loading') {
                        return;
                    }
                    state = 'opening';
                    frame.contentWindow.postMessage(buffer, origin);
                })
                .catch(fail);
        }
    }

    window.addEventListener('message', receive);
    frame.src = origin + '/#' + encodeURIComponent(JSON.stringify({files: []}));
}
