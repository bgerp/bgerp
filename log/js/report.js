function logReportSuccess(seconds) {
    var counter = document.getElementById('log-report-seconds');
    var progress = document.querySelector('.log-report-success-progress');
    var closeButton = document.getElementById('log-report-close');
    var deadline = Date.now() + seconds * 1000;

    progress.style.animationDuration = seconds + 's';
    progress.classList.add('is-counting-down');

    var interval = setInterval(function () {
        var remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        if (counter.textContent !== String(remaining)) {
            counter.textContent = remaining;
        }
    }, 200);

    var timeout = setTimeout(function () {
        clearInterval(interval);
        counter.textContent = '0';
        window.close();
    }, seconds * 1000);

    closeButton.addEventListener('click', function () {
        clearInterval(interval);
        clearTimeout(timeout);
        window.close();
    });
}
