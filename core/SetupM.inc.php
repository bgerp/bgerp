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


// Лейаута на HTML страницата
$layout = 
"<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<title>bgERP - настройване на системата (стъпка [#currentStep#])</title>
<style type=\"text/css\">
body {
    background-color:#00f; 
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

</style>
<link  rel=\"shortcut icon\" 
href=\"data:image/icon;base64,AAABAAEAEBAAAAAAAABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAABaALPDWgCzw1oAs8N" . "aALPDWgCzw////wDjqQD/46kA/+OpAP/jqQD/////AAB79eAAe/XgAHv14AB79eAAe/XgWgCzw1oAs8NaALPDWgCzw1oAs8P///8A46kA/+OpAP/jqQD/46kA/////wA" . "Ae/XgAHv14AB79eAAe/XgAHv14FwAvLBcALywXAC8sFwAvLBcALyw////AO+4AO3vuADt77gA7e+4AO3///8AAJb34wCW9+MAlvfjAJb34wCW9+NfAMqcXwDKnF8Aypx" . "fAMqcXwDKnP///wDxugDo8boA6PG6AOjxugDo////AACa+OQAmvjkAJr45ACa+OQAmvjkXwDKnF8AypxfAMqcXwDKnF8Aypz///8A8sUAzfPIAMXzyADF8sQAzv///wA" . "ApvjLAK35vgCu+b4AqvnEAKr5xP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wAAfS/MAH0vzAB9L8w" . "AfS/MAH0vzP///wABAAXgAQAF4AEABeABAAXg////AAB9L8wAfS/MAH0vzAB9L8wAfS/MAIs9yACLPcgAiz3IAIs9yACLPcj///8AAQAF4AEABeABAAXgAQAF4P///wA" . "Aiz3IAIs9yACLPcgAiz3IAIs9yACZTMUAmUzFAJlMxQCZTMUAmUzF////AAEABeABAAXgAQAF4AEABeD///8AAJlMxQCZTMUAmUzFAJlMxQCZTMUAql3BAKpdwQCqXrw" . "Aql6+AKpdwv///wABAAXgAQAE4AEABOABAAXg////AACqXcIAql6+AKpevACqXcIAql3C////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD" . "///8A////AP///wD///8A////AABu9N4AbvTeAG703gBu9N4AbvTe////AN6hAP/eoQD/3qEA/96hAP////8AWQCuy1kArstZAK7LWQCuy1kArssAivbiAIr24gCK9uI" . "AivbiAIr24v///wDqswD76rMA++qzAPvqswD7////AFoAt7haALe4WgC3uFoAt7haALe4AJr45ACa+OQAmvjkAJr45ACa+OT///8A8boA6PG6AOjxugDo8boA6P///wB" . "dAMOkXQDDpF0Aw6RdAMOkXQDDpACa+OQAmvjkAJr45ACa+OQAmvjk////APG6AOjxugDo8boA6PG6AOj///8AYQDUkWEA1JFhANSRYQDUkWEA1JEAmvjkAJr45ACa+OQ" . "AmvjkAJr45P///wDxugDo8boA6PG6AOjxugDo////AGEA1JFhANSRYQDUkWEA1JFhANSRBCAAAAQgAAAEIAAABCAAAAQgAAD//wAABCAAAAQgAAAEIAAABCAAAP//AAA" . "EIAAABCAAAAQgAAAEIAAABCAAAA==\" type=\"image/x-icon\">
<script type=\"text/javascript\"></script>
<meta name=\"format-detection\" content=\"telephone=no\">
<meta name=\"robots\" content=\"noindex,nofollow\">
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


// Оторизация. Засега само проверява дали е от локалния хост
$isLocal = in_array($_SERVER['REMOTE_ADDR'], array('::1', '127.0.0.1'));

if(!$isLocal) {
    return;
}


// На коя стъпка се намираме в момента?
$step = $_GET['step'] ? $_GET['step'] : 1;
$texts['currentStep'] = $step;

// Собственото URL
$selfUrl = "//{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}";
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

 
    // Ако GIT - а открие променени файлове, трябва да се изведат следните съобщения 
    // 1. В системата има променени файлове. Възстановете ги. (прави Revert на променените файлове и остава на тази стъпка)
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

    } elseif($newVer || $changed) {
        $links[] = "wrn|{$nextUrl}|Продължете, без да променяте системата »";
    } else {
        $links[] = "inf|{$nextUrl}|Вие имате последната версия на <b>bgERP</b>, може да продължите »";
    }
    
    $texts['body'] = linksToHtml($links);
        
    // Статистика за различните класове съобщения
    $stat = array();

    $texts['body'] .=  logToHtml($log, $stat);

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



// Субституираме в лейаута
foreach($texts as $place => $str) {
    $layout = str_replace("[#{$place}#]", $str, $layout);
}

  
 

echo $layout;
ob_flush();

// Ако се намираме на етапа на инициализиране, по-долу стартираме setup-а
if($step=4) {
}

die;


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
function linksToHtml($links)
{
    foreach($links as $l) {
        list($class, $url, $text) = explode('|', $l, 3);
        $html .= "\n<br><table width='100%' class='msg'><tr><th>" .
            "\n<a href='{$url}' class='{$class}'>{$text}</a>\n</th></tr></table><br>";
    }

    return $html;
}



/**
 * Дали има по-нова версия на това репозитори?
 */
function gitHasNewVersion($repoPath, &$log)
{
    if(rand(1,10) > 5) {
        $repoName = basename($repoPath);
        $log[] = "new:Има нова версия на  [<b>$repoName</b>]";
        return TRUE;
    }
}


/**
 * Дали има по-нова версия на това репозитори?
 */
function gitHasChanges($repoPath, &$log)
{
    if(rand(1,10) > 8) {
        $log[] = "wrn:Модифициран файл: <b>`somefile.class.php`</b>";

        return TRUE;
    }
}


/**
 * Синхронизира с последната версия на мастер-баранча
 */
function gitPullRepo($repoPath, &$log)
{
    sleep(1);
    $repoName = basename($repoPath);
    $log[] = "new:Репозиторито <b>[{$repoName}]</b> е обновено";
}


/**
 * Унищожава локалните промени, на фаловете, включени в репозиторито
 */
function gitRevertRepo($repoPath, &$log)
{
    sleep(1);
    $repoName = basename($repoPath);
    $log[] = "msg:Репозиторито <b>[{$repoName}]</b> е възстановено";
}



function progressFlush ($text, $percents=NULL)
{
    static $started = 0;
    
    static $absoluteTop = array();
    
    if (!isset($absoluteTop["$text"])) {
        $absoluteTop["$text"] = 35*count($absoluteTop)+50;
    } 
    
    ob_clean();
    ob_start();
    
    if ($started == 0) {
        echo str_repeat(" ", 1024), "\n";
        echo ("<!DOCTYPE html>");
    }
    $started++;
    // Прогресбар
    if (is_numeric($percents)) {
        if ($percents > 100) $percents = 100;
        $width = 4.5*$percents;
        echo "<li style='list-style-type: none; background-color: white; position:absolute; left:25px; top:" . $absoluteTop["$text"] . "px; z-index: {$started};'><span style='width: 180px; display: block; float: left;text-align:right;'>{$text}</span><span style=\"text-align: right; padding-right: 2px; padding-left:{$width}px; background-color: #28DB55;\"><span style=\"width: 0;\">&nbsp;</span></span>";
        echo("<span style=\"font-size: .8em; font-weight: bold; margin-left:3px;\">{$percents} %</span></li>");
    } else {
        // Изкарваме само текст
        echo("$text");
    }
    
    ob_flush();
    ob_end_flush();
    flush();
    
}

if ($_GET['a'] == 'blank') {
    echo ("<h2>Начало на инициализация...</h2>");
    exit;
}

if(empty($_GET['a'])) {
    echo ("<h1>Задаване на основните параметри</h1>");
}

$error = FALSE;

$URI_PATH = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],'/?'));

/**********************************
 * Проверка за конфигурационен файл
 **********************************/
if ($_GET['a'] == '1') {
    echo ("<h2>{$_GET['a']} Проверка за конфигурационен файл ...</h2>");
}

$filename = EF_ROOT_PATH . '/conf/' . EF_APP_NAME . '.cfg.php';

if(file_exists($filename)) {
    include($filename);
} else {
    echo "<li style='color: red;'>Грешка: Липсва конфигурационен файл $filename</li>";
    $error = TRUE;
    
    exit;
}

if ($_GET['a'] == '1') {
    echo "<li style='color: green;'>Конфигурационен файл $filename - ОК</li>";
    
    exit;
}

/**********************************
 * Проверка за връзка към MySql-a
 **********************************/
if ($_GET['a'] == '2') {
    echo ("<h2> {$_GET['a']} Проверка за връзка към MySql-a ...</h2>");
    if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS')) {
        if (FALSE !== mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS)) {
            echo("<li style='color: green;'>Успешна връзка с базата данни</li>");
        } else {
            echo("<li style='color: red;'>Неуспешна връзка с базата данни</li>");
            $error = TRUE;
        }
    } else {
        echo("<li style='color: red;'>Недефинирани константи за връзка с базата данни</li>");
        $error = TRUE;
    }
    
    // Ако няма грешка редиректваме браузъра направо към следващата стъпка
    if (!$error) {
        progressFlush("<div style='color: green; font-size: 16pt;'>OK!</div>");
        sleep(2);
        echo ("<script language=\"javascript\">");
        echo (" parent.next(1);");
        echo ("</script>");
    }

    exit;   
}

/**********************************
 * Проверка за необходимите модули на PHP-то
 **********************************/
if ($_GET['a'] == '3') {
    echo ("<h2> {$_GET['a']} Проверка за необходимите модули на PHP ...</h2>");
    // Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
    // за сега периодично се попълва ръчно
    $requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
                                'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
                                'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
                                'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');
    
    $activePhpModules = get_loaded_extensions();
    
    foreach($requiredPhpModules as $required) {
        if(!in_array($required, $activePhpModules)){
            progressFlush ("<li style='color: red;'> модул: $required - не е инсталиран!</li>");
            $error = TRUE;
        } else {
            progressFlush ("<li style='color: green;'> модул: $required - OK!</li>");
        }
    }

    // Ако няма грешка редиректваме браузъра направо към следващата стъпка
    if (!$error) {
        progressFlush("<div style='color: green; font-size: 16pt;'>OK!</div>");
        sleep(2);
        echo ("<script language=\"javascript\">");
        echo (" parent.next(1);");
        echo ("</script>");
    }
    
    exit;   
}

/**********************************
 * Проверка за необходимите модули на Apache
 **********************************/
if ($_GET['a'] == '4') {
    echo ("<h2> {$_GET['a']} Проверка за необходимите модули на Apache ...</h2>");
    
    // Масив съдържащ всички активни apache модули, необходими за правилна работа на ситемата
    
    $requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');
    
    $activeApacheModules = apache_get_modules();
    
    foreach($requiredApacheModules as $requiredA){
        
        if(!in_array($requiredA, $activeApacheModules)){
            progressFlush ("<li style='color: red;'> модул: $requiredA - не е инсталиран!</li>");
            $error = TRUE;
        } else {
            progressFlush ("<li style='color: green;'> модул: $requiredA - OK!</li>");
        }
    }

    // Ако няма грешка редиректваме браузъра направо към следващата стъпка
    if (!$error) {
        progressFlush("<div style='color: green; font-size: 16pt;'>OK!</div>");
        sleep(2);
        echo ("<script language=\"javascript\">");
        echo (" parent.next(1);");
        echo ("</script>");
    }
    
    exit;
}

/**********************************
 * Setup на bgerp
 **********************************/

if ($_GET['a'] == '5') {
    $totalRecords = 136676;
    $totalTables = 173;

    $res = file_get_contents("http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=55", FALSE, NULL, 0, 2);
    
    if ($res == 'OK') {
        progressFlush ("<h3>Инициализацията стартирана ...</h3>");
    } else {
        progressFlush ("<h3 style='color: red;'>Грешка при стартиране на Setup!</h3>");
        
        exit;
    }
    
    mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS);
    
    do {
        $recordsRes = mysql_query("SELECT SUM(TABLE_ROWS) AS RECS
                                    FROM INFORMATION_SCHEMA.TABLES 
                                WHERE TABLE_SCHEMA = '" . EF_DB_NAME ."'");
        
        $tablesRes = mysql_query("SELECT COUNT(*) TABLES FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '". EF_DB_NAME ."';");
        $rows = mysql_fetch_object($recordsRes);
        $tables = mysql_fetch_object($tablesRes);
        progressFlush("Зареждане данни:&nbsp", round($rows->RECS/$totalRecords,2)*100);
        progressFlush("Създаване таблици:&nbsp", round($tables->TABLES/$totalTables,2)*100);
        sleep(2);
    } while ($rows->RECS < $totalRecords && $tables->TABLES < $totalTables);
    
    progressFlush("<h3 style='position:relative; top:150px; padding-left:190px;'>Инициализирането завърши успешно!</h3>");
    
    echo ("
    <SCRIPT language=\"javascript\">
        parent.document.getElementById('next1').disabled = false;
        parent.document.getElementById('start').disabled = false;
        parent.document.getElementById('next1').value = 'Стартирай bgERP';
    </SCRIPT>
    ");
    
    exit;
}

/**********************************
 * Setup на bgerp самостоятелно инсталиране
 **********************************/
if ($_GET['a'] == '55') {

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


?>
<html>
<head>
	<!-- <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"> -->
</head>
<body>
<script language="javascript">
    
    // setInterval('frames[0].scrollTo(0,9999999)',1000);
    
    function next(r) {
        if ( typeof next.counter == 'undefined' || r==0 || next.counter>6) {
            next.counter = 0;
        }

        ++next.counter;     
        document.getElementById('test').src='http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}"); ?>'+'&a='+next.counter;

        // Деактивиране на бутоните
        if (next.counter == 5) {
            document.getElementById('next1').disabled = true;
            document.getElementById('start').disabled = true;
        }

        // Стартиране на bgERP
        if (next.counter == 6) {
            window.location = 'http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$URI_PATH}/"); ?>'+'';
        }
    }
</script>

<iframe src='<?php "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank"?>' frameborder="1" name="test" id="test" width=750 height=500 ></iframe>
<div style='width: 750px;'>
    <hr>
    <input id='start' type="button" style="float: left;" onclick="next(0); document.getElementById('test').src='<?php echo("http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank")?>';" value="Начало">
    <input id='next1' type="button" onclick="next(1);" value="Следващ" style="float: right;">
</div>
</body>
</html>
