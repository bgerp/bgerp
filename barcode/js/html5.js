var isScannerRunning = false;
function barcodeActions() {

    const cameraButtonsContainer = document.getElementById("camera-buttons");
    var cameraId = getCookie('selectedCamera');

    const html5Qrcode = new Html5Qrcode(
        "reader", { fps: 10, qrbox: 250 }
    );

    // Вземаме всички камери с Html5Qrcode.getCameras()
    Html5Qrcode.getCameras().then(cameras => {
        var frontCounter = 1;
        var backCounter = 1;
        // Добавяме бутони за всяка камера
        cameras.forEach((camera, index) => {
            const cameraButton = document.createElement("button");
            cameraButton.textContent = camera.label;

            // Ако има Front/back в името на камерите, ги записваме F1,F2,B1,B2 .. или с пореден номер
           if (camera.label.indexOf("front") != -1 ) {
               cameraButton.className = "front";
               cameraButton.textContent = "F" + frontCounter++;
           } else if(camera.label.indexOf("back") != -1 || camera.label.indexOf("rare") != -1) {
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
                startScanning(cameraId, html5Qrcode);
            }

        });

        if( $('.cameraSource.active').length == 0 &&  $('.cameraSource.back').length) {
            $('.cameraSource.back').last().click();
        } else {
            $('.cameraSource').first().click();
        }
    }).catch(err => {
        console.error("Error while getting cameras:", err);
    });
}
function openCamera() {
    document.getElementById('barcodeSearch').value = "";
    document.getElementById('filter').click();
}

// Сменяне на активната камера
function switchCamera(cameraId, html5Qrcode) {
    // ако работи една камера, първо я спираме
    if (isScannerRunning) {
        html5Qrcode.stop().then(ignore => {
            // Сканирането е спряно
            console.log("QR Code scanning stopped.");
            startScanning(cameraId, html5Qrcode);
        }).catch(err => {
            // Грешка при спиране
            console.log("Unable to stop scanning.");
        });
    } else {
        startScanning(cameraId, html5Qrcode);
    }
}

// Стартиране на камера с конкректа стойност
function startScanning(cameraId, html5Qrcode) {
    html5Qrcode.start(
        cameraId,
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        (decodedText, decodedResult) => {
            document.getElementById('barcodeSearch').value = decodedText;
            document.getElementById('filter').click();
        },
        (errorMessage) => {
            document.getElementById("errors").value = errorMessage;
        }
    ).catch((err) => {
        console.log("Error starting scanner:", err);

    });

    // Махаме клас active от всички бутони
    const cameraButtons = document.querySelectorAll(".cameraSource");
    cameraButtons.forEach(button => {
        button.classList.remove("active");
    });

    // Добавяме клас active на натиснатия бутон
    const activeButton = document.querySelector(`.cameraSource[value="${cameraId}"]`);
    if (activeButton) {
        activeButton.classList.add("active");
    }
    // запазване на текущата камера в бисквитка
    setCookie('selectedCamera', cameraId);
    isScannerRunning = true;
}