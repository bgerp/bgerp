/** Isolated camera lifecycle checks. GPL 3, 2026 Experta EOOD. */
async function checkBarcodeLifecycle() {
    var Real = Html5Qrcode, oldGet = getCookie, oldSet = setCookie, saved = 'back', checks = 0, failures = [], starts = [], stops = 0, submissions = 0, busy = false, failStart = false, instance;
    /** Records all regressions rather than stopping at the first one. */
    function check(ok, message) { checks++; if (!ok) failures.push(message); }
    /** Lets the simulated camera promises settle. */
    function delay(ms) { return new Promise(function(resolve) { setTimeout(resolve, ms); }); }
    /** Records camera access without any hardware or permission prompt. */
    function FakeScanner() { instance = this; }
    FakeScanner.getCameras = function() { return Promise.resolve([{id:'front',label:'Front camera'},{id:'back',label:'Back camera'}]); };
    /** Simulates delayed permission and exclusive camera ownership. */
    FakeScanner.prototype.start = function(id, options, success, error) {
        starts.push(id); this.success = success; this.error = error;
        var overlap = busy; busy = true;
        return delay(15).then(function() { if (failStart || overlap) { busy = false; throw Error('Simulated start failure'); } });
    };
    /** Simulates asynchronous camera release. */
    FakeScanner.prototype.stop = function() { stops++; return delay(10).then(function() { busy = false; }); };
    try {
        Html5Qrcode = FakeScanner;
        getCookie = function() { return saved; }; setCookie = function(name, value) { saved = value; };
        $('#camera-buttons').empty(); $('#barcodeSearch').val('');
        $('#filter').on('click.qa', function(e) { e.preventDefault(); submissions++; });
        await barcodeActions(); await delay(80);
        check(starts.join(',') === 'back', 'Saved camera starts exactly once');
        check($('.cameraSource.active').val() === 'back', 'Saved camera selected');
        check($('.cameraSource').map(function(){return $(this).text();}).get().join(',') === 'F1,B1', 'Front/Back labels ignore case');
        var before = starts.length;
        switchCamera('front', instance); switchCamera('back', instance); await delay(100);
        check(starts.slice(before).join(',') === 'front,back' && busy, 'Rapid changes are serialized');
        check(isScannerRunning === true, 'Running state after successful start');
        try { instance.error('No barcode found'); check(true, 'Empty frames are normal'); } catch(e) { check(false, 'Empty frames are normal'); }
        instance.success('000123', {}); instance.success('000123', {}); await delay(60);
        check(submissions === 1, 'Decoded result submitted once');
        check($('#barcodeSearch').val() === '000123', 'Leading barcode zeros');
        check(!busy && !isScannerRunning, 'Camera released before submission');
        $('#camera-buttons').empty(); $('#barcodeSearch').val(''); starts = []; busy = false; isScannerRunning = false; failStart = true;
        await barcodeActions(); await delay(70);
        check(!isScannerRunning, 'Failed start leaves stopped state');
        failStart = false; await switchCamera('front', instance); await delay(70);
        check(isScannerRunning && busy, 'Retry works after failed start');
        instance.success('Кирилица', {}); await delay(50);
        check($('#barcodeSearch').val() === 'Кирилица', 'Cyrillic QR content');
        check(stops >= 3, 'Tracks released on switches and decode');
        var previousStarts = starts.length;
        await barcodeActions(); await delay(30);
        check(starts.length === previousStarts && !isScannerRunning, 'Results page does not reopen camera');
        check(!$('#scanBtn').hasClass('hiddenBtn'), 'Results page offers a new scan');
        $('#barcodeSearch').val(''); saved = 'removed-camera';
        await barcodeActions(); await delay(40);
        check(starts[starts.length-1] === 'back', 'Missing saved camera falls back to rear');
        await stopBarcodeScanner(instance);
        FakeScanner.getCameras = function() { return Promise.resolve([]); };
        previousStarts = starts.length; await barcodeActions(); await delay(30);
        check(starts.length === previousStarts, 'No cameras does not start a scanner');
        FakeScanner.getCameras = function() { return Promise.reject(Error('Simulated permission denied')); };
        await barcodeActions(); await delay(30);
        check(!isScannerRunning, 'Permission denied keeps manual search usable');
        FakeScanner.getCameras = function() { return Promise.resolve([{id:'single',label:'Camera'}]); };
        previousStarts = starts.length; await barcodeActions(); await delay(30);
        check(starts.length === previousStarts + 1 && starts[starts.length-1] === 'single', 'Single camera starts once');
        window.dispatchEvent(new Event('pagehide')); await delay(40);
        check(!busy && !isScannerRunning, 'Leaving page releases camera');
        var previousSubmissions = submissions; instance.success('late', {}); await delay(30);
        check(submissions === previousSubmissions, 'Late decode ignored after leaving page');
        return {checks:checks, failures:failures};
    } finally {
        Html5Qrcode = Real; getCookie = oldGet; setCookie = oldSet; $('#filter').off('.qa');
    }
}
