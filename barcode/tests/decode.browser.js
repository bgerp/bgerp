/** Real pixel decoding with synthetic data. GPL 3, 2026 Experta EOOD. */
async function checkBarcodeDecoders() {
    var checks = 0, failures = [], reader = new Html5Qrcode('reader', {useBarCodeDetectorIfSupported:false}), zxing = new ZXing.BrowserMultiFormatReader();
    /** Fails if decoding changes content, including leading zeros. */
    function check(ok, message) { checks++; if (!ok) failures.push(message); }
    /** Creates white canvas fixtures without remote images. */
    function canvas(w,h) { var c=document.createElement('canvas'); c.width=w;c.height=h;c.getContext('2d').fillStyle='#fff';c.getContext('2d').fillRect(0,0,w,h); return c; }
    /** Draws a matrix from the official writer. */
    function matrixImage(matrix) { var c=canvas(matrix.getWidth(),matrix.getHeight()),ctx=c.getContext('2d');ctx.fillStyle='#000';for(var y=0;y<c.height;y++)for(var x=0;x<c.width;x++)if(matrix.get(x,y))ctx.fillRect(x,y,1,1);return c; }
    var hints=new Map();hints.set(ZXing.EncodeHintType.CHARACTER_SET,'UTF-8');
    var qr=matrixImage(new ZXing.QRCodeWriter().encode('Кирилица 001234',ZXing.BarcodeFormat.QR_CODE,300,300,hints));
    var rotated=canvas(300,300),rotation=rotated.getContext('2d'),pixels=qr.getContext('2d').getImageData(0,0,300,300).data;rotation.fillStyle='#000';for(var y=0;y<300;y++)for(var x=0;x<300;x++)if(pixels[(y*300+x)*4]<128)rotation.fillRect(299-y,x,1,1);
    var dmCore=matrixImage(new ZXing.DataMatrixWriter().encode('TEST-00123',ZXing.BarcodeFormat.DATA_MATRIX,240,240)),dm=canvas(280,280);dm.getContext('2d').drawImage(dmCore,20,20);
    // Independent EAN-13 fixture 5901234123457: standard L/G/R encodings.
    var L=['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
    var G=['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
    var digits='5901234123457',parity='LGGLLG',bits='101';
    for(var n=1;n<7;n++)bits+=(parity[n-1]==='L'?L:G)[Number(digits[n])];bits+='01010';
    for(var n=7;n<13;n++)bits+=L[Number(digits[n])].replace(/[01]/g,function(b){return b==='1'?'0':'1';});bits+='101';
    var ean=canvas(460,240),ctx=ean.getContext('2d');ctx.fillStyle='#000';for(var n=0;n<bits.length;n++)if(bits[n]==='1')ctx.fillRect(40+n*4,30,4,180);
    var fixtures=[[qr,'Кирилица 001234'],[rotated,'Кирилица 001234'],[ean,digits],[dm,'TEST-00123']];
    document.getElementById('pictures').textContent='';
    try {
        for (var i=0;i<fixtures.length;i++) {
            var c=fixtures[i][0],expected=fixtures[i][1]; document.getElementById('pictures').appendChild(c);
            var blob=await new Promise(function(resolve){c.toBlob(resolve,'image/png');});
            try { check(await reader.scanFile(new File([blob],'synthetic.png',{type:'image/png'}),false)===expected,'Html5Qrcode fixture '+i); } catch(e) { check(false,'Html5Qrcode fixture '+i+': '+String(e)); }
            try { var result=await zxing.decodeFromImageUrl(c.toDataURL());check(result.getText()===expected,'ZXing fixture '+i); } catch(e) { check(false,'ZXing fixture '+i+': '+String(e)); } zxing.reset();
        }
        var blank=canvas(300,300),blob=await new Promise(function(resolve){blank.toBlob(resolve,'image/png');});
        var rejected=false;try{await reader.scanFile(new File([blob],'blank.png',{type:'image/png'}),false);}catch(e){rejected=true;}check(rejected,'Html5Qrcode rejects blank image');
        rejected=false;try{await zxing.decodeFromImageUrl(blank.toDataURL());}catch(e){rejected=true;}check(rejected,'ZXing rejects blank image');
        check(reader.getState()===Html5QrcodeScannerState.NOT_STARTED,'File decoding does not start camera');
        return {checks:checks,failures:failures};
    } finally { reader.clear();zxing.reset(); }
}
