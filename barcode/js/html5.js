function onScanSuccess(decodedText, decodedResult) {
    // Попълваме полето с резултата и търсим баркода
    document.getElementById('barcodeSearch').value = decodedText;
    document.getElementById('filter').click();
}


function barcodeActions() {
    // При мобилен да са на един ред инпута и бутона
    if ($('body').hasClass('narrow')) {
        $('.formFields').attr("style", "display:inline-block; margin-bottom: 0");
        $('.formToolbar').attr("style", "display:inline-block; position: relative; top: -2px");
    }

    var html5QrcodeScanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
}

