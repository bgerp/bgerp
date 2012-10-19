<?php

ob_end_clean();

function progressFlush ($forFlush)
{
    static $started = 0;
    
    ob_clean();
    ob_start();
    
    if ($started == 0) {
        echo str_repeat(" ", 1024), "\n";
        $started++;
    }
    // Прогресбар
    if (is_int($forFlush)) {
        for ($i==1; $i<$forFlush; $i++)
        {
            echo("<span style='color: green;'>+</span>");

        }
        echo("<span style='color: green;'>100 %</span><br>");
    } else {
        // Изкарваме само текст
        echo($forFlush);
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
 * Setup на core_Packs
 **********************************/
if ($_GET['a'] == '5') {
    
    Mode::setPermanent('currentUserRec', NULL);
    Mode::destroy();
    core_Db::$noAutoSetup = TRUE;
    
    progressFlush("<h2> {$_GET['a']} Сетъп на core_Packs ... </h2>");
    echo ("<h4>Създаване структури за:</h4>");
    progressFlush("Плъгини ...");
    $Plugins = cls::get('core_Plugins');
    $res = $Plugins->setupMVC();
    progressFlush(8);

    progressFlush("Класове ...");
    $Classes = cls::get('core_Classes');
    $res .= $Classes->setupMVC();
    progressFlush(13);

    progressFlush("Крон ...");
    $Cron = cls::get('core_Cron');
    $res .= $Cron->setupMVC();
    progressFlush(10);

    progressFlush("Кеш ...");
    $Cache = cls::get('core_Cache');
    $res .= $Cache->setupMVC();
    progressFlush(11);

    progressFlush("Роли ...");
    // Сетъпваме мениджъра на ролите който си добавя admin, ако я няма
    $Roles = cls::get('core_Roles');
    $res .= $Roles->setupMVC();
    progressFlush(7);

    progressFlush("Потребители ...");
    $Users = cls::get('core_Users');
    $res .= $Users->setupMVC();
    progressFlush(15);
    
    progressFlush("Пакети ...");
    $Packs = cls::get('core_Packs');
    $res .= $Packs->setupMVC();
    progressFlush(10);
    
    progressFlush("Меню ...");
    // Зависимости на Crm-a
    $Menu = cls::get('bgerp_Menu');
    $res .= $Menu->setupMVC();
    progressFlush(8);

    progressFlush("Файлове ...");
    $Bucket = cls::get('fileman_Buckets');
    $res .= $Bucket->setupMVC();
    progressFlush(12);
        
    //$res = file_get_contents("http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$URI_PATH}/core_Packs/setupMVC");
    //echo($res);
    progressFlush("<div style='color: green; font-size: 16pt;'>OK!</div>");
    sleep(1);

    // Редиректваме браузъра направо към следващата стъпка
    echo ("<script language=\"javascript\">");
    echo (" parent.next(1);");
    echo ("</script>");
    
    exit;
}


/**********************************
 * Задаване на административен потребител
 **********************************/
if ($_GET['a'] == '6') {
    // Има зададено нещо от формата
    if ($_GET['submitted']==1) {
        
        core_Db::$noAutoSetup = TRUE;
        
        // Лека проверка за коректност
        if ($_GET['pass'] != $_GET['pass_again']) {
            echo ("<li style='color: red;'> Паролите не съвпадат!</li>");
            echo ("<a href='http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI']."&a=" . $_GET['a'] . "'>Назад</a><br>");
            exit;
        }
        $rec->nick = $_GET['nick'];
        $rec->pass = $_GET['pass'];
        $rec->names = $_GET['names'];
        $rec->email = $_GET['email'];
        
        // Добавяме админ потребителя, ако няма потребители досега
        $Users = cls::get('core_Users');
        if(!$Users->fetch('1=1')) {
            $Users->save($rec);
            $res .= "<li>Добавен Административен потребител!";
        } else {
            $res .= "<li>Административния потребител съществува отпреди";
        }
        echo ($res);
        
        exit;
    }
    echo("<h2> {$_GET['a']} Данни за административен потребител</h2>");
    echo("<form id=f1 method='get' action='' target='_self'>");
    echo("<input type=hidden name='SETUP'>");
    echo("<input type=hidden name='a' value={$_GET['a']}>");
    echo("<input type=hidden name=submitted value=1>");
    echo("<li> Ник: <input name=nick></li>");
    echo("<li> Парола: <input name=pass type=password></li>");
    echo("<li> Перола пак: <input name=pass_again type=password></li>");
    echo("<li> Имена: <input name=names></li>");
    echo("<li> Имейл: <input name=email></li>");
    echo("<input type=submit value=Запис>");
    echo("</form>");
    
    exit;
}


/**********************************
 * Задаване на фирма
 **********************************/
if ($_GET['a'] == '7') {
    // Има зададено нещо от формата
    if ($_GET['submitted']==1) {

        core_Db::$noAutoSetup = TRUE;

        $cfg['BGERP_OWN_COMPANY_ID'] = $_GET['companyId'];
        $cfg['BGERP_OWN_COMPANY_NAME'] = $_GET['companyName'];
        $cfg['BGERP_OWN_COMPANY_COUNTRY'] = $_GET['companyCountry'];

        // Ако няма фирма с id=1 - добавяме в конфигурационните данни
        $Company = cls::get('crm_Companies');
        $Company->setupMVC();
        if (!$Company->fetch('#id = 1')) {
            core_Packs::setConfig('crm', $cfg); 
            $packs = cls::get('core_Packs');
            $res = $packs->setupPack('crm');
        } else {
            $res = "<li>Съществува фирма по подразбиране";
        }
        //header("Location: http://{$_SERVER['SERVER_NAME']}/core_Packs/install/?pack=crm");
        //echo ($res);
        
        exit;
    }
    echo("<h2> {$_GET['a']} Данни за фирма</h2>");
    echo("<form id=f1 method='get' action='' target='_self'>");
    echo("<input type=hidden name='SETUP'>");
    echo("<input type=hidden name='a' value={$_GET['a']}>");
    echo("<input type=hidden name=submitted value=1>");
    echo("<input type=hidden name=companyId value=1>");
    echo("<li> Име на фирмата: <input name=companyName></li>");
    echo("<li> Държава: <input name=companyCountry value=Bulgaria></li>");
    echo("<input type=submit value=Запис>");
    echo("</form>");
    
    exit;
}

/**********************************
 * Край на помощника
 **********************************/
if ($_GET['a'] == '8') {
    if ($_GET['submitted']==1) {
        
        core_Db::$noAutoSetup = TRUE;
        
        $packs = cls::get('core_Packs');
        $res = $packs->setupPack('bgerp');
        progressFlush("<div style='color: green; font-size: 16pt;'>OK!</div>");        
        
        sleep(5);
        // Редиректваме браузъра направо към следващата стъпка
        echo ("<script language=\"javascript\">");
        echo (" parent.next(1);");
        echo ("</script>");
        exit;
    }
    
    echo("<h2> {$_GET['a']} Задаването на основните параметри приключи</h2>");
    echo("<form id=f1 method='get' action='' target='_self'>");
    echo("<input type=hidden name='SETUP'>");
    echo("<input type=hidden name='a' value={$_GET['a']}>");
    echo("<input type=hidden name=submitted value=1>");
    echo("<input type=submit value='Стартирай СЕТЪП на бгЕРП' onclick=\"parent.next(1);\">");
    echo("</form>");
    
    exit;
}
if ($_GET['a'] == '9' || $_GET['a'] == '10') {
    echo ("$a");
    exit;
}

?>
<script language="javascript">

    function next(r) {
        if ( typeof next.counter == 'undefined' || r==0 || next.counter>10) {
            next.counter = 0;
        }

        ++next.counter;     
        document.getElementById('test').src='http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}"); ?>'+'&a='+next.counter;

        // Скриване на бутона за сетъп на бгЕРП
        if (next.counter == 8) {
            document.getElementById('next1').style.visibility = 'hidden';
        }

        // Показване на бутона за стартиране на бгЕРП
        if (next.counter == 9) {
            document.getElementById('next1').style.visibility = 'visible';
            document.getElementById('next1').value = 'Стартирай бгЕРП';
        }

        // Стартиране на бгЕРП
        if (next.counter == 10) {
            window.location = 'http://'+'<?php echo("{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$URI_PATH}/"); ?>'+'';
        }
    }
    
</script>

<iframe src='<?php "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank"?>' frameborder="0" name="test" id="test" width=800 height=650></iframe>
<br>
<input type="button" onclick="next(0); document.getElementById('test').src='<?php echo("http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}&a=blank")?>';" value="Начало">
<input id='next1' type="button" onclick="next(1);" value="Следващ">
