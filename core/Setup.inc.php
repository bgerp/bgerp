<?php

if ($_GET['a'] == 'blank') exit;

if(empty($_GET['a'])) {
	echo ("<h1>Задаване на основните параметри</h1>");
}

/**********************************
 * Проверка за конфигурационен файл
 **********************************/
if ($_GET['a'] == '1') {
	echo ("<h2>Проверка за конфигурационен файл ...</h2>");
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
	echo ("<h2>Проверка за връзка към MySql-a ...</h2>");
	if (defined('EF_DB_USER') && defined('EF_DB_HOST') && defined('EF_DB_PASS')) {
		if (FALSE !== mysql_connect(EF_DB_HOST, EF_DB_USER, EF_DB_PASS)) {
			echo("<li style='color: green;'>Успешна връзка с базата данни</li>");
		} else {
			echo("<li style='color: red;'>Неуспешна връзка с базата данни</li>");
		}
	} else {
		echo("<li style='color: red;'>Недефинирани константи за връзка с базата данни</li>");
	}
	
	exit;	
}

/**********************************
 * Проверка за необходимите модули на PHP-то
 **********************************/
if ($_GET['a'] == '3') {
	echo ("<h2>Проверка за необходимите модули на PHP ...</h2>");
	// Масив съдържащ всички активни php модули, необходими за правилна работа на ситемата
	// за сега периодично се попълва ръчно
	$requiredPhpModules = array('calendar', 'Core', 'ctype', 'date', 'ereg',
								'exif', 'filter', 'ftp', 'gd', 'iconv', 'json',
								'mbstring', 'mysql', 'pcre', 'session', 'SimpleXML',
								'SPL', 'standard', 'tokenizer', 'xml', 'zlib', 'soap');
	
	$activePhpModules = get_loaded_extensions();
	
	foreach($requiredPhpModules as $required) {
		if(!in_array($required, $activePhpModules)){
			echo ("<li style='color: red;'> модул: $required - не е инсталиран!</li>");
			flush();
		} else {
			echo ("<li style='color: green;'> модул: $required - OK!</li>");
			flush();
		}
	}
	
	exit;	
}

/**********************************
 * Проверка за необходимите модули на Apache
 **********************************/
if ($_GET['a'] == '4') {
	echo ("<h2>Проверка за необходимите модули на Apache ...</h2>");
	
	// Масив съдържащ всички активни apache модули, необходими за правилна работа на ситемата
	
	$requiredApacheModules = array('core', 'mod_headers', 'mod_mime', 'mod_php5', 'mod_rewrite');
	
	$activeApacheModules = apache_get_modules();
	
	foreach($requiredApacheModules as $requiredA){
		
		if(!in_array($requiredA, $activeApacheModules)){
			echo ("<li style='color: red;'> модул: $requiredA - не е инсталиран!</li>");
			flush();
		} else {
			echo ("<li style='color: green;'> модул: $requiredA - OK!</li>");
			flush();
		}
	}
	
	exit;
}

/**********************************
 * Setup на core_Packs
 **********************************/
if ($_GET['a'] == '5') {
	echo("<h2>Сетъп на core_Packs ... </h2>");
	$res = file_get_contents("http://{$_SERVER['SERVER_NAME']}/core_Packs/setupMVC");
	echo($res);

	exit;
}


/**********************************
 * Задаване на административен потребител
 **********************************/
if ($_GET['a'] == '6') {
	// Има зададено нещо от формата
	if ($_GET['submitted']==1) {
		
		// Лека проверка за коректност
		if ($_GET['pass'] != $_GET['pass_again']) {
			echo ("<li style='color: red;'> Паролите не съвпадат!</li>");
			echo ("<a href='http://".$_SERVER['SERVER_NAME']."/?SETUP&a=" . $_GET['a'] . "'>Назад</a><br>");
			exit;
		}
		$rec['id'] = 1;
		$rec['nick'] = $_GET['nick'];
		$rec['pass'] = $_GET['pass'];
		$rec['names'] = $_GET['names'];
		$rec['email'] = $_GET['email'];
		
		// Сетъпваме мениджъра на ролите който си добавя admin, ако я няма
		$Roles = cls::get('core_Roles');
		$Roles->setupMVC();
		// $res = $Roles->fetchByName('admin');
		// echo('<pre>'); echo($res); die;
		$Users = cls::get('core_Users');
		$res = $Users->setupMVC();
		// Добавяме админ потребителя, ако няма потребители досега
		if(!$Users->fetch('1=1')) {
			$Users->save($rec);
			$res .= "<li>Добавен Административен потребител!";
		} else {
			$res .= "<li>Административния потребител съществува отпреди";
		}
		echo ($res);
		
		exit;
	}
	echo("<h2>Данни за административен потребител</h2>");
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
		
		$cfg['BGERP_OWN_COMPANY_ID'] = $_GET['companyId'];
		$cfg['BGERP_OWN_COMPANY_NAME'] = $_GET['companyName'];
		$cfg['BGERP_OWN_COMPANY_COUNTRY'] = $_GET['companyCountry'];
		//echo('<pre>'); print_r($_SESSION); die;
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
		echo ($res);
		
		exit;
	}
	echo("<h2>Данни за фирма</h2>");
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
		header( "Location: http://".$_SERVER['SERVER_NAME']."/core_Packs/install/?pack=bgerp") ;
		
		exit;
	}
	
	echo("<h2>Задаването на основните параметри приключи</h2>");
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
	    if ( typeof next.counter == 'undefined' || r==0) {
	    	next.counter = 0;
	    }

	    ++next.counter;		
		document.getElementById('test').src='http://'+location.host+'/?SETUP&a='+next.counter;

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
			window.location = 'http://'+location.host+'';
		}
	}
	
</script>

<iframe src='<?php "http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank"?>' frameborder="0" name="test" id="test" width=800 height=650></iframe>
<br>
<input type="button" onclick="next(0); document.getElementById('test').src='<?php echo("http://".$_SERVER['SERVER_NAME']."/?SETUP&a=blank")?>';" value="Начало">
<input id='next1' type="button" onclick="next(1);" value="Следващ">

