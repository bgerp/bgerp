<?php



/**
 * Скрипт 'Setup.inc.php' -  Инсталиране на bgERP
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
 
ob_end_clean();

function progressFlush ($text, $percents=NULL)
{
    static $started = 0;
    
    ob_clean();
    ob_start();
    
    if ($started == 0) {
        echo str_repeat(" ", 1024), "\n";
        $started++;
    }
    
    // Прогресбар
    if (is_numeric($percents)) {
        if ($percents > 100) $percents = 100;
        $width = 3.5*$percents;
        echo "<li style='list-style-type: none;'><span style='width: 180px; display: block; float: left;text-align:right;'>{$text}</span><span style=\"text-align: right; padding-right: 2px; padding-left:{$width}px; background-color: #28DB55;\"><span style=\"width: 0;\">&nbsp;</span></span>";
        echo("<span style=\"font-size: .8em; font-weight: bold; margin-left:3px;\">{$percents} %</span></li>");
    } else {
        // Изкарваме само текст
        echo($text);
    }
    
    ob_flush();
    ob_end_flush();
    flush();
    
}


if ($_GET['a'] == 'blank') exit;

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
        progressFlush ("<h3>Setup-а стартиран ...</h3>");
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
    
    sleep(2);

    echo ("
    <SCRIPT language=\"javascript\">
        parent.document.getElementById('next1').style.visibility = 'visible';
        parent.document.getElementById('next1').value = 'Стартирай бгЕРП';
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

<script language="javascript">
    
    setInterval('frames[0].scrollTo(0,9999999)',1000);
    
    function next(r) {
        if ( typeof next.counter == 'undefined' || r==0 || next.counter>6) {
            next.counter = 0;
        }

        ++next.counter;     
        document.getElementById('test').src='http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}"); ?>'+'&a='+next.counter;

        // Скриване на бутона за сетъп на бгЕРП
        if (next.counter == 5) {
            document.getElementById('next1').style.visibility = 'hidden';
        }

        // Показване на бутона за стартиране на бгЕРП
        //if (next.counter == 10) {
        //    document.getElementById('next1').style.visibility = 'visible';
         //   document.getElementById('next1').value = 'Стартирай бгЕРП';
        //}

        // Стартиране на бгЕРП
        if (next.counter == 6) {
            window.location = 'http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$URI_PATH}/"); ?>'+'';
        }
    }
</script>

<iframe src='<?php "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank"?>' frameborder="0" name="test" id="test" width=800 height=600 scrolling="no"></iframe>
<br>
<input type="button" onclick="next(0); document.getElementById('test').src='<?php echo("http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank")?>';" value="Начало">
<input id='next1' type="button" onclick="next(1);" value="Следващ">
