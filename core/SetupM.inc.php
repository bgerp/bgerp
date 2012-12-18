<?php


/**
 * Скрипт 'SetupM.inc.php' -  Инсталиране на bgERP
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link 
 */
 

// 1. Проверка дали имаме config файл. 
// 2. Проверка за връзка към MySQL
// 3. Проверка дали може да се чете/записва в UPLOADS, TMP, SBF
// 4. Проверка за необходимите модули на PHP
// 4. Провека за необходимите модули на Apache
// 6. Показване на прогрес барове
// 7. Стартиране на инсталацията
ob_end_clean();
header("Content-Type: text/html; charset=UTF-8");

// Стилове
$styles = "
<style type=\"text/css\">
body {
    background-color:#119; 
    color:white;
    font-family:Verdana,Arial;
}

a {
    color:#ffff33;
}

a:hover {
    text-decoration:none;
}

#license {
    text-align:justify;
}

.msg {
    width:100%;
}


.msg th {
    padding:10px;
    font-weight:normal;
    border:solid 2px white;
    background-color:#0000cc;
}

h1 {
    font-size:2.2em;
    margin:0px;
    text-shadow: 2px 4px 3px rgba(0,0,0,0.3);
}

#logo {
    box-shadow: 2px 4px 3px rgba(0,0,0,0.3);
}


#step[#currentStep#] {
    color:black !important;
    background-color:#ffcc00 !important;
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
    color:red !important;
    text-decoration:blink;
    line-height:1.5em;
}

.inf {
    line-height:1.5em;
}

.new {
    line-height:1.5em;
    color:#33ff00 !important;
}

.wrn, .wrn b {
    color:#ff9900 !important;
    line-height:1.5em;
}

#progress {
	list-style-type: none;
	background-color: #119;
	position:absolute;
	left: 0px;
	top: 95px;
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
	madding: 0;
	width: 790px;
	height: 610px;
	overflow: hidden;
}

#setupLog {
	position:absolute;
	left: 0px;
	top: 180px;
	1border-top: #000 1px solid;
	width: 790;
	height: 425;
	overflow:auto;
	background-color: #119;
}

#success {
	position:absolute;
	top:110px;
	padding-left:190px;'
}
</style>
";

// Лейаута на HTML страницата
$layout = 
"<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<title>bgERP - настройване на системата (стъпка [#currentStep#])</title>
" . $styles . "


<link  rel=\"shortcut icon\" 
href=\"data:image/icon;base64,AAABAAEAEBAAAAAAAABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAABaALPDWgCzw1oAs8N" . "aALPDWgCzw////wDjqQD/46kA/+OpAP/jqQD/////AAB79eAAe/XgAHv14AB79eAAe/XgWgCzw1oAs8NaALPDWgCzw1oAs8P///8A46kA/+OpAP/jqQD/46kA/////wA" . "Ae/XgAHv14AB79eAAe/XgAHv14FwAvLBcALywXAC8sFwAvLBcALyw////AO+4AO3vuADt77gA7e+4AO3///8AAJb34wCW9+MAlvfjAJb34wCW9+NfAMqcXwDKnF8Aypx" . "fAMqcXwDKnP///wDxugDo8boA6PG6AOjxugDo////AACa+OQAmvjkAJr45ACa+OQAmvjkXwDKnF8AypxfAMqcXwDKnF8Aypz///8A8sUAzfPIAMXzyADF8sQAzv///wA" . "ApvjLAK35vgCu+b4AqvnEAKr5xP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wAAfS/MAH0vzAB9L8w" . "AfS/MAH0vzP///wABAAXgAQAF4AEABeABAAXg////AAB9L8wAfS/MAH0vzAB9L8wAfS/MAIs9yACLPcgAiz3IAIs9yACLPcj///8AAQAF4AEABeABAAXgAQAF4P///wA" . "Aiz3IAIs9yACLPcgAiz3IAIs9yACZTMUAmUzFAJlMxQCZTMUAmUzF////AAEABeABAAXgAQAF4AEABeD///8AAJlMxQCZTMUAmUzFAJlMxQCZTMUAql3BAKpdwQCqXrw" . "Aql6+AKpdwv///wABAAXgAQAE4AEABOABAAXg////AACqXcIAql6+AKpevACqXcIAql3C////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD" . "///8A////AP///wD///8A////AABu9N4AbvTeAG703gBu9N4AbvTe////AN6hAP/eoQD/3qEA/96hAP////8AWQCuy1kArstZAK7LWQCuy1kArssAivbiAIr24gCK9uI" . "AivbiAIr24v///wDqswD76rMA++qzAPvqswD7////AFoAt7haALe4WgC3uFoAt7haALe4AJr45ACa+OQAmvjkAJr45ACa+OT///8A8boA6PG6AOjxugDo8boA6P///wB" . "dAMOkXQDDpF0Aw6RdAMOkXQDDpACa+OQAmvjkAJr45ACa+OQAmvjk////APG6AOjxugDo8boA6PG6AOj///8AYQDUkWEA1JFhANSRYQDUkWEA1JEAmvjkAJr45ACa+OQ" . "AmvjkAJr45P///wDxugDo8boA6PG6AOjxugDo////AGEA1JFhANSRYQDUkWEA1JFhANSRBCAAAAQgAAAEIAAABCAAAAQgAAD//wAABCAAAAQgAAAEIAAABCAAAP//AAA" . "EIAAABCAAAAQgAAAEIAAABCAAAA==\" type=\"image/x-icon\">
<script type=\"text/javascript\"></script>
<meta name=\"format-detection\" content=\"telephone=no\">
<meta name=\"robots\" content=\"noindex,nofollow\">
<script>
" .
'/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */
var hexcase=0;function hex_md5(a){return rstr2hex(rstr_md5(str2rstr_utf8(a)))}function hex_hmac_md5(a,b){return rstr2hex(rstr_hmac_md5(str2rstr_utf8(a),str2rstr_utf8(b)))}function md5_vm_test(){return hex_md5("abc").toLowerCase()=="900150983cd24fb0d6963f7d28e17f72"}function rstr_md5(a){return binl2rstr(binl_md5(rstr2binl(a),a.length*8))}function rstr_hmac_md5(c,f){var e=rstr2binl(c);if(e.length>16){e=binl_md5(e,c.length*8)}var a=Array(16),d=Array(16);for(var b=0;b<16;b++){a[b]=e[b]^909522486;d[b]=e[b]^1549556828}var g=binl_md5(a.concat(rstr2binl(f)),512+f.length*8);return binl2rstr(binl_md5(d.concat(g),512+128))}function rstr2hex(c){try{hexcase}catch(g){hexcase=0}var f=hexcase?"0123456789ABCDEF":"0123456789abcdef";var b="";var a;for(var d=0;d<c.length;d++){a=c.charCodeAt(d);b+=f.charAt((a>>>4)&15)+f.charAt(a&15)}return b}function str2rstr_utf8(c){var b="";var d=-1;var a,e;while(++d<c.length){a=c.charCodeAt(d);e=d+1<c.length?c.charCodeAt(d+1):0;if(55296<=a&&a<=56319&&56320<=e&&e<=57343){a=65536+((a&1023)<<10)+(e&1023);d++}if(a<=127){b+=String.fromCharCode(a)}else{if(a<=2047){b+=String.fromCharCode(192|((a>>>6)&31),128|(a&63))}else{if(a<=65535){b+=String.fromCharCode(224|((a>>>12)&15),128|((a>>>6)&63),128|(a&63))}else{if(a<=2097151){b+=String.fromCharCode(240|((a>>>18)&7),128|((a>>>12)&63),128|((a>>>6)&63),128|(a&63))}}}}}return b}function rstr2binl(b){var a=Array(b.length>>2);for(var c=0;c<a.length;c++){a[c]=0}for(var c=0;c<b.length*8;c+=8){a[c>>5]|=(b.charCodeAt(c/8)&255)<<(c%32)}return a}function binl2rstr(b){var a="";for(var c=0;c<b.length*32;c+=8){a+=String.fromCharCode((b[c>>5]>>>(c%32))&255)}return a}function binl_md5(p,k){p[k>>5]|=128<<((k)%32);p[(((k+64)>>>9)<<4)+14]=k;var o=1732584193;var n=-271733879;var m=-1732584194;var l=271733878;for(var g=0;g<p.length;g+=16){var j=o;var h=n;var f=m;var e=l;o=md5_ff(o,n,m,l,p[g+0],7,-680876936);l=md5_ff(l,o,n,m,p[g+1],12,-389564586);m=md5_ff(m,l,o,n,p[g+2],17,606105819);n=md5_ff(n,m,l,o,p[g+3],22,-1044525330);o=md5_ff(o,n,m,l,p[g+4],7,-176418897);l=md5_ff(l,o,n,m,p[g+5],12,1200080426);m=md5_ff(m,l,o,n,p[g+6],17,-1473231341);n=md5_ff(n,m,l,o,p[g+7],22,-45705983);o=md5_ff(o,n,m,l,p[g+8],7,1770035416);l=md5_ff(l,o,n,m,p[g+9],12,-1958414417);m=md5_ff(m,l,o,n,p[g+10],17,-42063);n=md5_ff(n,m,l,o,p[g+11],22,-1990404162);o=md5_ff(o,n,m,l,p[g+12],7,1804603682);l=md5_ff(l,o,n,m,p[g+13],12,-40341101);m=md5_ff(m,l,o,n,p[g+14],17,-1502002290);n=md5_ff(n,m,l,o,p[g+15],22,1236535329);o=md5_gg(o,n,m,l,p[g+1],5,-165796510);l=md5_gg(l,o,n,m,p[g+6],9,-1069501632);m=md5_gg(m,l,o,n,p[g+11],14,643717713);n=md5_gg(n,m,l,o,p[g+0],20,-373897302);o=md5_gg(o,n,m,l,p[g+5],5,-701558691);l=md5_gg(l,o,n,m,p[g+10],9,38016083);m=md5_gg(m,l,o,n,p[g+15],14,-660478335);n=md5_gg(n,m,l,o,p[g+4],20,-405537848);o=md5_gg(o,n,m,l,p[g+9],5,568446438);l=md5_gg(l,o,n,m,p[g+14],9,-1019803690);m=md5_gg(m,l,o,n,p[g+3],14,-187363961);n=md5_gg(n,m,l,o,p[g+8],20,1163531501);o=md5_gg(o,n,m,l,p[g+13],5,-1444681467);l=md5_gg(l,o,n,m,p[g+2],9,-51403784);m=md5_gg(m,l,o,n,p[g+7],14,1735328473);n=md5_gg(n,m,l,o,p[g+12],20,-1926607734);o=md5_hh(o,n,m,l,p[g+5],4,-378558);l=md5_hh(l,o,n,m,p[g+8],11,-2022574463);m=md5_hh(m,l,o,n,p[g+11],16,1839030562);n=md5_hh(n,m,l,o,p[g+14],23,-35309556);o=md5_hh(o,n,m,l,p[g+1],4,-1530992060);l=md5_hh(l,o,n,m,p[g+4],11,1272893353);m=md5_hh(m,l,o,n,p[g+7],16,-155497632);n=md5_hh(n,m,l,o,p[g+10],23,-1094730640);o=md5_hh(o,n,m,l,p[g+13],4,681279174);l=md5_hh(l,o,n,m,p[g+0],11,-358537222);m=md5_hh(m,l,o,n,p[g+3],16,-722521979);n=md5_hh(n,m,l,o,p[g+6],23,76029189);o=md5_hh(o,n,m,l,p[g+9],4,-640364487);l=md5_hh(l,o,n,m,p[g+12],11,-421815835);m=md5_hh(m,l,o,n,p[g+15],16,530742520);n=md5_hh(n,m,l,o,p[g+2],23,-995338651);o=md5_ii(o,n,m,l,p[g+0],6,-198630844);l=md5_ii(l,o,n,m,p[g+7],10,1126891415);m=md5_ii(m,l,o,n,p[g+14],15,-1416354905);n=md5_ii(n,m,l,o,p[g+5],21,-57434055);o=md5_ii(o,n,m,l,p[g+12],6,1700485571);l=md5_ii(l,o,n,m,p[g+3],10,-1894986606);m=md5_ii(m,l,o,n,p[g+10],15,-1051523);n=md5_ii(n,m,l,o,p[g+1],21,-2054922799);o=md5_ii(o,n,m,l,p[g+8],6,1873313359);l=md5_ii(l,o,n,m,p[g+15],10,-30611744);m=md5_ii(m,l,o,n,p[g+6],15,-1560198380);n=md5_ii(n,m,l,o,p[g+13],21,1309151649);o=md5_ii(o,n,m,l,p[g+4],6,-145523070);l=md5_ii(l,o,n,m,p[g+11],10,-1120210379);m=md5_ii(m,l,o,n,p[g+2],15,718787259);n=md5_ii(n,m,l,o,p[g+9],21,-343485551);o=safe_add(o,j);n=safe_add(n,h);m=safe_add(m,f);l=safe_add(l,e)}return Array(o,n,m,l)}function md5_cmn(h,e,d,c,g,f){return safe_add(bit_rol(safe_add(safe_add(e,h),safe_add(c,f)),g),d)}function md5_ff(g,f,k,j,e,i,h){return md5_cmn((f&k)|((~f)&j),g,f,e,i,h)}function md5_gg(g,f,k,j,e,i,h){return md5_cmn((f&j)|(k&(~j)),g,f,e,i,h)}function md5_hh(g,f,k,j,e,i,h){return md5_cmn(f^k^j,g,f,e,i,h)}function md5_ii(g,f,k,j,e,i,h){return md5_cmn(k^(f|(~j)),g,f,e,i,h)}function safe_add(a,d){var c=(a&65535)+(d&65535);var b=(a>>16)+(d>>16)+(c>>16);return(b<<16)|(c&65535)}function bit_rol(a,b){return(a<<b)|(a>>>(32-b))};' .
"
</script>
</head>
<body>
<div style='width:800px;padding:20px;display:table;'>
<table>
<tr>
    <td style='vertical-align:middle;width:32px;'>
    <table id='logo'>
    <tr>
        <td style='background-color:#F79600'></td>
        <td style='background-color:#00B8EF'></td>
        <td style='background-color:#CA005F'></td>
    </tr>
    <tr>
        <td style='background-color:#4C9900'></td>
        <td style='background-color:#050001'></td>
        <td style='background-color:#4C9900'></td>
    </tr>
    <tr>
        <td style='background-color:#CA005F'></td>
        <td style='background-color:#00B8EF'></td>
        <td style='background-color:#F79600'></td>
    </tr>
    </table>
    </td>
    <td nowrap><h1>bgERP - настройване на системата</h1></td>
</tr>
</table>
<br>
<table class='msg'>
<tr>
    <th id='step1'>1. Лиценз</th>
    <th id='step2'>2. Обновяване</th>
    <th id='step3'>3. Проверки</th>
    <th id='step4'>4. Инициализиране</th>
</tr>
</table>
<br>
[#body#]
</div>
</body>
</html>";

if (defined('BGERP_SETUP_KEY')) {
	if($_GET['SetupKey'] != BGERP_SETUP_KEY) {
	    halt('Wrong Setup Key!');
	}
}

// Определяме масива с локалните IP-та
$localIpArr = array('::1', '127.0.0.1');
session_name('SID');
session_start();
if($ip = $_SESSION[EF_APP_NAME . 'admin_ip']) {
    $localIpArr += array($ip);
}

// Оторизация. Засега само проверява дали е от локалния хост
$isLocal = in_array($_SERVER['REMOTE_ADDR'], $localIpArr);

if(!$isLocal) {
    halt("Non-authorized IP for Setup (" . $_SERVER['REMOTE_ADDR'] . ")");
}

// На коя стъпка се намираме в момента?
$step = $_GET['step'] ? $_GET['step'] : 1;
$texts['currentStep'] = $step;

// Собственото URL
$selfUrl = "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}";
list($selfUrl,) = explode('&', $selfUrl);

// URL на следващата стъпка
$nextUrl = $selfUrl . '&amp;step=' . ($step+1);
$selfUrl .= '&amp;step=' . $step;

for($i = 1; $i <= 4; $i++) {
    $nextUrl = str_replace('&step=' . $i, '', $nextUrl);
}
$nextUrl = "{$nextUrl}&amp;step=" . ($step+1);

// Ако се намираме на стъпка 1: Лиценз
if($step == 1) {
    $texts['body'] = "<table width='100%' class='msg'><tr><th>" .
        "\n<a href='{$nextUrl}'>&#9746; Ако приемате лиценза по-долу, може да продължите »</a></th></tr></table><br>";
    $texts['body'] .= "\n<div id='license'>" . 
        file_get_contents(__DIR__ . '/../license/gpl3.html') .
        "</div>";
    $texts['body'] .= "\n<br><table width='100%' class='msg'><tr><th>" .
        "\n<a href='$nextUrl'>&#9746; Ако приемате лиценза по-горе, може да продължите »</a></th></tr></table>";
}

// Стъпка 2: Обновяване
if($step == 2) {

 
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
    
    if(defined('EF_PRIVATE_PATH')) {
        $repos = array(EF_PRIVATE_PATH, EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH);
    } else {
        $repos = array(EF_APP_PATH, EF_EF_PATH, EF_VENDORS_PATH);
    }
    
    // Парамерти от Request, команди => репозиторита
    $update = $_GET['update'];
    $revert = $_GET['revert'];

    // Масив - лог за извършените действия
    $log = array();

    $newVer = 0;
    $changed = 0;
    $redirect = FALSE;
    foreach($repos as $repoPath) {
        
        $repoName = basename($repoPath);
        
        // Ако имаме команда за revert на репозиторито - изпълняваме я
        if($revert == $repoName) {
            gitRevertRepo($repoPath, $log);
        }
        
        // Ако имаме команда за обновяване на репозитори - изпълняваме я
        if($update == $repoName ||  $update == 'all') {
            gitPullRepo($repoPath, $log);
        }
         
        // Проверяваме за променени файлове в репозитори или за нова версия
        if(gitHasChanges($repoPath, $log)) {
            $links[] = "wrn|{$selfUrl}&amp;revert={$repoName}|В <b>[{$repoName}]</b> има променени файлове. Възстановете ги »";
            $changed++;
        } elseif(gitHasNewVersion($repoPath, $log)) {
            $links[] = "new|{$selfUrl}&amp;update={$repoName}|Има по-нова версия на <b>[{$repoName}]</b>. Обновете я »";
            $newVer++;
        }
    }
    
    if($newVer > 1 && !$changed) {
        $links[] = "new|$selfUrl&amp;update=all|Обновете едновременно цялата система »";
    }
    
    if($newVer || $changed) {
        $links[] = "wrn|{$nextUrl}|Продължете, без да променяте системата »";
    } else {
        $links[] = "inf|{$nextUrl}|Вие имате последната версия на <b>bgERP</b>, може да продължите »";
    }
    
    $texts['body'] = linksToHtml($links);
        
    // Статистика за различните класове съобщения
    $stat = array();

    $texts['body'] .= logToHtml($log, $stat);

}


// Ако се намираме на стъпка 2: Проверки
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
    

    // Необходими модули на PHP
    $log[] = 'h:Проверка за необходимите PHP модули:';
    $requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
                                'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
                                'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
                                'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');
    
    $activePhpModules = get_loaded_extensions();
    
    foreach($requiredPhpModules as $module) {
        if(in_array($module, $activePhpModules)){
            $log[] = "inf:Наличен PHP модул: <b>`$module`</b>";
        } else {
            $log[] = "err:Липсващ PHP модул: <b>`$module`</b>";
        }
    }


    // Необходими модули на Apache
    $log[] = 'h:Проверка за необходимите Apache модули:';

    $requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');
    
    $activeApacheModules = apache_get_modules();
    
    foreach($requiredApacheModules as $module){
        if(in_array($module, $activeApacheModules)){
            $log[] = "inf:Наличен Apache модул: <b>`$module`</b>";
        } else {
            $log[] = "err:Липсващ Apache модул: <b>`$module`</b>";
        }
    }
    

    // Проверка за връзка с MySQL сървъра
    $log[] = 'h:Проверка на сървъра на базата данни:';
    if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS')) {
        $link = mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS);
        if (FALSE !== $link) {
            $log[] = "inf:Успешна връзка със сървъра: <b>`" . EF_DB_HOST ." `</b>";
        } else {
            $log[] = "err:Неуспешна връзка на <b>`" . EF_DB_USER . "`</b> с MySQL сървъра: <b>`" . EF_DB_HOST ." `</b>";
        }
    } else {
        $log[] = "err:Недефинирани константи за връзка със сървъра на базата данни";
    }
    
    if(defined('EF_DB_NAME')) {
        if (!mysql_select_db(EF_DB_NAME)) {
            $createQuery = "CREATE DATABASE IF NOT EXISTS `" . EF_DB_NAME . "`";
            @mysql_query($createQuery, $link);
            if(($mysqlErr = mysql_errno($link)) > 0) {
                $log[] = "err:Грешка <b>{$mysqlErr}</b> при избор на базата: <b>`" . EF_DB_NAME . "`</b>";
			} else {
                $log[] = "new:Създадена е базата: <b>`" . EF_DB_NAME . "`</b>";
            }
        } else {
            $log[] = "inf:Налична база данни: <b>`" . EF_DB_NAME . "`</b>";
        }
    } else {
        $log[] = "err:Не е дефинирана константата <b>`EF_DB_NAME`</b>";
    }

    $log[] = 'h:Изчисляване на контролни суми (MD5):';

    $paths = array(
        'index-tpl' => EF_EF_PATH . '/_docs/webroot/index.php',
        'index' => EF_INDEX_PATH . '/index.php',
        'index-cfg' => EF_INDEX_PATH . '/index.cfg.php',
        'config' => EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php',
        );
    foreach($paths as $key => $path) {
        if(file_exists($path)) {
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
        $texts['body'] = "<table width='100%' class='msg'><tr><th>" .
        "<a href='$selfUrl' class='err'>Отстранете грешките и опитайте пак...</a></th></tr></table><br>" .
        $texts['body'];
    } elseif($stat['wrn']) {
        $texts['body'] = "<table width='100%' class='msg'><tr><th>" .
        "<a href='$nextUrl' class='wrn'>Има предупреждения. Ще продължите ли нататък? »</a></th></tr></table><br>" .
        $texts['body'];
    } else {
        $texts['body'] = "<table width='100%' class='msg'><tr><th>" .
        "<a href='$nextUrl'>&#10003; Всичко е наред. Продължете с инициализирането »</a></th></tr></table><br>" .
        $texts['body'];
    }

    
}

// Ако се намираме на етапа на инициализиране, по-долу стартираме setup-а
if($step == 4) {
	$texts['body'] .= "<iframe src='{$selfUrl}&step=5' name='init' id='init'></iframe>";
}

/**********************************
 * Setup на bgerp
 **********************************/
if ($step == '5') {
	$calibrate = 1000;
    $totalRecords = 137008;
    $totalTables = 201;
    $total = $totalTables*$calibrate + $totalRecords;
    // Пращаме стиловете
    echo ($styles);

    $res = file_get_contents("{$selfUrl}&step=55", FALSE, NULL, 0, 2);
    
    if ($res == 'OK') {
        contentFlush ("<h3 id='startHeader'>Инициализацията стартирана ...</h3>");
    } else {
        contentFlush ("<h3 id='startHeader' style='color: red;'>Грешка при стартиране на Setup!</h3>");
        
        exit;
    }
	
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
    
    mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS);
    
    static $cnt = 0;
    
    do {
        $recordsRes = mysql_query("SELECT SUM(TABLE_ROWS) AS RECS
                                    FROM INFORMATION_SCHEMA.TABLES 
                                    WHERE TABLE_SCHEMA = '" . EF_DB_NAME ."'");
        
        $tablesRes = mysql_query("SELECT COUNT(*) TABLES FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '". EF_DB_NAME ."';");
        $rows = mysql_fetch_object($recordsRes);
        $tables = mysql_fetch_object($tablesRes);
        $tables->TABLES; $rows->RECS;
        
        $percents = round(($rows->RECS+$calibrate*$tables->TABLES)/$total,2)*100;
        
        // Прогресбар
        if ($percents > 100) $percents = 100;
        $width = 4.5*$percents;
        contentFlush("<script>
						document.getElementById(\"progressIndicator\").style.paddingLeft=\"" . $width ."px\";
						document.getElementById(\"progressPercents\").innerHTML = '" . $percents . " %';
					</script>");
        
        // Лог
        $setupLog = file_get_contents(EF_SBF_PATH . '/setupLog.html');
	    //file_put_contents(EF_SBF_PATH . '/setupLog.html', "");
	    
	    $setupLog = preg_replace(array("/\r?\n/", "/\//"), array("\\n", "\/"), addslashes($setupLog));
        
        contentFlush("<script>
        				document.getElementById('setupLog').innerHTML += '" . $setupLog . "';
						var objDiv = document.getElementById('setupLog');
						objDiv.scrollTop = objDiv.scrollHeight;
				</script>");
                
        sleep(2);
    } while ($rows->RECS < $totalRecords && $tables->TABLES < $totalTables);
    
    contentFlush("<h3 id='success' >Инициализирането завърши успешно!</h3>");
    
    $appUri = substr($selfUrl, 0, strpos($selfUrl,'/?'));
    
    $l = linksToHtml(array("new|{$appUri}|Стартиране bgERP »"), "_parent");
    $l = preg_replace(array("/\r?\n/", "/\//"), array("\\n", "\/"), addslashes($l));
    contentFlush("<script>
        				document.getElementById('startHeader').innerHTML = '" .
						 $l . "';
				</script>");
    
    exit;
}

/**********************************
 * Setup на bgerp самостоятелно инсталиране
 **********************************/
if($step == 55) {
    // Затваряме връзката с извикване
    
    // Следващият ред генерира notice,
    // но без него file_get_contents забива, ако трябва да връща повече от 0 байта
    @ob_end_clean();
    
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    ob_start();
    echo "OK";
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    ob_end_clean();

	GLOBAL $setupFlag, $setupLog;
	$setupLog = TRUE;
	$setupFlag = TRUE;
    
	// Локал за функции като basename, fgetcsv
	setlocale(LC_ALL, 'en_US.UTF8');
		
    $Plugins = cls::get('core_Plugins');
    $Plugins->setupMVC();

    $Classes = cls::get('core_Classes');
    $Classes->setupMVC();

    $Cron = cls::get('core_Cron');
    $Cron->setupMVC();

    $Cache = cls::get('core_Cache');
    $Cache->setupMVC();

    // Сетъпваме мениджъра на ролите който си добавя admin, ако я няма
    $Roles = cls::get('core_Roles');
    $Roles->setupMVC();

    $Users = cls::get('core_Users');
    $Users->setupMVC();
    
    $Packs = cls::get('core_Packs');
    $Packs->setupMVC();
    
    // Зависимости на Crm-a
    $Menu = cls::get('bgerp_Menu');
    $Menu->setupMVC();

    $Bucket = cls::get('fileman_Buckets');
    $Bucket->setupMVC();

    $Packs->setupPack('bgerp');

    exit;
}

// Субституираме в лейаута
foreach($texts as $place => $str) {
    $layout = str_replace("[#{$place}#]", $str, $layout);
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
 * Стринговете имат следния формат: "class|url|message";
 * Връща string с html
 */
function linksToHtml($links, $target='_self')
{
    foreach($links as $l) {
        list($class, $url, $text) = explode('|', $l, 3);
        $html .= "\n<br><table width='100%' class='msg'><tr><th>" .
            "\n<a href='{$url}' class='{$class}' target='{$target}'>{$text}</a>\n</th></tr></table><br>";
    }

    return $html;
}



/**
 * Дали има по-нова версия на това репозитори?
 */
function gitHasNewVersion($repoPath, &$log)
{
	$repoName = basename($repoPath);
	
	$command = "git --git-dir={$repoPath}/.git remote show origin";

	exec($command, $arrRes, $returnVar);
	
	// В последния ред на резултата се намира индикацията на промени
	$lastKey = key(array_slice( $arrRes, -1, 1, TRUE));
	$hasNewVersion = strpos($arrRes[$lastKey], "local out of date");
	
	if($hasNewVersion !== FALSE) {
        $log[] = "new:[<b>$repoName</b>] Има нова версия.";
        
        return TRUE;
    }
}


/**
 * Дали има промени в локалното копие?
 */
function gitHasChanges($repoPath, &$log)
{
	$repoName = basename($repoPath);
	
	$command = "git --git-dir={$repoPath}/.git --work-tree={$repoPath} status -s";

	exec($command, $arrRes, $returnVar);
	
	$states = array("M" => "Модифициран", "??"=>"Непознат", "A"=>"Добавен");
	if (!empty($arrRes)) {
	    foreach ($arrRes as $row) {
	        $row = trim($row);
	        $arr = split(" ", $row);
	        $log[] = "wrn:<b>[{$repoName}]</b> " . $states[$arr[0]] . " файл: <b>`{$arr[1]}`</b>";
	    }
	    
    	return TRUE;
	}
}


/**
 * Синхронизира с последната версия на мастер-бранча
 */
function gitPullRepo($repoPath, &$log)
{
	$repoName = basename($repoPath);
	
	$command = "git --git-dir={$repoPath}/.git --work-tree={$repoPath} pull origin master 2>&1";

	exec($command, $arrRes, $returnVar);
	
	$success = array("Alredy up-to-date.", "Fast-forward");
	
	foreach ($success as $needle) {
		if (array_search($needle, $arrRes) !== FALSE) {
			$log[] = "new:<b>[{$repoName}]</b> е обновено.";
			
			return TRUE;
		}
	}
	
	// Показваме грешката, ако не е сработило горното условие
    foreach ($arrRes as $res) {
    	if (strpos($res, 'error:') !== FALSE || strpos($res, 'fatal:') !== FALSE) {
    		$err = substr($res, strrpos($res, ":")+1);
    		$log[] = "err:<b>[{$repoName}]</b> НЕ е обновено: {$err}";
    		
    		return FALSE;
    	}
    }
	
}


/**
 * Унищожава локалните промени, на фаловете, включени в репозиторито
 */
function gitRevertRepo($repoPath, &$log)
{
    $repoName = basename($repoPath);
    
    $command = "git --git-dir={$repoPath}/.git --work-tree={$repoPath} reset --hard origin/master 2>&1";
    
    exec($command, $arrRes, $returnVar);

    foreach ($arrRes as $res) { 
    	if (strpos($res, 'fatal:') !== FALSE) {
    		$err = substr($res, strrpos($res, ":")+1);
    		$log[] = "err:<b>[{$repoName}]</b> НЕ е възстановено: {$err}";
    		
    		return FALSE;
    	}
    }

    $log[] = "msg:Репозиторито <b>[{$repoName}]</b> е възстановено";
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
