/*
 * Помощни функции за логване и смяна на паролата
 */
var scriptStart =  new Date().getTime();


/**
 * Изпълнява се след субмитване на логин формата
 */
function loginFormSubmit(form, passSalt, hashFactor, nickType)
{   
	var pass = form.pass.value;
	form.pass.value = '';
	
	if (nickType == 'email') {
		var nick = form.email.value;
	} else {
		var nick = form.nick.value;
	}
	var passwordHash = encodePwd(pass, nick, passSalt, hashFactor);

	form.hash.value = applyChallenge(passwordHash, form.time.value);
	form.time.value = parseInt(form.time.value) + Math.round((new Date().getTime() - scriptStart)/1000 );

	return true;
}

/**
 * Трябва да установим следните параметри:
 * passExHash
 * passHash
 * passReHash
 * timeHash
 * isPassLenOK
 * 
 */
function userFormSubmit(form, passSalt, hashFactor, nickType, minPassLen, lg)
{
	if (nickType == 'email') {
		var nick = form.email.value;
	} else {
		var nick = form.nick.value;
	}

	if(form['passEx'] != undefined) {
		if(form.passEx.value != '') {
			form.passExHash.value = encodePwd(form.passEx.value, nick, passSalt, hashFactor);
		}
	}
	
	if(form['passNew'] != undefined) {
		if(form.passNew.value != '') {
			
			
			
			// Проверка дали новата парола отговаря на изискването за дължина
			if(form.passNew.value.length < minPassLen) {

				if(lg == 'bg') {
					alert('Паролата трябва да е минимум ' + minPassLen.toString() + ' символа!');
				} else {
					alert('Password must be at least ' + minPassLen.toString() + ' characters!');
				}

				return false;
			}

			// Проверка за съвпадане на двете въвеждания на новата парола
			if(form.passNew.value != form.passRe.value) {
				
				if(lg == 'bg') {
					alert('Паролите не си съвпадат, моля напишете ги отново!');
				} else {
					alert('Passwords do not match, please retype!');
				}

				return false;
			}

			var pass = form.passNew.value;
			form.passNew.value = '';
			form.passRe.value = '';

			form.passNewHash.value = encodePwd(pass, nick, passSalt, hashFactor);
		}
	}
	
	// Нулиране на инпут полето за старата парола
	if(form['passEx'] != undefined) {
		form.passEx.value = '';
	}

	return true;
}

/**
 * Хеширане на потребителската парола, преди изпращане
 */
function encodePwd(password, nick, salt, hashFactor)
{
	var res = '';
 
	nick = nick.toLowerCase();
	hashFactor = Math.min(10, password.length) * hashFactor;
		
	for(var i = 0; i <= hashFactor; i++) {
		res = CryptoJS.SHA256(res + nick + salt + i.toString() + password);
	}
  
	return res;
}



/**
 * Хеширане на потребителската парола, преди изпращане
 */
function encodePwd(password, nick, salt, hashFactor)
{	
	var	res = MD5(password + MD5(password) + salt);
 
	return res;
}


/**
 * Хешира хеша на паролата и времето
 */
function applyChallenge(ps5Enc, time)
{
	return CryptoJS.SHA256(ps5Enc + time.toString());
}

/*
CryptoJS v3.0.2
code.google.com/p/crypto-js
(c) 2009-2012 by Jeff Mott. All rights reserved.
code.google.com/p/crypto-js/wiki/License
*/
var CryptoJS=CryptoJS||function(i,p){var f={},q=f.lib={},j=q.Base=function(){function a(){}return{extend:function(h){a.prototype=this;var d=new a;h&&d.mixIn(h);d.$super=this;return d},create:function(){var a=this.extend();a.init.apply(a,arguments);return a},init:function(){},mixIn:function(a){for(var d in a)a.hasOwnProperty(d)&&(this[d]=a[d]);a.hasOwnProperty("toString")&&(this.toString=a.toString)},clone:function(){return this.$super.extend(this)}}}(),k=q.WordArray=j.extend({init:function(a,h){a=
this.words=a||[];this.sigBytes=h!=p?h:4*a.length},toString:function(a){return(a||m).stringify(this)},concat:function(a){var h=this.words,d=a.words,c=this.sigBytes,a=a.sigBytes;this.clamp();if(c%4)for(var b=0;b<a;b++)h[c+b>>>2]|=(d[b>>>2]>>>24-8*(b%4)&255)<<24-8*((c+b)%4);else if(65535<d.length)for(b=0;b<a;b+=4)h[c+b>>>2]=d[b>>>2];else h.push.apply(h,d);this.sigBytes+=a;return this},clamp:function(){var a=this.words,b=this.sigBytes;a[b>>>2]&=4294967295<<32-8*(b%4);a.length=i.ceil(b/4)},clone:function(){var a=
j.clone.call(this);a.words=this.words.slice(0);return a},random:function(a){for(var b=[],d=0;d<a;d+=4)b.push(4294967296*i.random()|0);return k.create(b,a)}}),r=f.enc={},m=r.Hex={stringify:function(a){for(var b=a.words,a=a.sigBytes,d=[],c=0;c<a;c++){var e=b[c>>>2]>>>24-8*(c%4)&255;d.push((e>>>4).toString(16));d.push((e&15).toString(16))}return d.join("")},parse:function(a){for(var b=a.length,d=[],c=0;c<b;c+=2)d[c>>>3]|=parseInt(a.substr(c,2),16)<<24-4*(c%8);return k.create(d,b/2)}},s=r.Latin1={stringify:function(a){for(var b=
a.words,a=a.sigBytes,d=[],c=0;c<a;c++)d.push(String.fromCharCode(b[c>>>2]>>>24-8*(c%4)&255));return d.join("")},parse:function(a){for(var b=a.length,d=[],c=0;c<b;c++)d[c>>>2]|=(a.charCodeAt(c)&255)<<24-8*(c%4);return k.create(d,b)}},g=r.Utf8={stringify:function(a){try{return decodeURIComponent(escape(s.stringify(a)))}catch(b){throw Error("Malformed UTF-8 data");}},parse:function(a){return s.parse(unescape(encodeURIComponent(a)))}},b=q.BufferedBlockAlgorithm=j.extend({reset:function(){this._data=k.create();
this._nDataBytes=0},_append:function(a){"string"==typeof a&&(a=g.parse(a));this._data.concat(a);this._nDataBytes+=a.sigBytes},_process:function(a){var b=this._data,d=b.words,c=b.sigBytes,e=this.blockSize,f=c/(4*e),f=a?i.ceil(f):i.max((f|0)-this._minBufferSize,0),a=f*e,c=i.min(4*a,c);if(a){for(var g=0;g<a;g+=e)this._doProcessBlock(d,g);g=d.splice(0,a);b.sigBytes-=c}return k.create(g,c)},clone:function(){var a=j.clone.call(this);a._data=this._data.clone();return a},_minBufferSize:0});q.Hasher=b.extend({init:function(){this.reset()},
reset:function(){b.reset.call(this);this._doReset()},update:function(a){this._append(a);this._process();return this},finalize:function(a){a&&this._append(a);this._doFinalize();return this._hash},clone:function(){var a=b.clone.call(this);a._hash=this._hash.clone();return a},blockSize:16,_createHelper:function(a){return function(b,d){return a.create(d).finalize(b)}},_createHmacHelper:function(a){return function(b,d){return e.HMAC.create(a,d).finalize(b)}}});var e=f.algo={};return f}(Math);
(function(i){var p=CryptoJS,f=p.lib,q=f.WordArray,f=f.Hasher,j=p.algo,k=[],r=[];(function(){function f(a){for(var b=i.sqrt(a),d=2;d<=b;d++)if(!(a%d))return!1;return!0}function g(a){return 4294967296*(a-(a|0))|0}for(var b=2,e=0;64>e;)f(b)&&(8>e&&(k[e]=g(i.pow(b,0.5))),r[e]=g(i.pow(b,1/3)),e++),b++})();var m=[],j=j.SHA256=f.extend({_doReset:function(){this._hash=q.create(k.slice(0))},_doProcessBlock:function(f,g){for(var b=this._hash.words,e=b[0],a=b[1],h=b[2],d=b[3],c=b[4],i=b[5],j=b[6],k=b[7],l=0;64>
l;l++){if(16>l)m[l]=f[g+l]|0;else{var n=m[l-15],o=m[l-2];m[l]=((n<<25|n>>>7)^(n<<14|n>>>18)^n>>>3)+m[l-7]+((o<<15|o>>>17)^(o<<13|o>>>19)^o>>>10)+m[l-16]}n=k+((c<<26|c>>>6)^(c<<21|c>>>11)^(c<<7|c>>>25))+(c&i^~c&j)+r[l]+m[l];o=((e<<30|e>>>2)^(e<<19|e>>>13)^(e<<10|e>>>22))+(e&a^e&h^a&h);k=j;j=i;i=c;c=d+n|0;d=h;h=a;a=e;e=n+o|0}b[0]=b[0]+e|0;b[1]=b[1]+a|0;b[2]=b[2]+h|0;b[3]=b[3]+d|0;b[4]=b[4]+c|0;b[5]=b[5]+i|0;b[6]=b[6]+j|0;b[7]=b[7]+k|0},_doFinalize:function(){var f=this._data,g=f.words,b=8*this._nDataBytes,
e=8*f.sigBytes;g[e>>>5]|=128<<24-e%32;g[(e+64>>>9<<4)+15]=b;f.sigBytes=4*g.length;this._process()}});p.SHA256=f._createHelper(j);p.HmacSHA256=f._createHmacHelper(j)})(Math);


/**
 *
 *  MD5 (Message-Digest Algorithm)
 *  http://www.webtoolkit.info/
 *
**/
var MD5 = function (string) {
 
	function RotateLeft(lValue, iShiftBits) {
		return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
	}
 
	function AddUnsigned(lX,lY) {
		var lX4,lY4,lX8,lY8,lResult;
		lX8 = (lX & 0x80000000);
		lY8 = (lY & 0x80000000);
		lX4 = (lX & 0x40000000);
		lY4 = (lY & 0x40000000);
		lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
		if (lX4 & lY4) {
			return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
		}
		if (lX4 | lY4) {
			if (lResult & 0x40000000) {
				return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
			} else {
				return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
			}
		} else {
			return (lResult ^ lX8 ^ lY8);
		}
 	}
 
 	function F(x,y,z) { return (x & y) | ((~x) & z); }
 	function G(x,y,z) { return (x & z) | (y & (~z)); }
 	function H(x,y,z) { return (x ^ y ^ z); }
	function I(x,y,z) { return (y ^ (x | (~z))); }
 
	function FF(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function GG(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function HH(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function II(a,b,c,d,x,s,ac) {
		a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
		return AddUnsigned(RotateLeft(a, s), b);
	};
 
	function ConvertToWordArray(string) {
		var lWordCount;
		var lMessageLength = string.length;
		var lNumberOfWords_temp1=lMessageLength + 8;
		var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
		var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
		var lWordArray=Array(lNumberOfWords-1);
		var lBytePosition = 0;
		var lByteCount = 0;
		while ( lByteCount < lMessageLength ) {
			lWordCount = (lByteCount-(lByteCount % 4))/4;
			lBytePosition = (lByteCount % 4)*8;
			lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
			lByteCount++;
		}
		lWordCount = (lByteCount-(lByteCount % 4))/4;
		lBytePosition = (lByteCount % 4)*8;
		lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
		lWordArray[lNumberOfWords-2] = lMessageLength<<3;
		lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
		return lWordArray;
	};
 
	function WordToHex(lValue) {
		var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
		for (lCount = 0;lCount<=3;lCount++) {
			lByte = (lValue>>>(lCount*8)) & 255;
			WordToHexValue_temp = "0" + lByte.toString(16);
			WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
		}
		return WordToHexValue;
	};
 
	function Utf8Encode(string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
 
		return utftext;
	};
 
	var x=Array();
	var k,AA,BB,CC,DD,a,b,c,d;
	var S11=7, S12=12, S13=17, S14=22;
	var S21=5, S22=9 , S23=14, S24=20;
	var S31=4, S32=11, S33=16, S34=23;
	var S41=6, S42=10, S43=15, S44=21;
 
	string = Utf8Encode(string);
 
	x = ConvertToWordArray(string);
 
	a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
 
	for (k=0;k<x.length;k+=16) {
		AA=a; BB=b; CC=c; DD=d;
		a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
		d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
		c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
		b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
		a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
		d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
		c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
		b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
		a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
		d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
		c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
		b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
		a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
		d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
		c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
		b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
		a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
		d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
		c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
		b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
		a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
		d=GG(d,a,b,c,x[k+10],S22,0x2441453);
		c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
		b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
		a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
		d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
		c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
		b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
		a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
		d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
		c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
		b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
		a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
		d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
		c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
		b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
		a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
		d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
		c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
		b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
		a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
		d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
		c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
		b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
		a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
		d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
		c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
		b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
		a=II(a,b,c,d,x[k+0], S41,0xF4292244);
		d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
		c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
		b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
		a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
		d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
		c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
		b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
		a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
		d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
		c=II(c,d,a,b,x[k+6], S43,0xA3014314);
		b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
		a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
		d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
		c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
		b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
		a=AddUnsigned(a,AA);
		b=AddUnsigned(b,BB);
		c=AddUnsigned(c,CC);
		d=AddUnsigned(d,DD);
	}
 
	var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
 
	return temp.toLowerCase();
}