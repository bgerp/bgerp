window.addEventListener('load', function () {
	
	let selectedDeviceId;
	const codeReader = new ZXing.BrowserMultiFormatReader();
	const barcodeSearch = document.getElementById('barcodeSearch');
	
	// Откриваме камерите
	codeReader.getVideoInputDevices().then((videoInputDevices) => {
		const sourceSelect = document.getElementById('sourceSelect');
		
		// Ако има камери
		if (videoInputDevices && videoInputDevices.length) {
			
			const scanTools = document.getElementById('scanTools');
			scanTools.style.display = 'block';
			
			// Махаме действието на бутона за сканиране
			document.getElementById('scanBtn').onclick = function() {
				
				return false;
			}
			
			videoInputDevices = videoInputDevices.reverse();
			selectedDeviceId = videoInputDevices[0].deviceId;
			
			// Ако не търсим и имаме повече от една камера - показваме възможност за избор на камера
			if (!barcodeSearch.value.trim() && videoInputDevices.length >= 1) {
				showInputDevices(videoInputDevices, sourceSelect);
			}
			
			// При промяна на selecta, да се пуска новата камера
			sourceSelect.onchange = () => {
				selectedDeviceId = sourceSelect.value;
				codeReader.reset();
				document.getElementById('scanBtn').click();
			};
			
			// След натискане на бутона за търсене
			document.getElementById('scanBtn').addEventListener('click', () => {
				
				// Ако блока за камера е скрит, показваме го
				if (sourceSelectPanel.style.display == 'none') {
					showInputDevices(videoInputDevices, sourceSelect);
				}
				
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
				})
			})
		}
	}).catch((err) => {
		console.error(err)
	})
})


/**
 * Помощна фунцкция за показване на картинка от камерата
 * 
 * @param videoInputDevices
 * @param sourceSelect
 * 
 * @return
 */
function showInputDevices(videoInputDevices, sourceSelect)
{
	videoInputDevices.forEach((element) => {
		const sourceOption = document.createElement('option');
		sourceOption.text = element.label;
		sourceOption.value = element.deviceId;
		sourceSelect.appendChild(sourceOption);
	})
	
	const sourceSelectPanel = document.getElementById('sourceSelectPanel');
	sourceSelectPanel.style.display = 'block';
}
