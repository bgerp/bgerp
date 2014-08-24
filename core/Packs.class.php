<?php



/**
 * Клас 'core_Packs' - Управление на пакети
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
class core_Packs extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Управление на пакети';
    
    
    /**
     * Кой може да инсталира?
     */
    var $canInstall = 'admin';
    
    
    /**
     * Кои може да деинсталира?
     */
    var $canDeinstall = 'admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
	
    /**
     * По колко пакета да показва на страница
     */
    var $listItemsPerPage = 24;
    

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,install=Обновяване,config=Конфигуриране,deinstall=Премахване';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'identifier(32)', 'caption=Пакет,notNull');
        $this->FLD('version', 'double(decimals=2)', 'caption=Версия,input=none');
        $this->FLD('info', 'varchar(128)', 'caption=Информация,input=none');
        $this->FLD('startCtr', 'varchar(64)', 'caption=Стартов->Мениджър,input=none,column=none');
        $this->FLD('startAct', 'varchar(64)', 'caption=Стартов->Контролер,input=none,column=none');
        $this->FLD('deinstall', 'enum(no,yes)', 'caption=Деинсталиране,input=none,column=none');
        
        // Съхранение на данните за конфигурацията
        $this->FLD('configData', 'text', 'caption=Конфигурация->Данни,input=none,column=none');

        $this->load('plg_Created,plg_SystemWrapper');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Дали пакета е инсталиран
     * 
     * @param string $name
     * 
     * @return id
     */
    static function isInstalled($name)
    {
        $rec = static::fetch(array("#name = '[#1#]'", $name));
        
        if (!$rec) return FALSE;
        
        return $rec->id;
    }
    
    
    /**
     * Начална точка за инсталиране на пакети
     */
    function act_Install()
    {
        
        $this->requireRightFor('install');
        
        $pack = Request::get('pack', 'identifier');
        
        if (!$pack) error('Missing pack name.');
        
        $res = $this->setupPack($pack, 0, TRUE, TRUE);
        
        return $this->renderWrapping($res);
    }
    
    
    /**
     * Деинсталиране на пакет
     */
    function act_Deinstall()
    {
        $this->requireRightFor('deinstall');
        
        $pack = Request::get('pack', 'identifier');
        
        if (!$pack) error('Липсващ пакет', $pack);
        
        if (!$this->fetch("#name = '{$pack}'")) {
            error('Този пакет не е инсталиран', $pack);
        }
        
        if ($this->fetch("(#name = '{$pack}') AND (#deinstall = 'yes')")) {
            
            $cls = $pack . "_Setup";
            
            if (cls::load($cls, TRUE)) {
                
                $setup = cls::get($cls);
                
                if (!method_exists($setup, 'deinstall')) {
                    $res = "<h2>Пакета <span class=\"green\">'{$pack}'</span> няма деинсталатор.</h2>";
                } else {
                    $res = "<h2>Деинсталиране на пакета <span class=\"green\">'{$pack}'</span></h2>";
                    $res .= (string) "<ul>" . $setup->deinstall() . "</ul>";
                }
            } else {
                $res = "<h2 class='red''>Липсва кода на пакета '{$pack}'</h2>";
            }
        }
        
        // Общи действия по деинсталирането на пакета
        
        // Премахване от core_Interfaces
        core_Interfaces::deinstallPack($pack);
        
        // Скриване от core_Classes
        core_Classes::deinstallPack($pack);
        
        // Премахване от core_Cron
        core_Cron::deinstallPack($pack);
        
        // Премахване от core_Plugins
        core_Plugins::deinstallPack($pack);
        
        // Премахване на информацията за инсталацията
        $this->delete("#name = '{$pack}'");
        
        $res .= "<div>Успешно деинсталиране.</div>";
        
        return new Redirect(array($this), $res);
    }
    
    
    /**
     * Връща всички не-инсталирани пакети
     */
    function getNonInstalledPacks()
    {
        
        if (!$this->fetch("#name = 'core'")) {
            $path = EF_EF_PATH . "/core/Setup.class.php";
            
            if(file_exists($path)) {
                $opt['core'] = 'Ядро на EF "core"';
            }
        }
        
        $appDirs = $this->getSubDirs(EF_APP_PATH);
        
        $vendorDirs = $this->getSubDirs(EF_VENDORS_PATH);
        
        $efDirs = $this->getSubDirs(EF_EF_PATH);
        
        if (defined('EF_PRIVATE_PATH')) {
            $privateDirs = $this->getSubDirs(EF_PRIVATE_PATH);
        }
        
        if (count($appDirs)) {
            foreach ($appDirs as $dir => $dummy) {
                $path = EF_APP_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    unset($vendorDirs[$dir]);
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] =  $dir .' - компонент на приложението';
                    }
                }
            }
        }
        
        if (count($vendorDirs)) {
            foreach ($vendorDirs as $dir => $dummy) {
                $path = EF_VENDORS_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] =  $dir .' - публичен компонент';
                    }
                }
            }
        }
        
        if (count($efDirs)) {
            foreach ($efDirs as $dir => $dummy) {
                $path = EF_EF_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = $dir . ' - компонент на фреймуърка"';
                    }
                }
            }
        }
        
        if (count($privateDirs)) {
            foreach($privateDirs as $dir => $dummy) {
                $path = EF_PRIVATE_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if (!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] =  $dir .' - собствен компонент';
                    }
                }
            }
        }
        
        return $opt;
    }
    
    
    /**
     * Изпълнява се преди извличането на редовете за листови изглед
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("#name");
    }
    
    
    /**
     * Рендира лентата с инструменти за списъчния изглед
     */
    function renderListToolbar_($data)
    {
        if (! ($opt = $this->getNonInstalledPacks())) return "";
        
        $form = cls::get('core_Form', array('view' => 'horizontal'));
        $form->FNC('pack', 'varchar', 'caption=Пакет,input');
        
        $form->setOptions('pack', $opt);
        $form->toolbar = cls::get('core_Toolbar');
        $form->setHidden(array('Act' => 'install'));
        $form->toolbar->addSbBtn('Инсталирай', 'default', 'ef_icon = img/16/install.png');
        $form->toolbar->addBtn('Обновяване на системата', array("core_Packs", "systemUpdate"), 'ef_icon = img/16/install.png');
        
        return $form->renderHtml();
    }
    
    
    /**
     * Връща съдържанието на кеша за посочения обект
     */
    function getSubDirs($dir)
    {
        $dirs = array();
        
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                
                while ($file = readdir($dh)) {
                    
                    if ($file == "." || $file == "..") continue;
                    
                    if (is_dir($dir . "/" . $file)) {
                        $dirs[$file] = TRUE;
                    }
                }
            } else {
                bp("Can't open dir", $dir, $dh);
            }
        }
        
        return $dirs;
    }
    
    
    /**
     * След конвертирането на един ред от вътрешно към вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Показва пореден, вместо ID номер
        static $rowNum;
        $rowNum++;
        $row->id = $rowNum;
        
        $imageUrl = sbf("img/100/default.png","");
        
        $filePath = getFullPath("{$rec->name}/icon.png");

        if ($filePath){
       		$imageUrl = sbf("{$rec->name}/icon.png","");
       	}
       	
       	$row->img = ht::createElement("img", array('src' => $imageUrl, 'alt' => 'icon-' . $rec->name));
       	
        $row->name = "<b>" . $mvc->getVerbal($rec, 'name') . "</b>";
         
        $row->name = new ET($row->name);
        $row->name->append(' ' . str_replace(',', '.', $rec->version));
        
    	if ($rec->startCtr) {
    	    try {
        	    $makeLink = FALSE;
        		
            	$startCtrMvc = cls::get($rec->startCtr);
            	
            	// Слага се линк към пакета, само ако потребителя има права за него
            	if(method_exists($startCtrMvc, 'haveRightFor')){
            		
            		//@TODO да се проверява имали права за default екшъна а не конкретно list
            		if($startCtrMvc->haveRightFor('list')){
            			$makeLink = TRUE;
            		}
            	} else {
            		
            		// Ако няма изискване за права се слага линк към пакета
            		$makeLink = TRUE;
            	}
            	
            	if($makeLink){
            		$row->name = ht::createLink($row->name, array($rec->startCtr, $rec->startAct), NULL, "class=pack-title");
            		$row->img = ht::createLink($row->img, array($rec->startCtr, $rec->startAct));
            	}
    	    } catch (Exception $e) {
    	        // Възможно е да липсва кода на пакета
    	    }
    		
        }
        
        
        if ($rec->deinstall == 'yes') {
        	$row->deinstall = ht::createLink(' ', array($mvc, 'deinstall', 'pack' => $rec->name), 'Наистина ли искате да деинсталирате пакета?', array('id'=>$rec->name."-deinstall", 'class'=>'deinstall-pack', 'ef_icon' => 'img/16/cancel.png'));
        } else {
        	$row->deinstall = "";
        }
        
        $row->name .= $row->deinstall;
        $row->name .= "<div class=\"pack-info\">{$rec->info}</div>";
       	
        $row->install = ht::createLink(tr("Инициализиране"), array($mvc, 'install', 'pack' => $rec->name), NULL, array('id'=>$rec->name."-install"));
        
        try {
            $conf = self::getConfig($rec->name);
        } catch (core_exception_Expect $e) {
            $row->install = 'Липсва кода на пакета!';
            $row->ROW_ATTR['style'] = 'background-color:red';
            return;
        }
        
        if ($conf->getConstCnt()) {

            $cls = $rec->name . "_Setup";
            $warn = '';
            if (cls::load($cls, TRUE)) {
                $setup = cls::get($cls);
                if(method_exists($setup, 'checkConfig') && $setup->checkConfig()) {
                    $warn = "<span  style='color:yellow; background-color:red; padding-left:3px; padding-right:3px; margin-right:5px;'>!</span>";
                }
            } 

            $row->config = ht::createLink($warn . tr("Настройки"), array($mvc, 'config', 'pack' => $rec->name, 'ret_url' => TRUE), NULL, array('id'=>$rec->name."-config"));
        }

        if ($conf->haveErrors()) {

            $row->ROW_ATTR['style'] = 'background-color:red';
        }
    }
    
    
    /**
     * Проверява:
     * (1) дали таблицата на този модел съществува
     * (2) дали е установен пакета 'core'
     * (3) дали е установен пакета EF_APP_CODE_NAME
     * което и да не е изпълнено - предизвиква начално установяване
     */
    function checkSetup()
    {
        static $semafor;
        
        if($semafor) return;
        
        $semafor = TRUE;
        
        if (!$this->db->tableExists($this->dbTableName)) {
            $this->firstSetup();
        } elseif (!$this->fetch("#name = 'core'") ||
            (!$this->fetch("#name = '" . EF_APP_CODE_NAME . "'") && cls::load(EF_APP_CODE_NAME . "_Setup", TRUE))) {
            $this->firstSetup();
        } else {

            return TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Setup()
    {
        if (isDebug()) {
            return $this->firstSetup(array('Index'));
        }
    }
    
    
    /**
     * Тази функция получава управлението само след първото стартиране
     * на системата. Нейната задача е да направи начално установяване
     * на ядрото на системата и заглавния пакет от приложението
     */
    function firstSetup($nextUrl = NULL)
    {
        $res = $this->setupPack('core');
        
        $res .= $this->setupPack(EF_APP_CODE_NAME);
        
        $html = "<html><head>";
        
        // Редиректваме към Users->add, с връщане към текущата страница
        $Users = cls::get('core_Users');
        
        if (!$nextUrl) {
            // Ако нямаме нито един потребител, редиректваме за добавяне на администратор
            if(!$Users->fetch('1=1')) {
                $url = array('core_Users', 'add', 'ret_url' => TRUE);
            } else {
                $url = getCurrentUrl();
            }
        } else {
            $url = $nextUrl;
        }

        $url = toUrl($url);
        
        $html .= "<meta http-equiv='refresh' content='15;url={$url}' />";
        
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
        $html .= "</head><body>";
        
        $html .= $res;
        
        $html .= "</body></html>";
        
        echo $html;
        
        shutdown();
    }
    
    
    /**
     * Прави начално установяване на посочения пакет. Ако в
     * Setup-а на пакета е указано, че той зависи от други пакети
     * (var $depends = ... ), прави се опит и те да се установят
     */
    function setupPack($pack, $version = 0, $force = TRUE, $loadData = FALSE)
    {
        // Максиламно време за инсталиране на пакет
        set_time_limit(400);
        
        static $f = 0;
        
        DEBUG::startTimer("Инсталиране на пакет '{$pack}'");
        
        // Имената на пакетите са винаги с малки букви
        $pack = strtolower($pack);
        
        // Предпазване срещу рекурсивно зацикляне
        if ($this->alreadySetup[$pack . $force]) return;
        
        // Отбелязваме, че на текущия хит, този пакет е установен
        $this->alreadySetup[$pack . $force] = TRUE;

        GLOBAL $setupFlag;
        
        // Ако е пуснат от сетъп-а записваме в Лог-а 
        if ($setupFlag) {
        	file_put_contents(EF_TEMP_PATH . '/setupLog.html', "<h2>Инсталиране на {$pack} ... <h2>", FILE_APPEND|LOCK_EX);
        }
        
        // Проверка дали Setup класа съществува
        if (!cls::load($pack . "_Setup", TRUE)) {
            return "<h4>Невъзможност да се инсталира <span class=\"red\">{$pack}</span>. " .
            "Липсва <span class=\"red\">Setup</span> клас.</h4>";
        }
        
        // Вземаме Setup класа, за дадения пакет
        $setup = cls::get($pack . '_Setup');
        
        // Ако има зависимости, проследяваме ги
        // Първо инсталираме зависимостите
        if ($setup->depends) {
            $depends = arr::make($setup->depends, TRUE);
            
            foreach($depends as $p => $v) {
                $res .= $this->setupPack($p, $v, FALSE, $loadData);
            }
        }

        // Започваме самото инсталиране
        if ($setup->startCtr && !$setupFlag) {
            $res .= "<h2>Инсталиране на пакета \"<a href=\"" .
            toUrl(array($setup->startCtr, $setup->startAct)) . "\"><b>{$pack}</b></a>\"&nbsp;";
        } else {
            $res .= "<h2>Инсталиране на пакета \"<b>{$pack}</b>\"&nbsp;";
        }

        try {
            $conf = self::getConfig($pack);
            if($conf->getConstCnt() && !$setupFlag) {  
               $res .= ht::createBtn("Конфигуриране", array('core_Packs', 'config', 'pack' => $pack), NULL, NULL, 'class=btn-settings');
            }
        } catch (core_exception_Expect $e) {}

        $res .= '</h2>';
        
        $res .= "<ul>";
        
        // Единственото, което правим, когато версията, която инсталираме
        // е по-малка от изискваната, е да сигнализираме за този факт
        if ($version > 0 && $version > $setup->version) {
            $res .= "<li style='color:red'>За пакета '{$pack}' се изисква версия [{$version}], " .
            "а наличната е [{$setup->version}]</li>";
        }
        
        // Ако инсталирането е форсирано 
        //   или този пакет не е инсталиран до сега 
        //   или инсталираната версия е различна спрямо тази
        // извършваме инсталационна процедура
        if (!$force) {
            $rec = $this->fetch("#name = '{$pack}'");
        }
        
        if ($force || empty($rec) || ($rec->version != $setup->version)) {
            
            // Форсираме системния потребител
            core_Users::forceSystemUser();
            
            // Форсираме Full инсталиране, ако имаме промяна на версиите
            if($rec && ($rec->version != $setup->version)) {
                Request::push(array('Full' => 1), 'full');
            }
 
            // Правим началното установяване
            $res .= $setup->install();

            if($loadData) {
                $res .= $setup->loadSetupData();
            }

            Request::pop('full');

            // Де-форсираме системния потребител
            core_Users::cancelSystemUser();
            
            $rec = $this->fetch("#name = '{$pack}'");
            
            // Правим запис на факта, че пакетът е инсталиран
            if(!is_object($rec)) $rec = new stdClass();
            $rec->name = $pack;
            $rec->version = $setup->version;
            $rec->info = $setup->info;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            $rec->deinstall = method_exists($setup, 'deinstall') ? 'yes' : 'no';
            $this->save($rec);
        } else {
            $res .= "<li>Пропускаме, има налична инсталация</li>";
        }
        
        if (method_exists($setup, 'checkConfig')) {
            $res .= $setup->checkConfig();
        }
        
        $res .= "</ul>";
        
        if ($setupFlag) {
			// Махаме <h2> тага на заглавието
			$res = substr($res, strpos($res, "</h2>"), strlen($res));
			file_put_contents(EF_TEMP_PATH . '/setupLog.html', $res, FILE_APPEND|LOCK_EX);
			unset($res);
        }
        
        DEBUG::stopTimer("Инсталиране на пакет '{$pack}'");
        
        if ($setupFlag && $pack == 'bgerp') {
            shutdown();
        }

        return $res;
    }


	/**
     * Стартира обновяване на системата през УРЛ
     */
    function act_systemUpdate()
    {
		requireRole('admin');
		self::systemUpdate();
    }

    
    /**
     * Стартира обновяване на системата
     */
    function systemUpdate()
	{
		$SetupKey = setupKey();
		//$SetupKey = md5(BGERP_SETUP_KEY . round(time()/10));
		
		redirect(array("core_Packs", "systemUpdate", SetupKey=>$SetupKey, "step"=>2, "bgerp"=>1));
	}    


    /****************************************************************************************
     *                                                                                      *
     *     Функции за работа с конфигурацията                                               *
     *                                                                                      *
     ****************************************************************************************/

    /**
     * Връща конфигурационните данни за даден пакет
     */
    static function getConfig($packName, $userId=NULL) 
    {
        $rec = static::fetch("#name = '{$packName}'");
        $setup = cls::get("{$packName}_Setup");

        // В Setup-a се очаква $configDesctiption в следната структура:
        // Полета за конфигурационни променливи на пакета
        // Описание на конфигурацията: 
        // array('CONSTANT_NAME' => array($type, 
        //                                $params, 
        //                                'options' => $options, 
        //                                'suggestions' => $suggestions, 
        //        'CONSTANT_NAME2' => .....
               
        $conf = cls::get('core_ObjectConfiguration', array($setup->getConfigDescription(), $rec->configData, $userId));
    
        return $conf;
    }
    
    
    /**
     * Връща дадения код от конфигурационните данни
     * 
     * @param mixed $packConfig - Инстанция на пакета или името на пакета
     * @param string $key - Името на полето
     */
    static function getConfigKey($packConfig, $key)
    {
        if (!is_object($packConfig)) {
            $packConfig = static::getConfig($packConfig);
        }
        
        return $packConfig->_data[$key];
    }
    
    
    /**
     * Връща стойността на конфига, в зависимост от езика
     * 
     * @param mixed $packConfig - Инстанция на пакета или името на пакета
     * @param string $key - Името на полето
     * 
     * @return string
     */
    static function getConfigValue($packConfig, $key)
    {
        $value = NULL;
        
        if (!is_object($packConfig)) {
            $packConfig = static::getConfig($packConfig);
        }
        
        // Ако текущия език не отговаря на езика по подразбиране
        $currLg = core_Lg::getCurrent();
        $defaultLg = core_Lg::getDefaultLang();
        if ($defaultLg != $currLg) {
            try {
                
                // Опитаваме се да вземем данните за текущия език
                $currLgHeader = $key . '_' . strtoupper($currLg);
                $value = $packConfig->$currLgHeader;
                
                if (is_null($value) && ($currLg != 'en')) {
                    
                    // Ако няма данни за текущия език използваме на английски
                    $value = EMAIL_OUTGOING_HEADER_TEXT_EN;
                }
            } catch (Exception $e) {
            }
        }
        
        if (is_null($value)) {
            
            // Вземаме хедъра по подразбиране
            $value = $packConfig->$key;
        }
        
        return $value;
    }
    
    
    /**
     * Задаваме стойност за ключ от пакета, ако не е зададен
     * 
     * @param string $pack
     * @param string $dataKey
     * @param string $dataVal
     * 
     * @return boolean
     */
    static function setIfNotConfigKey($pack, $dataKey, $dataVal)
    {
        // Вземаме конфига
        $confWebkit = core_Packs::getConfig($pack);
        
        $oldVal = core_Packs::getConfigKey($confWebkit, $dataKey);
        
        // Ако не е избрана нищо
        if (!isset($oldVal)) {
            
            $data[$dataKey] = $dataVal;
            
            // Добавяме в конфигурацията
            core_Packs::setConfig($pack, $data);
            
            return TRUE;
        }
    }
    
    
    /**
     * Конфирурира даден пакет
     */
    function act_Config()
    {
        requireRole('admin');

        expect($packName = Request::get('pack', 'identifier'));
        
        $rec = static::fetch("#name = '{$packName}'");
        
        $cls = $packName . "_Setup";
           
        if (cls::load($cls, TRUE)) {
            $setup = cls::get($cls);
        } else {
            error("Липсваш клас $cls");
        }
        
        if (!($description = $setup->getConfigDescription())) {
            error("Пакета $pack няма нищо за конфигуриране");
        }
        
        if ($rec->configData) {
            $data = unserialize($rec->configData);
        } else {
            $data = array();
        }
 
        $form = cls::get('core_Form');

        $form->title = "Настройки на пакета|* <b style='color:green;'>{$packName}<//b>";
 
        foreach ($description as $field => $arguments) {
            $type   = $arguments[0];
            $params = arr::combine($arguments[1], $arguments[2]);
            
            // Полето ще се въвежда
            $params['input'] = 'input';
            
            // Ако не е зададено, заглавието на полето е неговото име
            setIfNot($params['caption'], '|*' . $field);

            $typeInst = core_Type::getByName($type);

            if (defined($field)) {
                $defVal = $typeInst->toVerbal(constant($field));
                $params['hint'] .= ($params['hint'] ? "\n" : '') . 'Стойност по подразбиране|*: "' . $defVal . '"';
            }

            $form->FNC($field, $type, $params);
            
            if ($data[$field] && (!defined($field) || ($data[$field] != constant($field)))) { 
                $form->setDefault($field, $data[$field]);
            } elseif(defined($field)) {
                $form->setDefault($field, constant($field));
                $form->setField($field, array('attr' => array('class' => 'const-default-value')));
            }
        }

        $form->setHidden('pack', $rec->name);

        $form->input();

        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        if ($form->isSubmitted()) {
            
            // $data = array();

            foreach ($description as $field => $params) {
                $sysDefault = defined($field) ? constant($field) : '';
                if ($sysDefault != $form->rec->{$field} ) {
                    
                    if ($form->rec->{$field} !== NULL) {
                        $data[$field] = $form->rec->{$field};
                    }
                } else {
                    $data[$field] = '';
                }
            }

            $id = self::setConfig($packName, $data);
        
            // Правим запис в лога
            $this->log($data->cmd, $rec->id, "Промяна на конфигурацията на пакет {$packName}");
            
            return new Redirect($retUrl);
        }
        
        $form->toolbar->addSbBtn('Запис', 'default', 'ef_icon = img/16/disk.png');

        // Добавяне на допълнителни системни действия
        if (count($setup->systemActions)) {
            foreach ($setup->systemActions as $name => $url) {
                $form->toolbar->addBtn($name, $url);
            }
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl,  'ef_icon = img/16/close16.png');
        
        if (method_exists($setup, 'checkConfig') && ($errMsg = $setup->checkConfig())) {
            $form->info = "<div style='padding:10px;border:dotted 1px red;background-color:#ffff66;color:red;'>{$errMsg}</div>";
        }
        
        return $this->renderWrapping($form->renderHtml());

    }
    

    /**
     * Задава конфигурация на пакет
	 *
     * @param string $name
     * @param array  $data
     */
    static function setConfig($name, $data)
    {
    	$rec = self::fetch("#name = '{$name}'");
    	if(!$rec) {
    		$rec = new stdClass();
    		$rec->name = $name;
    	}
    	
    	if($rec->configData) { 
    		$exData = unserialize($rec->configData);
    	} else {
    		$exData = array();
    	}
    	
    	if (count($data)) {
    		foreach($data as $key => $value) {
                $exData[$key] = $value;
    		}
    	}
    	
    	$rec->configData = serialize($exData);
    	
    	return self::save($rec);   	
    }

    
    /**
     * Функция за преобразуване на стринга в константите в масив
     * 
     * @param string $conf - Данните, които ще се преобразуват
     * 
     * @return array $resArr - Масив с дефинираните константи
     */
    static function toArray($conf)
    {
        // Ако е масив
        if (is_array($conf)) {
            
            return $conf;
        }
        
        // Ако е празен стринг
        if (empty($conf)) {
            
            return array();
        }
        
        // Масив с всички стойности
        $cArr = explode(',', $conf);
        
        // Обхождаме масива
        foreach ($cArr as $conf) {
            
            // Изчистваме празните интервали
            $conf = trim($conf);
            
            // Ако стринга не е празен
            if($conf !== '') {

                // Добавяме в масива
                $resArr[$conf] = $conf;
            }
        }
        
        return $resArr;
    }
   
    
    /**
     * Променяме Списъчния изглед на пакетите
     */
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	$res = new ET(getFileContent("core/tpl/ListPack.shtml"));
    	$blockTpl = $res->getBlock('ROW');
    	
    	foreach ($data->rows as $row) {
    		$rowTpl = clone($blockTpl);
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
    	
    	return FALSE; 
    }
}
