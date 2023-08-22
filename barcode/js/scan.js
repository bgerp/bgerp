var selectedDeviceId;
const codeReader = new ZXing.BrowserMultiFormatReader();
window.addEventListener('load', function () {
	const barcodeSearch = document.getElementById('barcodeSearch');
	
	// Откриваме камерите
	codeReader.getVideoInputDevices().then((videoInputDevices) => {
		// Ако има камери
		if (videoInputDevices && videoInputDevices.length > 0) {
			const scanTools = document.getElementById('scanTools');
			scanTools.style.display = 'block';

			// Махаме действието на бутона за сканиране
			document.getElementById('scanBtn').onclick = function() {
				return false;
			}

			videoInputDevices = videoInputDevices.reverse();
			// Вземеме последната активна камера от бисквитките
			var cameraId = getCookie('selectedCamera');
			if(cameraId) {
				selectedDeviceId = cameraId;
			} else {
				selectedDeviceId = videoInputDevices[0].deviceId;

			}
			// Ако не търсим и имаме повече от една камера - показваме възможност за избор на камера
			if (!barcodeSearch.value.trim() && videoInputDevices.length >= 1) {
				showInputDevices(videoInputDevices);
			}

			// След натискане на бутона за търсене
			document.getElementById('scanBtn').addEventListener('click', () => {
				if (selectedDeviceId) {
					const cameraDiv = document.getElementById('camera')
					cameraDiv.style.display = 'block'
					// Взмаме баркода чрез камерата и го добавяме в полето за търсене
					codeReader.decodeFromVideoDevice(selectedDeviceId, 'video', (result, err) => {
						if (result) {
							barcodeSearch.value = result.text;
							cameraDiv.style.display = 'none'
							codeReader.reset();
							document.getElementById('barcodeForm').submit();
						}

						if (err && !(err instanceof ZXing.NotFoundException)) {
							console.error(err);
						}
					});
				}
			})

			//
			var elems = [].filter.call( document.getElementsByTagName("button"), function( button ) {
				return button.value === cameraId;
			});
			// Ако има камера, записана в бисквитките, я стартираме
			if (elems[0]) {
				elems[0].click();
			}
		}
	}).catch((err) => {
		console.error(err)
	})
})


/**
 * Помощна фунцкция за показване на картинка от камерата
 * 
 * @param videoInputDevices
 *
 * @return
 */
function showInputDevices(videoInputDevices)
{
	var frontCounter = 1;
	var backCounter = 1;
	const sourceSelectPanel = document.getElementById('sourceSelectPanel');
	sourceSelectPanel.style.display = 'block';

	videoInputDevices.forEach((element, index) => {
		const sourceLink = document.createElement('button');
		// Ако има Front/back в името на камерите, ги записваме F1,F2,B1,B2 .. или с пореден номер
		if (element.label.indexOf("front") != -1 ) {
			sourceLink.innerText = "F" + frontCounter++;
		} else if(element.label.indexOf("back") != -1 || element.label.indexOf("rare") != -1) {
			sourceLink.innerText = "B" + backCounter++;
		} else {
			sourceLink.innerText = index + 1;
		}

		sourceLink.className = 'button cameraSource';
		sourceLink.value = element.deviceId;
		sourceLink.onclick = changeVideoSource;
		sourceSelectPanel.appendChild(sourceLink);
	});

}

function changeVideoSource() {
	selectedDeviceId = this.value;
	setCookie('selectedCamera', this.value);
    Array.from(document.querySelectorAll('.cameraSource.active')).forEach((element) => {
		element.classList.remove('active');
	});
	this.classList.add('active');
	codeReader.reset();
	document.getElementById('scanBtn').click();
}
