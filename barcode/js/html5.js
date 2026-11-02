var isScannerRunning = false;
var barcodeCameraChange = Promise.resolve();
var barcodeScanCompleted = false;
var barcodeScanner;

/** Подготвя камерите и стартира избраната точно веднъж. */
function barcodeActions() {

    barcodeScanCompleted = false;
    if (document.getElementById('barcodeSearch').value.trim()) {
        document.getElementById('scanBtn').classList.remove('hiddenBtn');
        return Promise.resolve();
    }
    const cameraButtonsContainer = document.getElementById("camera-buttons");
    var cameraId = getCookie('selectedCamera');

    const html5Qrcode = new Html5Qrcode(
        "reader", { fps: 10, qrbox: 250 }
    );

    barcodeScanner = html5Qrcode;

    // Вземаме всички камери с Html5Qrcode.getCameras()
    return Html5Qrcode.getCameras().then(cameras => {
        const scanBtn = document.getElementById("scanBtn");
        if (!cameras.length) return;
        scanBtn.classList.remove("hiddenBtn");
        cameraButtonsContainer.textContent = "";
        if (cameras.length === 1) return switchCamera(cameras[0].id, html5Qrcode);

        var frontCounter = 1;
        var backCounter = 1;
        // Добавяме бутони за всяка камера

        cameras.forEach((camera, index) => {
            const cameraButton = document.createElement("button");
            cameraButton.type = "button";
            cameraButton.textContent = camera.label;

            // Ако има Front/back в името на камерите, ги записваме F1,F2,B1,B2 .. или с пореден номер
           if (/front/i.test(camera.label) ) {
               cameraButton.className = "front";
               cameraButton.textContent = "F" + frontCounter++;
           } else if(/back|rear|rare/i.test(camera.label)) {
               cameraButton.className = "back";
               cameraButton.textContent = "B" + backCounter++;
           } else {
               cameraButton.className = "device";
               cameraButton.textContent = index + 1;
           }

            cameraButton.value = camera.id;
            cameraButton.className += " cameraSource";

            // при натискане на бутона да сменим камерата
            cameraButton.addEventListener("click", () => {
                switchCamera(camera.id, html5Qrcode);
            });
            cameraButtonsContainer.appendChild(cameraButton);

            // клас active на селектираната камера
            if (camera.id === cameraId && cameraId ) {
                cameraButton.classList.add("active");
            }
        });

        var selected = $('.cameraSource.active').first();
        if (!selected.length) selected = $('.cameraSource.back').last();
        if (!selected.length) selected = $('.cameraSource').first();
        return switchCamera(selected.val(), html5Qrcode);
    }).catch(err => {
        console.error("Error while getting cameras:", err);
    });
}
/** Отваря търсенето с празно поле за ново сканиране. */
function openCamera() {
    document.getElementById('barcodeSearch').value = "";
    document.getElementById('filter').click();
}

/** Изчаква текущото стартиране/спиране, преди да смени камерата. */
function switchCamera(cameraId, html5Qrcode) {
    barcodeCameraChange = barcodeCameraChange.then(function () {
        if (barcodeScanCompleted) return;
        return stopBarcodeScanner(html5Qrcode).then(function () {
            if (!barcodeScanCompleted) return startScanning(cameraId, html5Qrcode);
        });
    }).catch(function (err) {
        console.error("Error switching camera:", err);
    });
    return barcodeCameraChange;
}

/** Освобождава камерата, ако стартирането е завършило успешно. */
function stopBarcodeScanner(html5Qrcode) {
    if (!isScannerRunning) return Promise.resolve();
    return html5Qrcode.stop().then(function () { isScannerRunning = false; });
}

/** Запазва избора след успешно стартиране и предава само един резултат. */
function startScanning(cameraId, html5Qrcode) {
    return html5Qrcode.start(
        cameraId,
        { fps: 10, qrbox: { width: 250, height: 250 } },
        function (decodedText) {
            if (barcodeScanCompleted) return;
            barcodeScanCompleted = true;
            document.getElementById('barcodeSearch').value = decodedText;
            barcodeCameraChange = barcodeCameraChange.then(function () {
                return stopBarcodeScanner(html5Qrcode);
            }).then(function () {
                document.getElementById('filter').click();
            }).catch(function (err) {
                console.error("Error stopping scanner:", err);
            });
        },
        function () { /* Кадър без разпознат код е нормален при сканиране. */ }
    ).then(function () {
        isScannerRunning = true;
        document.querySelectorAll('.cameraSource').forEach(function (button) {
            button.classList.toggle('active', button.value === cameraId);
        });
        setCookie('selectedCamera', cameraId);
    });
}

/** Освобождава видеопотока и при напускане без разпознат код. */
window.addEventListener('pagehide', function () {
    barcodeScanCompleted = true;
    barcodeCameraChange = barcodeCameraChange.then(function () {
        if (barcodeScanner) return stopBarcodeScanner(barcodeScanner);
    }).catch(function (err) { console.error("Error stopping scanner:", err); });
});
