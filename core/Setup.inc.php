<?php


/**
 * Скрипт 'Setup.inc.php' -  Инсталиране на bgERP
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link 
 */
 

/**********************************
 * Първоначални проверки за достъп до Setup-а
 **********************************/

// Ако извикването идва от крон-а го игнорираме
if (($_GET['Ctr'] == 'core_Cron' || $_GET['Act'] == 'cron')) {
    return;
}

// Колко време е валидно заключването - в секунди
DEFINE ('SETUP_LOCK_PERIOD', 240);

defIfNot('BGERP_GIT_PATH', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '"C:/Program Files (x86)/Git/bin/git.exe"' : 'git');

if (setupKeyValid() && !setupProcess()) {
    // Опит за стартиране на сетъп
    if (!setupLock()) {
        halt("Грешка при стартиране на Setup.");
    }
    setcookie("setup", setupKey(), time() + SETUP_LOCK_PERIOD);
} elseif (!setupKeyValid() && !setupProcess()) {
    // Ако не сме в setup режим и няма изискване за такъв връщаме в нормалното изпълнение на приложението
    // Ако има останало cookie го чистим
    if (isset($_COOKIE['setup'])) {
        setcookie("setup", "", time()-3600);    
    }
    
    return;
} elseif (!setupKeyValid() && setupProcess() && !isset($_COOKIE['setup'])) {
    // Стартиран setup режим - неоторизиран потребител - връща подходящо съобщение и излиза
    // Не спираме bgERP-a на потребителите по време на сетъп процес
    
    return;
    // halt("Процес на обновяване - опитайте по късно.");
}


// На коя стъпка се намираме в момента?
$step = $_GET['step'] ? $_GET['step'] : 1;
$texts['currentStep'] = $step;

$flagOK = MD5($_GET['SetupKey'] . 'flagOK');
if($step == 'testSelfUrl') {
    echo $flagOK;
    die;
}

// Какъв е протокол-а
if (isset($_SERVER['HTTPS']) &&
		($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
	$protocol = 'https://';
}
else {
	$protocol = 'http://';
}

if($username = $_SERVER['PHP_AUTH_USER']) {
    $password = $_SERVER['PHP_AUTH_PW'];
    $auth = $username . ':' . $password . '@';
} else {
    $auth = '';
}

// Собственото URL
$selfUri = "{$protocol}{$auth}{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
 

// Определяне на локалното URL и контекста
$opts = array(
    'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
                "Cookie: setup=bar\r\n",
    'timeout'=>2
      )
);

$context = stream_context_create($opts);
if(defined('BGERP_ABSOLUTE_HTTP_HOST')) {
    $localUrl = str_replace("{$protocol}{$auth}{$_SERVER['HTTP_HOST']}", "{$protocol}{$auth}" . BGERP_ABSOLUTE_HTTP_HOST, $selfUri);
} else {
    $localUrl = $selfUri;
}

// URL на следващата стъпка
$selfUrl = addParams($selfUri, array('step' => $step));
$nextUrl = addParams($selfUri, array('step' => $step+1));
 
// Определяме линка към приложението
$appUri = $selfUrl; 
if (strpos($selfUrl,'core_Packs/systemUpdate') !== FALSE) {
    $appUri = substr($selfUrl, 0, strpos($selfUrl,'core_Packs/systemUpdate'));
}
if (strpos($appUri,'/?') !== FALSE) {
    $appUri = substr($appUri, 0, strpos($appUri,'/?'));
} 

if (isset($_REQUEST['cancel'])) {
	setupUnlock();	

	header("location: {$appUri}");
}

ob_end_clean();
header("Content-Type: text/html; charset=UTF-8");

// Стилове
$texts['styles'] = "
<style type=\"text/css\">
body {
    background-color:#7bb6f8; 
    color:#000;
    font-family:Verdana,Arial;
	margin:0;
}

.setup-body{
	margin:5px;
}

ul{
	padding:0;
}

a {
    color:#081dd8;
}

a:hover {
    text-decoration:none;
}

#license {
    text-align:justify;
}

.holder{
	width:800px;
	padding:20px;
}

.msg {
    width:100%;
	padding:0;
}

.msg li.step , .msg.stats li{
    padding:10px;
    font-weight:normal;
	box-shadow:0 0 5px rgba(0,0,0,0.5);
    background-color:#94cbf9;
	width:24.8%;
	float:left;
	padding:1em 0;
	text-align:center;
	margin:0 0.1%;
	list-style:none;
}

.msg li a {
	line-height:normal !important;
	outline:none;
	-webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

.msg li.step{
	margin-bottom:0.3%;
	white-space:nowrap;
}

.msg li.step a{
	display:block;
}

li.clear{
	list-style:none;
	clear:both;
}		

.msg.stats li{
	width:99.8%;
	margin:0 0.1% 20px;
}
		
h1 {
   	font-size:2.2em;
	margin:0px;
	color: #FFF;
	text-shadow: 2px 4px 3px rgba(0, 0, 0, 0.3);
}

#logo {
    box-shadow: 2px 4px 3px rgba(0,0,0,0.3);
	background: #FFF;
	display:block; 
	float:left;
	margin-right:10px;
}

#step" . $step . " {
    color:black !important;
    background-color:#fbfbab !important;
}

#astep" . $step . " {
 color:000 !important;
}

a.menu {
 color:#000 !important;
 text-decoration:none;
}

#logo td {
    width:8px;
    height:8px;
    background-color:white;
}

.h {
    margin-top:5px;
    line-height:1.5em;
    font-weight:bold;
}

.err, .err b {
    color:#e00 !important;
    text-decoration:blink;
    line-height:1.5em;
}

.inf {
    line-height:1.5em;
}

.new {
    line-height:1.5em;
    color:#006600 !important;
}

.wrn, .wrn b {
    color:#aa5500 !important;
    line-height:1.5em;
}

#progress {
    list-style-type: none;
	padding-bottom:10px;
}

#progressTitle {
    width: 180px;
    display: block;
    float: left;
    text-align:right;
}

#progressIndicator {
 	text-align: right;
 	padding-right: 2px;
 	background-color: #ffcc00;
}

#progressPercents {
    font-size: .8em;
    font-weight: bold;
    margin-left:3px;
}

#init {
    border: 0px;
    margin: 0;
    padding: 0;
    width: 800px;
    height: 610px;
    overflow: hidden;
}

#setupLog {
    position:absolute;
    left: 0px;
    top: 180px;
    width: 800px;
    height: 425px;
    overflow:auto;
	background-color: rgba(255, 255, 255, 0.3);
}


.debug-info {
    color:black;
}

.debug-notice {
    color:#800;
}

.debug-new {
    color:#0a0;
}

.debug-update {
	color:#0a0;
}

.debug-error {
	color:#d00;
}
#setupLog li.debug-error {
    font-size: 1.5em;
}

#setupLog li {
    font-size: 0.6em;
}

#setupLog h2 {
    font-size: 0.8em;
}


/*край на цветове*/

#success {
    position:absolute;
    top:110px;
    padding-left:190px;
}

#startHeader li{
	font-size:0.85em;
}
		
@media handheld, only screen and (max-width: 768px){
	.holder{
		width:100%;
		padding:0;
	}
	
	.holder > table{
		width:100% !important;
	}
}

@media handheld, only screen and (max-width: 320px), only screen and (max-width:533px), only screen and (max-width: 480px), only screen and (max-width: 640px) {  
	
	.msg li.step{
		width:99.8%;
		margin-bottom:0.2em;
	}
	.msg li.step a{
		text-align:left;
		font-size:1.1em;
		padding-left:1em;
	}
	.holder{
		width:100%;
		padding:0;
	}
	#progress{
		width:100%;
		overflow:hidden;
	}
	.holder > table{
		width:100% !important;
	}
	
	#init {
		width:100%;
	}
	#logo td{
		height:4px;
		width:4px;
	}
	#setupLog{
		width:100%;
		overflow:auto;
	}
	#init {
		width:100%;
		overflow:hidden;
	}
	.msg{
		margin:0 0 20px;	
	}
	
	h1{
		font-size:1.6em;
	}
	
	#progressTitle {
		float:none;
		width:100%;
		text-align:center;
	}
		
	#progressPercents{
		display: block;
		text-align:center;
	}
	
	#success {
		position: relative;
		top: -26px;
		font-size: 1.1em;
		padding-left: 0;
		padding-bottom: 20px;
	}
	
	#setupLog {
		top:200px;
		width:100%;
	}
	#startHeader li{
		background-color:#fbfbab !important;
	}
}	
</style>
";

$texts['scripts'] = "<script>
" .
'/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */
var hexcase=0;function hex_md5(a){return rstr2hex(rstr_md5(str2rstr_utf8(a)))}function hex_hmac_md5(a,b){return rstr2hex(rstr_hmac_md5(str2rstr_utf8(a),str2rstr_utf8(b)))}function md5_vm_test(){return hex_md5("abc").toLowerCase()=="900150983cd24fb0d6963f7d28e17f72"}function rstr_md5(a){return binl2rstr(binl_md5(rstr2binl(a),a.length*8))}function rstr_hmac_md5(c,f){var e=rstr2binl(c);if(e.length>16){e=binl_md5(e,c.length*8)}var a=Array(16),d=Array(16);for(var b=0;b<16;b++){a[b]=e[b]^909522486;d[b]=e[b]^1549556828}var g=binl_md5(a.concat(rstr2binl(f)),512+f.length*8);return binl2rstr(binl_md5(d.concat(g),512+128))}function rstr2hex(c){try{hexcase}catch(g){hexcase=0}var f=hexcase?"0123456789ABCDEF":"0123456789abcdef";var b="";var a;for(var d=0;d<c.length;d++){a=c.charCodeAt(d);b+=f.charAt((a>>>4)&15)+f.charAt(a&15)}return b}function str2rstr_utf8(c){var b="";var d=-1;var a,e;while(++d<c.length){a=c.charCodeAt(d);e=d+1<c.length?c.charCodeAt(d+1):0;if(55296<=a&&a<=56319&&56320<=e&&e<=57343){a=65536+((a&1023)<<10)+(e&1023);d++}if(a<=127){b+=String.fromCharCode(a)}else{if(a<=2047){b+=String.fromCharCode(192|((a>>>6)&31),128|(a&63))}else{if(a<=65535){b+=String.fromCharCode(224|((a>>>12)&15),128|((a>>>6)&63),128|(a&63))}else{if(a<=2097151){b+=String.fromCharCode(240|((a>>>18)&7),128|((a>>>12)&63),128|((a>>>6)&63),128|(a&63))}}}}}return b}function rstr2binl(b){var a=Array(b.length>>2);for(var c=0;c<a.length;c++){a[c]=0}for(var c=0;c<b.length*8;c+=8){a[c>>5]|=(b.charCodeAt(c/8)&255)<<(c%32)}return a}function binl2rstr(b){var a="";for(var c=0;c<b.length*32;c+=8){a+=String.fromCharCode((b[c>>5]>>>(c%32))&255)}return a}function binl_md5(p,k){p[k>>5]|=128<<((k)%32);p[(((k+64)>>>9)<<4)+14]=k;var o=1732584193;var n=-271733879;var m=-1732584194;var l=271733878;for(var g=0;g<p.length;g+=16){var j=o;var h=n;var f=m;var e=l;o=md5_ff(o,n,m,l,p[g+0],7,-680876936);l=md5_ff(l,o,n,m,p[g+1],12,-389564586);m=md5_ff(m,l,o,n,p[g+2],17,606105819);n=md5_ff(n,m,l,o,p[g+3],22,-1044525330);o=md5_ff(o,n,m,l,p[g+4],7,-176418897);l=md5_ff(l,o,n,m,p[g+5],12,1200080426);m=md5_ff(m,l,o,n,p[g+6],17,-1473231341);n=md5_ff(n,m,l,o,p[g+7],22,-45705983);o=md5_ff(o,n,m,l,p[g+8],7,1770035416);l=md5_ff(l,o,n,m,p[g+9],12,-1958414417);m=md5_ff(m,l,o,n,p[g+10],17,-42063);n=md5_ff(n,m,l,o,p[g+11],22,-1990404162);o=md5_ff(o,n,m,l,p[g+12],7,1804603682);l=md5_ff(l,o,n,m,p[g+13],12,-40341101);m=md5_ff(m,l,o,n,p[g+14],17,-1502002290);n=md5_ff(n,m,l,o,p[g+15],22,1236535329);o=md5_gg(o,n,m,l,p[g+1],5,-165796510);l=md5_gg(l,o,n,m,p[g+6],9,-1069501632);m=md5_gg(m,l,o,n,p[g+11],14,643717713);n=md5_gg(n,m,l,o,p[g+0],20,-373897302);o=md5_gg(o,n,m,l,p[g+5],5,-701558691);l=md5_gg(l,o,n,m,p[g+10],9,38016083);m=md5_gg(m,l,o,n,p[g+15],14,-660478335);n=md5_gg(n,m,l,o,p[g+4],20,-405537848);o=md5_gg(o,n,m,l,p[g+9],5,568446438);l=md5_gg(l,o,n,m,p[g+14],9,-1019803690);m=md5_gg(m,l,o,n,p[g+3],14,-187363961);n=md5_gg(n,m,l,o,p[g+8],20,1163531501);o=md5_gg(o,n,m,l,p[g+13],5,-1444681467);l=md5_gg(l,o,n,m,p[g+2],9,-51403784);m=md5_gg(m,l,o,n,p[g+7],14,1735328473);n=md5_gg(n,m,l,o,p[g+12],20,-1926607734);o=md5_hh(o,n,m,l,p[g+5],4,-378558);l=md5_hh(l,o,n,m,p[g+8],11,-2022574463);m=md5_hh(m,l,o,n,p[g+11],16,1839030562);n=md5_hh(n,m,l,o,p[g+14],23,-35309556);o=md5_hh(o,n,m,l,p[g+1],4,-1530992060);l=md5_hh(l,o,n,m,p[g+4],11,1272893353);m=md5_hh(m,l,o,n,p[g+7],16,-155497632);n=md5_hh(n,m,l,o,p[g+10],23,-1094730640);o=md5_hh(o,n,m,l,p[g+13],4,681279174);l=md5_hh(l,o,n,m,p[g+0],11,-358537222);m=md5_hh(m,l,o,n,p[g+3],16,-722521979);n=md5_hh(n,m,l,o,p[g+6],23,76029189);o=md5_hh(o,n,m,l,p[g+9],4,-640364487);l=md5_hh(l,o,n,m,p[g+12],11,-421815835);m=md5_hh(m,l,o,n,p[g+15],16,530742520);n=md5_hh(n,m,l,o,p[g+2],23,-995338651);o=md5_ii(o,n,m,l,p[g+0],6,-198630844);l=md5_ii(l,o,n,m,p[g+7],10,1126891415);m=md5_ii(m,l,o,n,p[g+14],15,-1416354905);n=md5_ii(n,m,l,o,p[g+5],21,-57434055);o=md5_ii(o,n,m,l,p[g+12],6,1700485571);l=md5_ii(l,o,n,m,p[g+3],10,-1894986606);m=md5_ii(m,l,o,n,p[g+10],15,-1051523);n=md5_ii(n,m,l,o,p[g+1],21,-2054922799);o=md5_ii(o,n,m,l,p[g+8],6,1873313359);l=md5_ii(l,o,n,m,p[g+15],10,-30611744);m=md5_ii(m,l,o,n,p[g+6],15,-1560198380);n=md5_ii(n,m,l,o,p[g+13],21,1309151649);o=md5_ii(o,n,m,l,p[g+4],6,-145523070);l=md5_ii(l,o,n,m,p[g+11],10,-1120210379);m=md5_ii(m,l,o,n,p[g+2],15,718787259);n=md5_ii(n,m,l,o,p[g+9],21,-343485551);o=safe_add(o,j);n=safe_add(n,h);m=safe_add(m,f);l=safe_add(l,e)}return Array(o,n,m,l)}function md5_cmn(h,e,d,c,g,f){return safe_add(bit_rol(safe_add(safe_add(e,h),safe_add(c,f)),g),d)}function md5_ff(g,f,k,j,e,i,h){return md5_cmn((f&k)|((~f)&j),g,f,e,i,h)}function md5_gg(g,f,k,j,e,i,h){return md5_cmn((f&j)|(k&(~j)),g,f,e,i,h)}function md5_hh(g,f,k,j,e,i,h){return md5_cmn(f^k^j,g,f,e,i,h)}function md5_ii(g,f,k,j,e,i,h){return md5_cmn(k^(f|(~j)),g,f,e,i,h)}function safe_add(a,d){var c=(a&65535)+(d&65535);var b=(a>>16)+(d>>16)+(c>>16);return(b<<16)|(c&65535)}function bit_rol(a,b){return(a<<b)|(a>>>(32-b))};
' .
"
</script>";

// Лейаута на HTML страницата
$layout = 
" <!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\n \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=2\">
<title>bgERP - настройване на системата (стъпка [#currentStep#] )</title>
[#styles#]


<link  rel=\"shortcut icon\" 
href=\"data:image/icon;base64,AAABAAEAEBAAAAAAAABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAABaALPDWgCzw1oAs8N" . "aALPDWgCzw////wDjqQD/46kA/+OpAP/jqQD/////AAB79eAAe/XgAHv14AB79eAAe/XgWgCzw1oAs8NaALPDWgCzw1oAs8P///8A46kA/+OpAP/jqQD/46kA/////wA" . "Ae/XgAHv14AB79eAAe/XgAHv14FwAvLBcALywXAC8sFwAvLBcALyw////AO+4AO3vuADt77gA7e+4AO3///8AAJb34wCW9+MAlvfjAJb34wCW9+NfAMqcXwDKnF8Aypx" . "fAMqcXwDKnP///wDxugDo8boA6PG6AOjxugDo////AACa+OQAmvjkAJr45ACa+OQAmvjkXwDKnF8AypxfAMqcXwDKnF8Aypz///8A8sUAzfPIAMXzyADF8sQAzv///wA" . "ApvjLAK35vgCu+b4AqvnEAKr5xP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wAAfS/MAH0vzAB9L8w" . "AfS/MAH0vzP///wABAAXgAQAF4AEABeABAAXg////AAB9L8wAfS/MAH0vzAB9L8wAfS/MAIs9yACLPcgAiz3IAIs9yACLPcj///8AAQAF4AEABeABAAXgAQAF4P///wA" . "Aiz3IAIs9yACLPcgAiz3IAIs9yACZTMUAmUzFAJlMxQCZTMUAmUzF////AAEABeABAAXgAQAF4AEABeD///8AAJlMxQCZTMUAmUzFAJlMxQCZTMUAql3BAKpdwQCqXrw" . "Aql6+AKpdwv///wABAAXgAQAE4AEABOABAAXg////AACqXcIAql6+AKpevACqXcIAql3C////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD" . "///8A////AP///wD///8A////AABu9N4AbvTeAG703gBu9N4AbvTe////AN6hAP/eoQD/3qEA/96hAP////8AWQCuy1kArstZAK7LWQCuy1kArssAivbiAIr24gCK9uI" . "AivbiAIr24v///wDqswD76rMA++qzAPvqswD7////AFoAt7haALe4WgC3uFoAt7haALe4AJr45ACa+OQAmvjkAJr45ACa+OT///8A8boA6PG6AOjxugDo8boA6P///wB" . "dAMOkXQDDpF0Aw6RdAMOkXQDDpACa+OQAmvjkAJr45ACa+OQAmvjk////APG6AOjxugDo8boA6PG6AOj///8AYQDUkWEA1JFhANSRYQDUkWEA1JEAmvjkAJr45ACa+OQ" . "AmvjkAJr45P///wDxugDo8boA6PG6AOjxugDo////AGEA1JFhANSRYQDUkWEA1JFhANSRBCAAAAQgAAAEIAAABCAAAAQgAAD//wAABCAAAAQgAAAEIAAABCAAAP//AAA" . "EIAAABCAAAAQgAAAEIAAABCAAAA==\" type=\"image/x-icon\">
<script type=\"text/javascript\"></script>
<meta name=\"format-detection\" content=\"telephone=no\">
<meta name=\"robots\" content=\"noindex,nofollow\">
[#scripts#]
</head>
<body class='setup-body'>
<div class='holder'>
<h1><table id='logo'>
    <tr>
        <td style='background-color:#F79600'> </td>
        <td style='background-color:#00B8EF'> </td>
        <td style='background-color:#CA005F'> </td>
    </tr>
    <tr>
        <td style='background-color:#4C9900'> </td>
        <td style='background-color:#050001'> </td>
        <td style='background-color:#4C9900'> </td>
    </tr>
    <tr>
        <td style='background-color:#CA005F'> </td>
        <td style='background-color:#00B8EF'> </td>
        <td style='background-color:#F79600'> </td>
    </tr>
    </table>bgERP - настройване на системата</h1>
<br>
<ul class='msg'>
    <li class='step' id='step1'><a class=menu id='astep1' href='" . $selfUri. "&amp;step=1'>1. Лиценз</a></li>
    <li class='step' id='step2'><a class=menu id='astep2' href='" . $selfUri. "&amp;step=2'>2. Обновяване</a></li>
    <li class='step' id='step3'><a class=menu id='astep3' href='" . $selfUri. "&amp;step=3'>3. Проверки</a></li>
    <li class='step' id='step4'><a class=menu id='astep4' href='" . $selfUri. "&amp;step=4'>4. Инициализиране</a></li>
    <li class='clear'></li>
</ul>

[#body#]
</div>
</body>
</html>";

// 1. Проверка дали имаме config файл. 
// 2. Проверка за връзка към MySQL
// 3. Проверка дали може да се чете/записва в UPLOADS, TMP, SBF
// 4. Проверка за необходимите модули на PHP
// 4. Провека за необходимите модули на Apache
// 6. Показване на прогрес барове
// 7. Стартиране на инсталацията

if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Стъпка 1: Лиценз
if($step == 1) {
    $texts['body'] = "<ul class='msg stats'><li>" .
        "\n<a href='{$nextUrl}'>&#9746; Ако приемате лиценза по-долу, може да продължите »</a></li></ul><br>";
    $texts['body'] .= "\n<div id='license'>" . 
        file_get_contents(__DIR__ . '/../license/gpl3.html') .
        "</div>";
    $texts['body'] .= "\n<br><ul class='msg stats'><li>" .
        "\n<a href='$nextUrl'>&#9746; Ако приемате лиценза по-горе, може да продължите »</a></li></ul>";
}


// Стъпка 2: Обновяване
if($step == 2) {
     
    $log = array();
    $checkUpdate = isset($_GET['update']) || isset($_GET['revert']);

    if(!defined(PRIVATE_GIT_BRANCH)) {
        define(PRIVATE_GIT_BRANCH, BGERP_GIT_BRANCH);
    }

    if(defined('EF_PRIVATE_PATH')) {
        $repos = array(EF_PRIVATE_PATH => PRIVATE_GIT_BRANCH, EF_APP_PATH => BGERP_GIT_BRANCH);
    } else {
        $repos = array(EF_APP_PATH => PRIVATE_GIT_BRANCH);
    }
    switch ($checkUpdate) {
        // Не се изисква сетъп
        case FALSE :
            $reposLastDate = "<table>";
            foreach($repos as $repoPath => $branch) {
                $reposLastDate .= "<tr><td align='right'>" . basename($repoPath).": </td><td style='font-weight: bold;'>" . gitLastCommitDate($repoPath, $log) . " (" . gitCurrentBranch($repoPath, $log) . ")</td></tr> ";
            }
            $reposLastDate .= "</table>";             
            // Показваме бутони за ъпдейтване и информация за състоянието
            $links[] = "inf|{$selfUrl}&amp;update|Проверка за по-нова версия »||";
            $links[] = "wrn|{$nextUrl}|Продължаване без обновяване »";
            break;
        case TRUE : 
            // Ако Git установи различие в бранчовете на локалното копие и зададената константа
            //  - превключва репозиторито в бранча зададен в константата
            
            // Ако GIT - а открие локално променени файлове, трябва да се изведат следните съобщения
            // 1. В системата има локално променени файлове. Възстановете ги. (прави Revert на променените файлове и остава на тази стъпка)
            // 2. Продължете, без да възстановявате променените файлове (отива на следваща стъпка)
        
            // Ако GIT-а открие по-нова версия на bgERP, трябва да се изведат следните съобщения:
            // 1. Има по-нова версия на bgERP. Обновете системата (прави pull на най-новото от мастер-бранч и остава на тази стъпка)
            // 2. Има по-нова версия на PRIVATE. Обновете системата (прави pull на най-новото от мастер-бранч и остава на тази стъпка)
            
            // Накрая, в зависимост от това дали има обновления
            // 3. Продължете, без да променяте системата (отива на следваща стъпка)
        
            // Ако нито едно от горните не е вярно, да се изведе:
            // 1. Имате най-новата версия на bgERP, може да продължите (отива на следваща стъпка)
            
            
            // Парамерти от Request, команди => репозиторита
            $update = $_GET['update'];
            $revert = $_GET['revert'];
        
            // Масив - лог за извършените действия
        
            $newVer = 0;
            $changed = 0;
    
            foreach($repos as $repoPath => $branch) {
                
                $repoName = basename($repoPath);
                
                // Превключваме репозиторито в зададения в конфигурацията бранч
                if (!gitSetBranch($repoPath, $log, $branch)) {
                    continue;
                }
                     
                // Ако имаме команда за revert на репозиторито - изпълняваме я
                if ($revert == $repoName) {
                    gitRevertRepo($repoPath, $log);
                }
                
                // Ако имаме команда за обновяване на репозитори - изпълняваме я
                if ($update == $repoName ||  $update == 'all') {
                    gitPullRepo($repoPath, $log, $branch);
                }
                 
                // Проверяваме за променени файлове в репозитори или за нова версия
                if (gitHasChanges($repoPath, $log)) {
                    $links[] = "wrn|{$selfUrl}&amp;revert={$repoName}|В <b>[{$repoName}]</b> има променени файлове. Възстановете ги »";
                    $changed++;
                } elseif (gitHasNewVersion($repoPath, $log, $branch)) {
                    $links[] = "new|{$selfUrl}&amp;update={$repoName}|Има по-нова версия на <b>[{$repoName}]</b>. Обновете я »";
                    $newVer++;
                }
            }
            if ($newVer > 1 && !$changed) {
                $links[] = "new|$selfUrl&amp;update=all|Обновете едновременно цялата система »";
            }
            
            if ($newVer || $changed) {
                $links[] = "wrn|{$nextUrl}|Продължете, без да променяте системата »";
            } else {
                $links[] = "inf|{$nextUrl}|Вие имате последната версия на <b>bgERP</b>, може да продължите »";
            }
            
            break;
    }
        
    $texts['body'] = linksToHtml($links);
            
    // Статистика за различните класове съобщения
    $stat = array();
    
    $texts['body'] .= logToHtml($log, $stat);
    $texts['body'] .= "<div style='font-size:14px;margin-top: 10px; clear:both;'> $reposLastDate</div>";
    
}


// Ако се намираме на стъпка 3: Проверки
if($step == 3) {
    
    $log = array();

    // Проверяваме дали имаме достъп за четене/запис до следните директории
    $log[] = 'h:Проверка и създаване на работните директории:';

    $folders = array(
        EF_SBF_PATH, // sbf root за приложението
        EF_TEMP_PATH, // временни файлове
        EF_UPLOADS_PATH // файлове на потребители
    );
        
    foreach($folders as $path) {
        if(!is_dir($path)) {
            if(!mkdir($path, 0777, TRUE)) {
                $log[] = "err:Не може да се създаде директорията: <b>`{$path}`</b>";
            } else {
                $log[] = "new:Създадена е директория: <b>`{$path}`</b>";
            }
        } else {
            if(!is_writable($path)) {
                $log[] = "err:Не може да се записва в директорията: <b>`{$path}`</b>";
            } else {
                $log[] = "inf:Налична директория: <b>`{$path}`</b>";
            }
        }
    }
    
    // Проверка дали локалните URL-та работят
    $log[] = 'h:Проверка дали локалните URL-та работят:';
    $res = @file_get_contents("{$localUrl}&step=testSelfUrl", FALSE, $context, 0, 32);

    if($res == $flagOK) {
        $log[] = "inf:Локалните URL-та се достъпват";
    } else {
        $log[] = "wrn:Локалните URL-та не се достъпват. Задайте стойност на константата BGERP_ABSOLUTE_HTTP_HOST или нагласете файла hosts, така че от PHP да се достъпват локалните URL";
    }

    // Необходими модули на PHP
    $log[] = 'h:Проверка за необходимите PHP модули:';
    $requiredPhpModules = array('imap', 'calendar', 'Core', 'ctype', 'date',
                                'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
                                'mbstring', 'mysqli', 'pcre', 'session', 'SimpleXML',
                                'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap', 'curl');
    
    $activePhpModules = get_loaded_extensions();
    
    foreach ($requiredPhpModules as $module) {
        if (in_array($module, $activePhpModules)){
            $log[] = "inf:Наличен PHP модул: <b>`$module`</b>";
        } else {
            $log[] = "err:Липсващ PHP модул: <b>`$module`</b>";
        }
    }


    // Необходими модули на Apache
    $log[] = 'h:Проверка за необходимите Apache модули:';

    $requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_rewrite', 'mod_deflate');
    
    
    if(function_exists('apache_get_modules')) {
        $activeApacheModules = apache_get_modules();
        
        foreach($requiredApacheModules as $module){
            if(in_array($module, $activeApacheModules)){
                $log[] = "inf:Наличен Apache модул: <b>`$module`</b>";
            } else {
                $log[] = "err:Липсващ Apache модул: <b>`$module`</b>";
            }
        }
    } else {
        $log[] = "inf:Apache не работи с mod-php";
    }
    

    // Проверка за налични програми
    if (!core_Os::isWindows()) {
        // Необходими програми на сървъра
        $log[] = 'h:Проверка за необходимите програми на сървъра:';
        
        $requiredPrograms = array('wget');
        
        foreach($requiredPrograms as $program){
            if (@exec('which ' . escapeshellcmd($program))){
                $log[] = "inf:Налична програма: <b>`$program`</b>";
            } else {
                $log[] = "wrn:Липсваща програма: <b>`$program`</b>";
            }
        }
        
        $log[] = 'h:Проверка за необходимите параметри на сървъра:';
        
        $minMemoryLimit = 1000000;
        $memoryLimit = core_Os::getMemoryLimit();
        
        if (isset($memoryLimit)) {
            if ($memoryLimit > $minMemoryLimit) {
                $log[] = "inf:Достатъчна оперативна памет";
            } else {
                if ($memoryLimit < (($minMemoryLimit/2) + ($minMemoryLimit/40))) {
                    $log[] = "err:Оперативната памет е под допустимите минимални стойности";
                } else {
                    $log[] = "wrn:Оперативната памет е под препоръчителните стойности";
                }
            }
        }
        
        $freeMemory = core_Os::getFreeMemory();
        if (isset($freeMemory)) {
            if ($freeMemory > ($minMemoryLimit/10)) {
                $log[] = "inf:Достатъчна свободна оперативна памет";
            } else {
                if ($memoryLimit < ($minMemoryLimit/20)) {
                    $log[] = "err:Свободната оперативната памет е под допустимите минимални стойности";
                } else {
                    $log[] = "wrn:Свободната оперативната памет е под препоръчителните стойности";
                }
            }
        }
        
        $minFreeSpace = 200000;
        $freeRootSpace = core_Os::getFreePathSpace(EF_ROOT_PATH);
        $freeSbfSpace = core_Os::getFreePathSpace(EF_SBF_PATH);
        $freeTempSpace = core_Os::getFreePathSpace(EF_TEMP_PATH);
        
        $freeSpace = min(array($freeRootSpace, $freeSbfSpace, $freeTempSpace));
        
        if (isset($freeSpace)) {
            if ($freeSpace > $minFreeSpace) {
                $log[] = "inf:Достатъчно свободно място на диска";
            } else {
                if ($freeSpace < $minFreeSpace/2) {
                    $log[] = "err:Свободното място в диска е под допустимите стойности";
                } else {
                    $log[] = "wrn:Свободното място в диска е под препоръчителните стойности";
                }
            }
        }
    }
    
    // Проверка за връзка с MySQL сървъра
    $log[] = 'h:Проверка на сървъра на базата данни:';
    if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS') && defined('EF_DB_NAME')) {

        $DB = new core_Db();
    	try {
    		$DB->connect(FALSE);
    		$log[] = "inf:Успешна връзка със сървъра: <b>`" . EF_DB_HOST ." `</b>";

    	} catch (core_Exception_Expect $e) {
    		$log[] = "err: " . $e->getMessage();
    		reportException($e);
    	}
    } else {
        $log[] = "err:Недефинирани константи за връзка със сървъра на базата данни";
    }
    

    // Ако не са дефинирани някой от константите EF_USERS_PASS_SALT, EF_SALT, EF_USERS_HASH_FACTOR ги дефинираме в bgerp.conf.php 
    $consts = array();
    
    // Име на приложението
    if(!defined('EF_APP_TITLE')) {
        $consts['EF_APP_TITLE'] = "bgERP";
    }
    
    // "Подправка" за кодиране на паролите
    if(!defined('EF_USERS_PASS_SALT')) {
        $consts['EF_USERS_PASS_SALT'] = getRandomString();
    }
    
    // Обща сол
    if(!defined('EF_SALT')) {
        $efSaltGenerated = $consts['EF_SALT'] = getRandomString();
    }
    
    // Препоръчителна стойност между 200 и 500
    if(!defined('EF_USERS_HASH_FACTOR')) {
        $consts['EF_USERS_HASH_FACTOR'] = 200;
    }   
       
    if (!empty($consts)) {
        $log[] = 'h:Задаваме константи :';
    }

    $paths = array(
        'index-tpl' => EF_APP_PATH . '/_docs/webroot/index.php',
        'index' => EF_INDEX_PATH . '/index.php',
        'index-cfg' => EF_INDEX_PATH . '/index.cfg.php',
        'config' => EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php',
        );
        
    if (file_exists($paths['config'])) {
        $resetCache = FALSE;
        $src = file_get_contents($paths['config']);
        // В конфигурационния файл задаваме незададените константи
        if (!empty($consts)) {
            foreach ($consts as $name => $value) {
                $src .= "\n";
                $src .= "// Добавено от setup.inc.php \n";
                $src .= "DEFINE('" . $name . "', '{$value}');\n";
                $constsLog .= ($constsLog) ? ', ' . $name : $name;
            }
            if (FALSE === @file_put_contents($paths['config'], $src)) {
                $log[] = "err: Недостатъчни права за добавяне в <b>`" . $paths['config'] . "`</b>";
            } else {
                $log[] = "new: Записани константи <b>{$constsLog}</b>";
                $resetCache = TRUE;
            }
        }
        if (defined('EF_DB_USER') && defined('EF_DB_PASS') && is_writable($paths['config'])) {
            if (EF_DB_USER == 'root' && EF_DB_PASS == 'USER_PASSWORD_FOR_DB') {
                $passwordDB = getRandomString();
                // Опитваме да сменим паролата на mysql-a
                @exec("mysqladmin -uroot -pUSER_PASSWORD_FOR_DB password {$passwordDB}", $output, $returnVar);
                if ($returnVar == 0) {
                    $src = str_replace('USER_PASSWORD_FOR_DB', $passwordDB, $src);
                    @file_put_contents($paths['config'], $src);
                    $log[] = "new: Паролата на root на mysql-a е сменена";
                    $resetCache = TRUE;
                } else {
                    $log[] = "wrn: Паролата на root на mysql-a не е сменена - използвате шаблонна парола, която се разпространява с имиджите на bgERP";
                }
            }
        }
        
        if ($resetCache) {
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }
    }
    $log[] = 'h:Изчисляване на контролни суми (MD5):';
            
    foreach ($paths as $key => $path) {
        if (file_exists($path)) {
            $src = file_get_contents($path);
            $hashs[$key] =  md5($src);
            $log[] = "inf:{$path} => <small>`" . $hashs[$key] . "`</small>";
        } else {
            $log[] = "err:Липсва файла <b>`" . $path . "`</b>";
        }
    }

    if(isset($hashs['index-tpl']) && isset($hashs['index']) && ($hashs['index-tpl'] != $hashs['index'])) {
        $log[] = "wrn:Файлът <b>`index.php`</b> се различава от шаблона";
    }


    // Статистика за различните класове съобщения
    $stat = array();

    $texts['body'] =  logToHtml($log, $stat);
    
    if($stat['err']) {
        $texts['body'] = "<ul class='msg stats'><li>" .
        "<a href='$selfUrl' class='err'>Отстранете грешките и опитайте пак...</a></li><ul><br>" .
        $texts['body'];
    } elseif($stat['wrn']) {
        $texts['body'] = "<ul class='msg stats'><li>" .
        "<a href='$nextUrl' class='wrn'>Има предупреждения. Ще продължите ли нататък? »</a></li><ul><br>" .
        $texts['body'];
    } else {
        $texts['body'] = "<ul class='msg stats'><li>" .
        "<a href='$nextUrl'>&#10003; Всичко е наред. Продължете с инициализирането »</a></li><ul><br>" .
        $texts['body'];
    }

    
}

// Ако се намираме на етапа на инициализиране, по-долу стартираме setup-а
if($step == 4) {
    $texts['body'] .= linksToHtml(array("new|{$selfUrl}&step=5| Стартиране инициализация »"));
    if (strtolower(BGERP_GIT_BRANCH) == 'dev') {
        $texts['body'] .= linksToHtml(array("new|{$selfUrl}&cancel| Стартирай bgERP »"));
    }
}

if($step == 5) {  
    $texts['body'] .= "<iframe src='{$selfUrl}&step=setup' name='init' id='init'></iframe>";
}

/**********************************
 * Setup на bgerp
 **********************************/
if ($step == 'setup') {

    set_time_limit(1000);

    $calibrate = 1000;
    $totalRecords = 209972; // 205 300
    $totalTables = 365; //366
    $percents = $persentsBase = $persentsLog = 0;
    $total = $totalTables*$calibrate + $totalRecords;

    // Пращаме стиловете
    echo ($texts['styles']);
 
    // Първоначално изтриване на Log-a
    file_put_contents(EF_SETUP_LOG_PATH, "");
    
    // Стартираме инициализацията
    contentFlush ("<h3 id='startHeader'>Стартиране на инициализацията ... <img src='{$localUrl}&step=start' width=0 height=0 ></h3>");

    // Пращаме javascript-a за smooth скрол-а
    contentFlush("<script>
    var mouseDown = 0;
    document.body.onmousedown = function() { 
        mouseDown = 1;
    }
    document.body.onmouseup = function() {
        mouseDown = 0;
    }
    function scroll()
     {
        var objDiv = document.getElementById('setupLog');
        if ((objDiv.scrollTop+objDiv.offsetHeight) < objDiv.scrollHeight && mouseDown == 0)
        {
           objDiv.scrollTop+=5;
           
        }
        
    }
    var handle=setInterval('scroll()', 4);
    </script>
    ");
    
    // Слагаме div за лог-а и шаблон за прогрес бар-а
    contentFlush("<div id='setupLog'></div>
                    <li id=\"progress\" >
                    <span id=\"progressTitle\">Прогрес:&nbsp</span>
                    <span id=\"progressIndicator\" style=\"padding-left:0px;\">
                    <span style=\"width: 0;\">&nbsp;</span>
                    </span>
                    <span id=\"progressPercents\">0 %</span>
                    </li>
                ");
    $cnt = 0;
    do {
        clearstatcache(EF_SETUP_LOG_PATH);
        $fTime = filemtime(EF_SETUP_LOG_PATH);
        clearstatcache(EF_SETUP_LOG_PATH);
        list($numTables, $numRows) = dataBaseStat(); 

        // От базата идват 80% от прогрес бара
        $percentsBase = round(($numRows + $calibrate * $numTables*(4/5))/$total, 2)*100;
        
        // Изчитаме лог-а
        $setupLog = @file_get_contents(EF_SETUP_LOG_PATH);

        if (!empty($setupLog) && $percentsLog < 20) {
            $percentsLog+=2;
        }
        
        $percents = $percentsBase + $percentsLog;
        if ($percents > 98) $percents = 98;
        $width = 4.5*$percents;
        
        // Прогресбар
        contentFlush("<script>
                        document.getElementById(\"progressIndicator\").style.paddingLeft=\"" . $width ."px\";
                        document.getElementById(\"progressPercents\").innerHTML = '" . $percents . " %';
                    </script>");
        
        // Изтриваме Log-a - ако има нещо в него
        if (!empty($setupLog)) {
            do {
                $res = @file_put_contents(EF_SETUP_LOG_PATH, "", LOCK_EX);
                if($res !== FALSE) break;
                usleep(1000);
            } while($i++ < 100);
        }
        
        $setupLog = preg_replace(array("/\r?\n/", "/\//"), array("\\n", "\/"), addslashes($setupLog));
        
        contentFlush("<script>
                        document.getElementById('setupLog').innerHTML += '" . $setupLog . "';
                </script>");
                
        sleep(2);
        Debug::log('Sleep 2 sec. in' . __CLASS__);
        
        $fTime2 = filemtime(EF_SETUP_LOG_PATH);
        if (($fTime2 - $fTime) > 0) {
            $logModified = TRUE;
        } else {
            $logModified = FALSE;
        }
        
        $cnt++;
        if ($cnt > 100) {
            // Ако инсталацията увисне
            wp($cnt, $numTables, $numRows, $percentsBase, $setupLog, strlen($setupLog), $logModified, $fTime2, $fTime);
        }
        
      } while (setupProcess() || !empty($setupLog) || $logModified);
    
    if ($percents < 100) {
        $percents = 100;
        $width = 4.5*$percents;
        // Прогресбар
        contentFlush("<script>
                        document.getElementById(\"progressIndicator\").style.paddingLeft=\"" . $width ."px\";
                        document.getElementById(\"progressPercents\").innerHTML = '" . $percents . " %';
                    </script>");
    } 
     
        
    
    sleep(1);
    Debug::log('Sleep 1 sec. in' . __CLASS__);

    contentFlush("<h3 id='success'>Инициализирането завърши успешно!</h3>");
    
    $l = linksToHtml(array("new|{$appUri}|Стартиране bgERP »|_parent")); 
    $l = preg_replace(array("/\r?\n/", "/\//"), array("\\n", "\/"), addslashes($l));
    contentFlush("<script>
                        document.getElementById('startHeader').innerHTML = '" .
                         $l . "';
                </script>");
    // Спираме и smooth скрол-а и чистим setup cookie
    sleep(1);
    Debug::log('Sleep 1 sec. in' . __CLASS__);

    contentFlush("<script>
                        clearInterval(handle);
                        document.cookie = 'setup=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                </script>");
    setupUnlock();

    exit;
}

/**********************************
 * Setup на bgerp самостоятелно инсталиране
 **********************************/
if($step == 'start') {
    // Затваряме връзката с извикване
    
    // Следващият ред генерира notice,
    // но без него file_get_contents забива, ако трябва да връща повече от 0 байта
    @ob_end_clean();

    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    ob_start();
    echo $flagOK;
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    ob_end_clean();

    GLOBAL $setupFlag;

    $setupFlag = TRUE;
    // Създаваме празен Log файл
    file_put_contents(EF_SETUP_LOG_PATH, '');
    
    // Локал за функции като basename, fgetcsv
    setlocale(LC_ALL, 'en_US.UTF8');
    
    $ef = new core_Setup();
    try {
        try {
            $res = $ef->install();
            file_put_contents(EF_SETUP_LOG_PATH, 'Starting bgERP initialization...' . $res);
        } catch (core_exception_Expect $e) {
            file_put_contents(EF_SETUP_LOG_PATH, $res . "ERROR: " . $e->getMessage());
            reportException($e);
        }
    } catch (Exception $e) {
        file_put_contents(EF_SETUP_LOG_PATH, $e->getMessage());
        reportException($e);
    }
    
    $Packs = cls::get('core_Packs');

    $Packs->setupPack("bgerp");

    setupUnlock();

    shutdown();
}

// Субституираме в лейаута
foreach($texts as $place => $str) {
    $layout = str_replace("[#{$place}#]", $str, $layout);
}

if ($efSaltGenerated) {
    $setKeyFortune = setupKey($efSaltGenerated);
    $layout = str_replace($_GET['SetupKey'], $setKeyFortune, $layout);
}

echo $layout;
ob_flush();

die;

//=======================
// Декларации функции
//=======================

/**
 * Преобразува лог от вътрешни операции към HTML
 */
function logToHtml($log, &$stat)
{
    foreach($log as $line) {
        list($class, $text) = explode(':', $line, 2);
        $html .= "\n<div class='{$class}'>{$text}</div>";
        $stat[$class]++;
    }

    return $html;
}


/**
 * Превръща масив от форматирани стрингове в линкве към следващи действия
 * Стринговете имат следния формат: "class|url|message|target";
 * Връща string с html
 */
function linksToHtml($links)
{
    foreach($links as $l) {
        list($class, $url, $text, $target, $info) = array_pad(explode('|', $l, 5), 5, '');
        $html .= "\n<ul class='msg stats'><li>" .
            "\n<a href='{$url}' class='{$class}' target='{$target}'>{$text}</a>\n{$info}</li></ul><br>";
    }

    return $html;
}

/**
 * Изпълнява git команда и връща стрингoвия резултат
 */
function gitExec($cmd, &$output)
{
    @exec(BGERP_GIT_PATH . " {$cmd}", $output, $returnVar);
    
    return ($returnVar == 0);    
}


/**
 * Връща датата на последния комит на дадено репозитори
 */
function gitLastCommitDate($repoPath, &$log)
{

    $command = " --git-dir=\"{$repoPath}/.git\" log -1 --pretty=format:'%ci'";

    $repoName = basename($repoPath);

    // Първият ред съдържа резултата
    if (gitExec($command, $res)) {

        return trim(substr(trim($res[0], "'"), 0, strpos($res[0], " +")));
    }

    return FALSE;
}


/**
 * Връща текущият бранч на репозиторито или FALSE ако не е сетнат
 */
function gitCurrentBranch($repoPath, &$log)
{
    
    $command = " --git-dir=\"{$repoPath}/.git\" rev-parse --abbrev-ref HEAD 2>&1";

    $repoName = basename($repoPath);

    // Първият ред съдържа резултата
    if (gitExec($command, $res)) {
        
        return trim($res[0]);
    }
    
    return FALSE;
}


/**
 * Сетва репозиторито в зададен бранч. Ако не е зададен го взима от конфигурацията
 */
function gitSetBranch($repoPath, &$log, $branch = NULL)
{

    $repoName = basename($repoPath);

    $currentBranch = gitCurrentBranch($repoPath, $log);

    if(isset($branch)) {
        if ($currentBranch == $branch) {
            return TRUE;
        }
        $requiredBranch = $branch;
    } else {
        $requiredBranch = BGERP_GIT_BRANCH;
    }
    
    $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin +{$requiredBranch}:{$requiredBranch} 2>&1";
    
    $commandCheckOut = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" checkout {$requiredBranch} 2>&1";
 
    if (!gitExec($commandFetch, $arrRes)) {
        foreach ($arrRes as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при превключване в {$requiredBranch} fetch:" . $val):"";
        }
        
        return FALSE;
    } else {
        if (!gitExec($commandCheckOut, $arrRes)) {
            foreach ($arrRes as $val) {
                $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при превключване в {$requiredBranch} checkOut:" . $val):"";
            }
            
            return FALSE;
        } else {
            // Ако и двете команди са успешни значи всичко е ОК
            $log[] = "info: [<b>$repoName</b>] превключен {$requiredBranch} бранч.";
            
            return TRUE;
        }
        
    }

//    $log[] = "err: [<b>$repoName</b>] Грешка при превключване в бранч {$requiredBranch}";
    
    return FALSE;
}


/**
 * Дали има по-нова версия на това репозитори в зададения бранч?
 */
function gitHasNewVersion($repoPath, &$log, $branch = BGERP_GIT_BRANCH)
{
    $repoName = basename($repoPath);
    
    // Команда за SHA1 на локалния бранч 
    $command = " --git-dir=\"{$repoPath}/.git\" rev-parse " . $branch;

    if (!gitExec($command, $arrResLocal)) {
        foreach ($arrResLocal as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при rev-parse : " . $val):"";
        }
        
        return FALSE;
    }
  
    // Команда за SHA1 на отдалечения бранч
    $command = " --git-dir=\"{$repoPath}/.git\" ls-remote origin " . $branch;

    if (!gitExec($command, $arrResRemote)) {
        foreach ($arrResRemote as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при ls-remote origin : " . $val):"";
        }
        
        return FALSE;
    }
    foreach ($arrResRemote as $val) {
    	if (strpos($val, "refs/heads") === TRUE);
    	$refsHeads = $val;
    }
    $arrResRemote = preg_split('/\s+/', $refsHeads);
    
    //print_r($arrResRemote); die;
    
    if($arrResRemote[0] !== $arrResLocal[0]) {
        $log[] = "new:[<b>$repoName</b>] Има нова версия.";
        
        return TRUE;
    }
        
    
    return FALSE;
}



/**
 * Дали има промени в локалното копие?
 */
function gitHasChanges($repoPath, &$log)
{

    $repoName = basename($repoPath);
    
    $command = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" status -s 2>&1";

    if (!gitExec($command, $arrRes)) {
        foreach ($arrRes as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при status: " . $val):"";
        }
        
        return FALSE;
    }
  
    // $states = array("M" => "Модифициран", "??"=>"Непознат", "A"=>"Добавен");
    $statesWarning = array("M" => "Модифициран", "A"=>"Добавен", "D"=>"Изтрит");
    $statesInfo = array("??"=>"Непознат");
    $wrn = FALSE;
    if (!empty($arrRes)) {
        foreach ($arrRes as $row) {
            $row = trim($row);
            $arr = explode(" ", $row);
            if (isset($statesWarning[$arr[0]])) {
                $log[] = "wrn:<b>[{$repoName}]</b> " . $statesWarning[$arr[0]] . " файл: <b>`{$arr[1]}`</b>";
                $wrn = TRUE;
            }
            if (isset($statesInfo[$arr[0]])) {
                $log[] = "inf:<b>[{$repoName}]</b> " . $statesInfo[$arr[0]] . " файл: <b>`{$arr[1]}`</b>";
            }
        }
        
        return $wrn;
    }
    
    return FALSE;
}


/**
 * Синхронизира с последната версия на зададения бранч
 */
function gitPullRepo($repoPath, &$log, $branch = BGERP_GIT_BRANCH)
{
    
    $repoName = basename($repoPath);
    
    $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin " . $branch . " 2>&1";

    $commandMerge = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge FETCH_HEAD";
    
    // За по голяма прецизност е добре да се пусне и git fetch
    
    if (!gitExec($commandFetch, $arrResFetch)) {
        foreach ($arrResFetch as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при fetch: " . $val):"";
        }
        
        return FALSE;
    }
  
    if (!gitExec($commandMerge, $arrResMerge)) {
        foreach ($arrResMerge as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при merge origin/" . $branch.": " . $val):"";
        }
        
        return FALSE;
    }
    
    $log[] = "new:<b>[{$repoName}]</b> е обновено.";
            
    return TRUE;
}



/**
 * Унищожава локалните промени, на файловете, включени в репозиторито
 */
function gitRevertRepo($repoPath, &$log)
{
    
    $repoName = basename($repoPath);
    
    $command = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" reset --hard 2>&1";
    
    if (!gitExec($command, $arrRes)) {
        foreach ($arrRes as $val) {
            $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при reset --hard :" . $val):"";
        }
        
        return FALSE;
    }

    $log[] = "msg:Репозиторито <b>[{$repoName}]</b> е възстановено";
    
    return TRUE;
}


/**
 * Праща съдържание към клиента
 */
function contentFlush ($content)
{
    static $started = 0;
    
    
    ob_clean();
    ob_start();
    
    if ($started == 0) {
        echo str_repeat(" ", 1024), "\n";
        echo ("<!DOCTYPE html>");
        $started++;
    }
    
    echo($content);

    ob_flush();
    ob_end_flush();
    flush();
    
}

/**
 * Начало на режим на Setup на bgERP
 * - сетва семафора
 * 
 * @return boolean
 */
function setupLock()
{
    if (!is_dir(EF_TEMP_PATH)) {
        mkdir(EF_TEMP_PATH, 0777, TRUE);
    }
    return touch(EF_TEMP_PATH . "/setupLock.tmp");
}

/**
 * Край на режим на Setup на bgERP
 * 
 *
 */
function setupUnlock()
{

    return @unlink(EF_TEMP_PATH . "/setupLock.tmp");
}
    
/**
 * Дали bgERP е в сетъп режим
 * 
 * @return boolean
 */
function setupProcess()
{
    if (@file_exists(EF_TEMP_PATH . "/setupLock.tmp")) {
        return TRUE;
//         clearstatcache(EF_TEMP_PATH . "/setupLock.tmp");
//         if (time() - filemtime(EF_TEMP_PATH . "/setupLock.tmp") > SETUP_LOCK_PERIOD) {
//             setupUnlock();
            
//             return FALSE;
//        }
    } else {
        
        return FALSE;   
    }
    
    return TRUE;
}
    

/**
 * Проверява валидност на сетъп ключ
 * 
 * @return boolean
 */
function setupKeyValid()
{
    // При празна база връща валиден setup ключ
    $DB = new core_Db();
    
    try {
        $DB->connect(FALSE);
    } catch (core_exception_Expect $e) {

        return TRUE;
    }
    
    if ($DB->databaseEmpty() && !setupProcess()) {
        return TRUE;
    }
    
    // Ако има setup cookie и има пуснат сетъп процес връща валиден ключ
    if (isset($_COOKIE['setup']) && setupProcess()) {
        return TRUE;
    }

    // Ако сетъп-а е стартиран от локален хост или инсталатор 
    // Определяме масива с локалните IP-та
    $localIpArr = array('::1', '127.0.0.1');
    $isLocal = in_array($_SERVER['REMOTE_ADDR'], $localIpArr);
    $key = $_GET['SetupKey'];
    if ($key == BGERP_SETUP_KEY && $isLocal ) {
        return TRUE;
    }
    
    return $_GET['SetupKey'] == setupKey();
}


/**
 * Връща броя на таблиците и редовете в базата
 * 
 * @return array
 */
function dataBaseStat()
{
    $DB = new core_Db();

    $recordsRes = $DB->query("SELECT SUM(TABLE_ROWS) AS RECS
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = '" . $DB->escape($DB->dbName) ."'");
    $rows = $DB->fetchObject($recordsRes);
    
    $tablesRes = $DB->query("SELECT COUNT(*) TABLES FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '". $DB->escape($DB->dbName) ."';");
    $tables = $DB->fetchObject($tablesRes);
    
    return array($tables->TABLES, $rows->RECS);
}


function getRandomString($length = 15)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}


/**
 * Добавя параметър в стринг представящ URL
 */
function addParams($url, $newParams)
{
    $purl = parse_url($url);
    
    if (!$purl) return FALSE;
    
    $params = array();
    
    if (!empty($purl["query"])) {
        parse_str($purl["query"], $params);
    }
    
    // Добавяме новите параметри
    foreach ($newParams as $key => $value) {
        $params[$key] = $value;
    }
    
    $purl["query"] = "";
    
    foreach ($params as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $purl["query"] .= ($purl["query"] ? '&' : '') . "{$name}[{$key}]=" . urlencode($v);
            }
        } else {
            $purl["query"] .= ($purl["query"] ? '&' : '') . "{$name}=" . urlencode($value);
        }
    }

    $res = "";
    
    if (isset($purl["scheme"])) {
        $res .= $purl["scheme"] . "://";
    }
    
    if (isset($purl["user"])) {
        $res .= $purl["user"] . ':';
        $res .= $purl["pass"];
        $res .= "@";
    }
    $res .= $purl["host"];
    
    if ($purl["port"]) {
        $res .= ":" . $purl["port"];
    }
    
    $res .= $purl["path"];
    
    if (isset($purl["query"])) {
        $res .= "?" . $purl["query"];
    }
    
    if (isset($purl["fragment"])) {
        $res .= "#" . $purl["fragment"];
    }

    return $res;
}
