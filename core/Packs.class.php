<?php



/**
 * Клас 'core_Packs' - Управление на пакети
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Packs extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Управление на пакети';
    
    
    /**
     * 
     */
    public $canAdd = 'no_one';
    
    
    /**
     * 
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да инсталира?
     */
    public $canInstall = 'admin';
    
    
    /**
     * Кои може да деинсталира?
     */
    public $canDeinstall = 'admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin';
	
	
    /**
     * По колко пакета да показва на страница
     */
    public $listItemsPerPage = 24;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_SystemWrapper, plg_Search, plg_State';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'name, info, startCtr';
    
    
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
        
        $this->FLD('state', 'enum(active=Инсталирани, draft=Неинсталирани, closed=Деактивирани, hidden=Без инсталатор, deprecated=За изтриване)', 'caption=Състояние,column=none,input=none,notNull,hint=Състояние на пакетите');
        
        // Съхранение на данните за конфигурацията
        $this->FLD('configData', 'text', 'caption=Конфигурация->Данни,input=none,column=none');

        $this->setDbUnique('name');
    }
    
    
    /**
     * Дали пакета е инсталиран
     * 
     * @param string $name
     * 
     * @return id|FALSE
     */
    static function isInstalled($name, $rightNow = FALSE)
    {
        static $isInstalled = array();
        
        $name = trim(strtolower($name));
        
        // Дали в момента не се инсталира?
        if($rightNow) {
            if($this->alreadySetup[$name . TRUE] || $this->alreadySetup[$name . TRUE]) {

                return TRUE;
            }
        }

        if (!isset($isInstalled[$name])) {

            
            $rec = static::fetch(array("#name = '[#1#]'", $name));
            
            if ($rec && $rec->state == 'active') {
                $isInstalled[$name] = $rec->id;
            } else {
                $isInstalled[$name] = FALSE;
            }
        }
        
        return $isInstalled[$name];
    }
    
    
    /**
     * Начална точка за инсталиране на пакети
     */
    function act_Install()
    {
        $this->requireRightFor('install');
        
        $pack = Request::get('pack', 'identifier');
        
        if (!$pack) error('@Missing pack name.');
        
        $haveRoleDebug = haveRole('debug');
        
        $res = $this->setupPack($pack, 0, TRUE, TRUE, $haveRoleDebug);
        
        $pack = strtolower($pack);
        $rec = $this->fetch(array("LOWER(#name) = '[#1#]'", $pack));
        $this->logWrite('Инсталиране на пакета', $rec->id);
        
        if ($haveRoleDebug) {
            
            return $this->renderWrapping($res);
        }
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        return new Redirect($retUrl, $res);
    }
    
    
    /**
     * Деинсталира пакет от системата
     * 
     * @param string $pack - името на пакета, който ще се деинсталира
     * @return string $res - резултата
     */
    public function deinstall($pack)
    {
        $delete = FALSE;
        
    	if (!$pack) error('@Липсващ пакет', $pack);
    	
    	if (!($rec = $this->fetch(array("#name = '[#1#]'", $pack)))) {
    		error('@Този пакет не е инсталиран', $pack);
    	}
    	
		$cls = $pack . "_Setup";
	
		if (cls::load($cls, TRUE)) {
	        
		    if ($rec->deinstall != 'yes') {
		        error('@Този пакет не може да бъде премахнат', $pack);
		    }
		    
			$setup = cls::get($cls);
	
			if (!method_exists($setup, 'deinstall')) {
				$res = "<div>Пакета '{$pack}' няма деинсталатор.</div>";
			} else {
				$res = (string)$setup->deinstall();
			}
		} else {
		    $delete = TRUE;
			$res = "<div class='debug-error'>Липсва кода на пакета '{$pack}'</div>";
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
    	
    	if ($delete) {
    	    $this->delete($rec->id);
    	    
    	    $res .= "<div>Успешно премахване на пакета '{$pack}'.</div>";
    	} else {
    	    $rec->state = 'closed';
    	    $this->save($rec, 'state');
    	    
    	    $res .= "<div>Успешно деактивиране на пакета '{$pack}'.</div>";
    	}
    	
    	return $res;
    }
    
    
    /**
     * Деинсталиране на пакет
     */
    function act_Deinstall()
    {
        $this->requireRightFor('deinstall');
        
        $pack = Request::get('pack', 'identifier');
        
        $res = $this->deinstall($pack);
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        $pack = strtolower($pack);
        $rec = $this->fetch(array("LOWER(#name) = '[#1#]'", $pack));
        $this->logWrite('Деинсталиране на пакета', $rec->id);
        
        return new Redirect($retUrl, $res);
    }
    


    public function act_InvalidateMigrations()
    {
        $data = self::getConfig('core')->_data;
        
        $migrations = $nonValid = array();


        $form = cls::get('core_Form');
        $form->FLD('migrations', 'set()', 'caption=Миграции->Успешни');
        $form->FLD('nonValid', 'set()', 'caption=Миграции->Невалидни');

        $rec = $form->input();
        
        $retUrl = getRetUrl();
         
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($form->isSubmitted()) {    

            $inv = arr::make($rec->migrations);
            foreach($inv as $key) {
                $migName = 'migration_' . $key;
                $data[$migName] = FALSE;
            }
            $inv = arr::make($rec->nonValid);
            foreach($inv as $key) {
                $migName = 'migration_' . $key;
                $data[$migName] = TRUE;
            }
      
            self::setConfig('core', $data);
        }
        
        foreach($data as $key => $true) {
            if(substr($key, 0, 10) == 'migration_') {
                $key = substr($key, 10);
                if($true) {
                    $migrations[$key] = $key;
                } else {
                    $nonValid[$key] = $key;
                }
            }
        }
        
        if(count($migrations)) {
            $form->setSuggestions('migrations', $migrations);
        } else {
            $form->setField('migrations', 'input=none');
        }
        
        if(count($nonValid)) {
            $form->setSuggestions('nonValid', $nonValid);
        } else {
            $form->setField('nonValid', 'input=none');
        }

        if(count($migrations) || count($nonValid)) {
            $form->toolbar->addSbBtn('Инвалидирай');
        } else {
            $form->info = "Все още няма минали миграции";
        }

        $form->title = 'Инвалидиране на избраните миграции';

        $form->toolbar->addBtn('Отказ', $retUrl);
        
        
        $res = $form->renderHtml();
        
        $this->currentTab = 'Пакети->Миграции';

        return $this->renderWrapping($res, $form);


    }
    
    /**
     * Връща масив с имената на всички инсталирани пакети
     * 
     * @return array
     */
    public static function getInstalledPacksNamesArr()
    {
        $resArr = array();
        
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            
            $resArr[$rec->name] = $rec->name;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с имената на всички активирани пакети
     * 
     * @return array
     */
    public static function getUsedPacksNamesArr()
    {
        $resArr = array();
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        $query->orWhere("#state = 'hidden'");
        while ($rec = $query->fetch()) {
            
            $resArr[$rec->name] = $rec->name;
        }
        
        return $resArr;
    }
    
    
    /**
     * Вкарва всички неинстлирани пакети
     */
    function loadSetupData()
    {
        $packsName = $this->getAllPacksNamesArr();
        
        $installedPacksName = self::getInstalledPacksNamesArr();
        
        // Изтриваме премахнатите пакети
        $removedPacksArr = array_diff($installedPacksName, $packsName);
        foreach ((array)$removedPacksArr as $packName) {
            $this->deinstall($packName);
        }
        
        foreach ($packsName as $pack => $desc) {
            
            $setupName = $pack . '_Setup';
            
            if (!cls::load($setupName, TRUE)) continue;
            
            $setup = cls::get($setupName);
            
            $rec = $this->fetch(array("#name = '[#1#]'", $pack));
            
            if (!is_object($rec)) {
                $rec = new stdClass();
                $rec->name = $pack;
                $rec->deinstall = 'yes';
            }
            
            $rec->info = $setup->info;
            $rec->version = $setup->version;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            
            if ($setup->deprecated) {
                if ($rec->state != 'deprecated' && $rec->id) {
                    $this->deinstall($pack);
                }
                
                $rec->state = 'deprecated';
            } else if ($setup->noInstall) {
                $rec->state = 'hidden';
            } else {
                if ($rec->state != 'active') {
                    $rec->state = 'draft';
                }
            }
            
            self::save($rec);
        }
    }
    
    
    /**
     * Връща всички не-инсталирани пакети
     * 
     * @return array
     */
    function getAllPacksNamesArr()
    {
        $opt = array();
        $path = EF_APP_PATH . "/core/Setup.class.php";
        
        if(file_exists($path)) {
            $opt['core'] = 'core';
        }
        
        $appDirs = $this->getSubDirs(EF_APP_PATH);
        
        if (defined('EF_PRIVATE_PATH')) {
            $privateDirs = $this->getSubDirs(EF_PRIVATE_PATH);
        }
        
        if (count($appDirs)) {
            foreach ($appDirs as $dir => $dummy) {
                $path = EF_APP_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    $opt[$dir] =  $dir;
                }
            }
        }
        
        if (count($privateDirs)) {
            foreach($privateDirs as $dir => $dummy) {
                $path = EF_PRIVATE_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                // Ако този пакет не е инсталиран - 
                // добавяме го като опция за инсталиране
                if (file_exists($path)) {
                    $opt[$dir] =  $dir;
                }
            }
        }
        
        return $opt;
    }
    
    
    /**
     * Връща всички не-инсталирани пакети
     * 
     * @return array
     */
    function getNonInstalledPacks()
    {
        $opt = array();
        if (!$this->fetch("#name = 'core'")) {
            $path = EF_APP_PATH . "/core/Setup.class.php";
            
            if(file_exists($path)) {
                $opt['core'] = 'Ядро на EF "core"';
            }
        }
        
        $appDirs = $this->getSubDirs(EF_APP_PATH);
        
        if (defined('EF_PRIVATE_PATH')) {
            $privateDirs = $this->getSubDirs(EF_PRIVATE_PATH);
        }
        
        if (count($appDirs)) {
            foreach ($appDirs as $dir => $dummy) {
                $path = EF_APP_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if (file_exists($path)) {
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] =  $dir .' - компонент на приложението';
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
     * 
     * @param object $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $stateField = $data->listFilter->getField('state');
        unset($stateField->type->options['deprecated']);
        $stateField->type->options = array('all' => 'Всички') + $stateField->type->options;
        $stateField->autoFilter = 'autoFilter';
        
        $data->listFilter->setDefault('state', 'all');
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
                
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = "{$mvc->searchInputField}, state";
        
        $data->listFilter->input(NULL, 'silent');
        
        if($filter = $data->listFilter->rec) {
            $isAll = FALSE;
            if (($filter->state != 'all') && $filter->state) {
                $data->query->where(array("#state = '[#1#]'", $filter->state));
            } else {
                $isAll = TRUE;
            }
            
            if ($filter->state != 'hidden') {
                if (!$filter->search && $isAll) {
                    $data->query->where("#state != 'hidden'");
                }
            }
        }
        
        $data->query->orderBy("#name");
        $data->query->where("#state != 'deprecated'");
    }
    
    
    /**
     * 
     * 
     * @param core_Packs $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Обновяване на системата', array("core_Packs", "systemUpdate"), 'ef_icon = img/16/download.png, title=Сваляне на най-новия код и инициализиране на системата, class=system-update-btn');
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
                // Не може да се отвори директорията
                error('Не може да се отвори директорията', $dir, $dh);
            }
        }
        
        return $dirs;
    }
    
    
    /**
     * След конвертирането на един ред от вътрешно към вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->STATE_CLASS = trim($row->STATE_CLASS);
        
        $imageUrl = sbf("img/100/default.png","");
        
        $filePath = getFullPath("{$rec->name}/icon.png");

        if ($filePath){
       		$imageUrl = sbf("{$rec->name}/icon.png","");
       	}
       	
       	$row->img = ht::createElement("img", array('src' => $imageUrl, 'alt' => 'icon-' . $rec->name));
       	
        $row->name = new ET("<b>" . $row->name . "</b>");
        // $row->name->append(' ' . str_replace(',', '.', $row->version));
        
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
            	
            	if ($makeLink && ($rec->state != 'draft') && ($rec->state != 'hidden')) {
            		$row->name = ht::createLink($row->name, array($rec->startCtr, $rec->startAct), NULL, "class=pack-title");
            		$row->img = ht::createLink($row->img, array($rec->startCtr, $rec->startAct));
            	}
    	    } catch(ErrorException $e) {
    	        // Възможно е да липсва кода на пакета
    	    }
        }
        
        $row->deinstall = "";
        
        try {
            $conf = self::getConfig($rec->name);
        } catch (core_exception_Expect $e) {
            $row->install = 'Липсва кода на пакета!';
            $row->STATE_CLASS = 'missing';
            
        	$row->deinstall = ht::createLink('', array($mvc, 'deinstall', 'pack' => $rec->name, 'ret_url' => TRUE), 'Наистина ли искате да изтриете пакета?', array('id'=>$rec->name."-deinstall", 'class'=>'deinstall-pack', 'ef_icon' => 'img/16/reject.png', 'title'=>'Изтриване на пакета'));
            $row->name .= $row->deinstall;
        	$row->name .= "<div class=\"pack-info\">{$row->info}</div>";
        	
            return;
        }
        
        $installUrl = array($mvc, 'install', 'pack' => $rec->name, 'status' => 'initialize', 'ret_url' => TRUE);
        
        if ($rec->state == 'active') {
            
            if ($rec->deinstall == 'yes') {
            	$row->deinstall = ht::createLink('', array($mvc, 'deinstall', 'pack' => $rec->name, 'ret_url' => TRUE), 'Наистина ли искате да деактивирате пакета?', array('id'=>$rec->name."-deinstall", 'class'=>'deinstall-pack', 'ef_icon' => 'img/16/reject.png', 'title'=>'Деактивиране на пакета'));
            }
            
            $row->install = ht::createLink(tr("Инициализиране"), $installUrl, NULL, array('id'=>$rec->name."-install", 'title'=>'Обновяване на пакета'));
        } elseif ($rec->state == 'draft') {
            $installUrl['status'] = 'install';
            $row->install = ht::createLink(tr("Инсталирай"), $installUrl, "Наистина ли искате да инсталирате пакета?", array('id'=>$rec->name."-install", 'title'=>'Начално инсталиране на пакета'));
        } elseif ($rec->state == 'closed') {
            $installUrl['status'] = 'activate';
            $row->install = ht::createLink(tr("Активирай"), $installUrl, "Наистина ли искате да активирате пакета?", array('id'=>$rec->name."-install", 'title'=>'Активиране и инициализиране на пакета'));
        }
        
        if ($rec->state == 'active' || $rec->state == 'hidden') {
            
            $cls = $rec->name . "_Setup";
            $row->config = '';
            
            if ($conf->getConstCnt()) {
                $row->config = ht::createLink(tr("Настройки"), array($mvc, 'config', 'pack' => $rec->name, 'ret_url' => TRUE), NULL, array('id'=>$rec->name."-config", 'title'=>'Конфигуриране на пакета'));
            }
            
            if (cls::load($cls, TRUE)) {
                $setup = cls::get($cls);
                if(method_exists($setup, 'checkConfig') && ($errMsg = $setup->checkConfig())) {
                    $row->config = ht::createHint($row->config, $errMsg, 'error');
                }
            }
        }
        
        $row->name .= $row->deinstall;
        $row->name .= "<div class=\"pack-info\">{$row->info}</div>";
        
        if ($conf->haveErrors()) {

            $row->ROW_ATTR['style'] = 'background-color:red';
        }
        
        if ($row->config && $row->install) {
            $row->configInstall = ' ';
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
        $this->logWrite('Сетъп на системата');
        
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
    function setupPack($pack, $version = 0, $force = TRUE, $loadData = FALSE, $verbose = TRUE)
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
            do {
                $res = @file_put_contents(EF_SETUP_LOG_PATH, "<h2>Инсталиране на {$pack} ... <h2>", FILE_APPEND|LOCK_EX);
                if($res !== FALSE) break;
                usleep(1000);
            } while($i++ < 100);
        }
        
        // Проверка дали Setup класа съществува
        if (!cls::load($pack . "_Setup", TRUE)) {
            
            if ($verbose) {
                return "<h4>Невъзможност да се инсталира <span class=\"debug-error\">{$pack}</span>. " .
            		"Липсва <span class=\"debug-error\">Setup</span> клас.</h4>";
            } else {
                return "<span class='debug-error'>Грешка при инсталиране на пакета '{$pack}'.</span>";
            }
        }
        
        // Вземаме Setup класа, за дадения пакет
        $setup = cls::get($pack . '_Setup');
        
        // Ако има зависимости, проследяваме ги
        // Първо инсталираме зависимостите
        if ($setup->depends) {
            $depends = arr::make($setup->depends, TRUE);
            
            foreach($depends as $p => $v) {
                $res .= $this->setupPack($p, $v, FALSE, $loadData, $verbose);
            }
        }

        // Започваме самото инсталиране
        if ($setup->startCtr && !$setupFlag) {
            $res .= "<h2>Инициализиране на пакета \"<a href=\"" .
            toUrl(array($setup->startCtr, $setup->startAct)) . "\"><b>{$pack}</b></a>\"&nbsp;";
        } else {
            $res .= "<h2>Инициализиране на пакета \"<b>{$pack}</b>\"&nbsp;";
        }

        try {
            $conf = self::getConfig($pack);
            if($conf->getConstCnt() && !$setupFlag) {  
               $res .= ht::createBtn("Конфигуриране", array('core_Packs', 'config', 'pack' => $pack), NULL, NULL, 'class=btn-settings,title=Настройки на пакета');
            }
        } catch (core_exception_Expect $e) {
            // Не показваме буотона
        }

        $res .= '</h2>';
        
        $res .= "<ul>";
        
        // Единственото, което правим, когато версията, която инсталираме
        // е по-малка от изискваната, е да сигнализираме за този факт
        if ($version > 0 && $version > $setup->version) {
            $res .= "<li class='debug-error'>За пакета '{$pack}' се изисква версия [{$version}], " .
            "а наличната е [{$setup->version}]</li>";
        }
        
        // Ако инсталирането е форсирано 
        //   или този пакет не е инсталиран до сега 
        //   или инсталираната версия е различна спрямо тази
        // извършваме инсталационна процедура
        if (!$force) {
            $rec = $this->fetch("#name = '{$pack}'");
        }
        
        if ($force || empty($rec) || ($rec->version != $setup->version) || (!$force && $rec->state != 'active')) {
            
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
            
            if ($setup->isSystem) {
                $rec->deinstall = 'no';
            } else {
                $rec->deinstall = method_exists($setup, 'deinstall') ? 'yes' : 'no';
            }
            
            $rec->state = 'active';
            
            $this->save($rec);
        } else {
            $res .= "<li>Пропускаме, има налична инсталация</li>";
        }
        
        if (method_exists($setup, 'checkConfig')) {
            if ($checkRes = $setup->checkConfig()) {
                $res .= "<li style='color: red;'>" . $checkRes . '</li>';
            }
        }
        
        $res .= "</ul>";
        
        if ($setupFlag) {
			// Махаме <h2> тага на заглавието
			$res = substr($res, strpos($res, "</h2>"), strlen($res));

            do {
                $res = @file_put_contents(EF_SETUP_LOG_PATH, $res, FILE_APPEND|LOCK_EX);
                if($res !== FALSE) break;
                usleep(1000);
            } while($i++ < 100);
			
			unset($res);
        }
        
        DEBUG::stopTimer("Инициализация на пакет '{$pack}'");
        
        if ($setupFlag && $pack == 'bgerp') {
            // в setup-a очакваме резултат
            return;
        }
        
        if ($verbose) {
            
            return $res;
        } else {
            
            return "<div>Успешна инициализация на пакета '{$pack}'</div>";
        }
    }


	/**
     * Стартира обновяване на системата през УРЛ
     */
    function act_systemUpdate()
    {
		requireRole('admin');
		
		self::logRead('Обновяване на системата');
		
		return self::systemUpdate();
    }

    
    /**
     * Стартира обновяване на системата
     */
    function systemUpdate()
	{
		$SetupKey = setupKey();
		//$SetupKey = md5(BGERP_SETUP_KEY . round(time()/10));
		
		return new Redirect(array("core_Packs", "systemUpdate", SetupKey=>$SetupKey, "step"=>2, "bgerp"=>1));
	}    


    /****************************************************************************************
     *                                                                                      *
     *     Функции за работа с конфигурацията                                               *
     *                                                                                      *
     ****************************************************************************************/

    /**
     * Връща конфигурационните данни за даден пакет
     */
    static function getConfig($packName) 
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
               
        $conf = cls::get('core_ObjectConfiguration', array($setup->getConfigDescription(), $rec->configData));
    
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
                    
                    $currLgHeader = $key . '_EN';
                    
                    // Ако няма данни за текущия език използваме на английски
                    $value = $packConfig->$currLgHeader;
                }
            } catch (core_exception_Expect $e) {
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
     * Може и да се форсира
     * 
     * @param string $pack
     * @param string $dataKey
     * @param string $dataVal
     * 
     * @return boolean
     */
    static function setIfNotConfigKey($pack, $dataKey, $dataVal, $force = FALSE)
    {
        // Вземаме конфига
        $confWebkit = core_Packs::getConfig($pack);
        
        $oldVal = core_Packs::getConfigKey($confWebkit, $dataKey);
        
        // Ако не е избрана нищо
        if (!isset($oldVal) || $force) {
            
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
            error("@Липсваш клас", $cls);
        }
        
        if (!($description = $setup->getConfigDescription())) {
            error("@Пакета няма нищо за конфигуриране", $packName);
        }
        
        if ($rec->configData) {
            $data = unserialize($rec->configData);
        } else {
            $data = array();
        }
 
        $form = cls::get('core_Form');

        $form->title = "Настройки на пакета|* <b style='color:green;'>{$packName}</b>";
 
        foreach ($description as $field => $arguments) {
            $type   = $arguments[0];
            $params = arr::combine($arguments[1], $arguments[2]);
            
            // Полето ще се въвежда
            $params['input'] = 'input';
            
            // Ако не е зададено, заглавието на полето е неговото име
            setIfNot($params['caption'], '|*' . $field);

            $typeInst = core_Type::getByName($type);

            if (defined($field)) {
                Mode::push('text', 'plain');
                $defVal = $typeInst->toVerbal(constant($field));
                Mode::pop('text');
                $params['hint'] .= ($params['hint'] ? "\n" : '') . 'Стойност по подразбиране|*: "' . $defVal . '"';
            }

            $form->FNC($field, $type, $params);
          
            if (($data[$field] || $data[$field] === (double) 0 || $data[$field] === (int) 0) && 
                (!defined($field) || ($data[$field] != constant($field)))) { 
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
                    
                    $fType = $form->getFieldType($field, FALSE);
                    
                    // Да може да се зададе автоматичната стойност
                    if ((($fType instanceof type_Class) || ($fType instanceof type_Enum) || ($fType instanceof color_Type)) 
                        && ($fType->params['allowEmpty']) && ($form->rec->{$field} === NULL))  {
                        
                        $data[$field] = NULL;
                    } elseif ($form->rec->{$field} !== NULL) {
                        $data[$field] = $form->rec->{$field};
                    }
                } else {
                    $data[$field] = '';
                }
            }
      
            $id = self::setConfig($packName, $data);
        
            // Правим запис в лога
            $this->logWrite("Промяна на конфигурацията на пакет", $rec->id);
            
            $msg = 'Конфигурацията е записана';
            
            // Ако е инсталиран, обновяваме пакета
            if (self::isInstalled($packName)) {
                $setupClass = $packName . '_Setup';
                if($setupClass::INIT_AFTER_CONFIG) {
                    $msg .= '<br>' . self::setupPack($packName, $rec->version, TRUE, TRUE, FALSE);
                }
            }
            
            return new Redirect($retUrl, $msg);
        }
        
        $form->toolbar->addSbBtn('Запис', 'default', 'ef_icon = img/16/disk.png, title=Съхраняване на настройките');

        // Добавяне на допълнителни системни действия
        if (count($setup->systemActions)) {
            foreach ($setup->systemActions as $sysActArr) {
                
                $form->toolbar->addBtn($sysActArr['title'], $sysActArr['url'], $sysActArr['params']);
            }
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl,  'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        if (method_exists($setup, 'checkConfig') && ($errMsg = $setup->checkConfig())) {
            $errMsg = tr($errMsg);
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
        
        $resArr = array();
        
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
        if ($data->rows) {
            $res = new ET(getFileContent("core/tpl/ListPack.shtml"));
        	$blockTpl = $res->getBlock('ROW');
        	
        	foreach ($data->rows as $row) {
        		$rowTpl = clone($blockTpl);
        		$rowTpl->placeObject($row);
        		$rowTpl->removeBlocks();
        		$rowTpl->append2master();
        	}
        } else {
            $res = new ET('Няма пакети');
        }
    	
    	return FALSE; 
    }

}
