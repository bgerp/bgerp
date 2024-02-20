const wrapper = document.getElementById("signature-pad");
const clearButton = wrapper.querySelector("[data-action=clear]");
const savePNGButton = wrapper.querySelector("[data-action=save-png]");
const canvas = wrapper.querySelector("canvas");
const signaturePad = new SignaturePad(canvas, {
  // It's Necessary to use an opaque color when saving image as JPEG;
  // this option can be omitted if only saving as PNG or SVG
  backgroundColor: 'rgba(255, 255, 255, 0)'
});
signaturePad.penColor = "rgb(0, 43, 89)";
signaturePad.minWidth = 5;
signaturePad.maxWidth = 10;

// Adjust canvas coordinate space taking into account pixel ratio,
// to make it look crisp on mobile devices.
// This also causes canvas to be cleared.
function resizeCanvas() {
  // When zoomed out to less than 100%, for some very strange reason,
  // some browsers report devicePixelRatio as less than 1
  // and only part of the canvas is cleared then.
  const ratio =  Math.max(window.devicePixelRatio || 1, 1);

  // This part causes the canvas to be cleared
  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  canvas.getContext("2d").scale(ratio, ratio);

  // This library does not listen for canvas changes, so after the canvas is automatically
  // cleared by the browser, SignaturePad#isEmpty might still return false, even though the
  // canvas looks empty, because the internal data of this library wasn't cleared. To make sure
  // that the state of this library is consistent with visual state of the canvas, you
  // have to clear it manually.
  //signaturePad.clear();
  
  // If you want to keep the drawing on resize instead of clearing it you can reset the data.
  signaturePad.fromData(signaturePad.toData());
}

// On mobile devices it might make more sense to listen to orientation change,
// rather than window resize events.
window.onresize = resizeCanvas;
resizeCanvas();

function download(dataURL, filename) {
  const blob = dataURLToBlob(dataURL);
  const url = window.URL.createObjectURL(blob);

  const a = document.createElement("a");
  a.style = "display: none";
  a.href = url;
  a.download = filename;

  document.body.appendChild(a);
  a.click();

  window.URL.revokeObjectURL(url);
}

// One could simply use Canvas#toBlob method instead, but it's just to show
// that it can be done using result of SignaturePad#toDataURL.
function dataURLToBlob(dataURL) {
  // Code taken from https://github.com/ebidel/filer.js
  const parts = dataURL.split(';base64,');
  const contentType = parts[0].split(":")[1];
  const raw = window.atob(parts[1]);
  const rawLength = raw.length;
  const uInt8Array = new Uint8Array(rawLength);

  for (let i = 0; i < rawLength; ++i) {
    uInt8Array[i] = raw.charCodeAt(i);
  }

  return new Blob([uInt8Array], { type: contentType });
}

clearButton.addEventListener("click", () => {
  signaturePad.clear();
});

function padActions() {

  $(document.body).on('click', ".saveBtn, .saveStampBtn", function(e){
    if (signaturePad.isEmpty()) {
      let warning = $(this).attr("data-warning");
      alert(warning);
    } else {
      const dataURL = signaturePad.toDataURL('image/png', {includeBackgroundColor: false});

      let url = $(this).attr("data-url");
      if(!url) return;

      console.log("URL:" + url);
      console.log(dataURL);

      let resObj = new Object();
      resObj['url'] = url;
      let params = {sign: dataURL};
      getEfae().process(resObj, params);
    }
  });


  $(document.body).on('click', ".cancelBtn", function(e){
    let url = $(this).attr("data-url");

    location.href = url;
  });
}
