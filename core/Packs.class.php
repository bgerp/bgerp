<?php


/**
 * Клас 'core_Packs' - Управление на пакети
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_Packs extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Управление на пакети';
    
    
    public $canAdd = 'no_one';
    
    
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
    public $searchFields = 'name, info, startCtr';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'identifier(32)', 'caption=Пакет,notNull');
        $this->FLD('version', 'double(decimals=2)', 'caption=Версия,input=none');
        $this->FLD('info', 'html(128)', 'caption=Информация,input=none');
        $this->FLD('startCtr', 'varchar(64)', 'caption=Стартов->Мениджър,input=none,column=none');
        $this->FLD('startAct', 'varchar(64)', 'caption=Стартов->Контролер,input=none,column=none');
        
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
     * @return int|FALSE
     */
    public static function isInstalled($name, $rightNow = false)
    {
        static $isInstalled = array();

        $me = cls::get(get_called_class());

        $name = trim(strtolower($name));
        
        // Дали в момента не се инсталира?
        if ($rightNow) {
            if ($me->alreadySetup[$name . true] || $me->alreadySetup[$name . true]) {
                
                return true;
            }
        }
        
        if (!isset($isInstalled[$name])) {
            $rec = static::fetch(array("#name = '[#1#]'", $name));
            
            if ($rec && $rec->state == 'active') {
                $isInstalled[$name] = $rec->id;
            } else {
                $isInstalled[$name] = false;
            }
        }
        
        return $isInstalled[$name];
    }
    
    
    /**
     * Начална точка за инсталиране на пакети
     */
    public function act_Install()
    {
        $this->requireRightFor('install');
        
        $pack = Request::get('pack', 'identifier');
        
        if (!$pack) {
            error('@Missing pack name.');
        }
        
        $haveRoleDebug = haveRole('debug');
        
        $res = $this->setupPack($pack, 0, true, true, $haveRoleDebug);
        $res .= core_Classes::rebuild();
        $res .= core_Cron::cleanRecords();

        core_Cache::eraseFull();

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
     *
     * @return string $res - резултата
     */
    public function deinstall($pack)
    {
        if (!$pack) {
            error('@Липсващ пакет', $pack);
        }
        
        if (!($rec = $this->fetch(array("#name = '[#1#]'", $pack)))) {
            error('@Този пакет не е инсталиран', $pack);
        }
        
        $cls = $pack . '_Setup';
        
        if (cls::load($cls, true)) {
            $setup = cls::get($cls);
            if (!$setup->canDeinstall()) {
                error('@Този пакет не може да бъде премахнат', $pack);
            }
            $res = (string) $setup->deinstall();
            $rec->state = 'closed';
            $this->save($rec, 'state');
            $res .= "<li class='notice'>Успешно деактивиране на пакета '{$pack}'.</li>";
        } else {
            $res = "<div class='debug-error'>Липсва кода на пакета '{$pack}'</div>";
            
            // Изтриване на пакета от менюто
            $res = bgerp_Menu::remove($pack);
            
            // Премахване от core_Interfaces
            $res .= core_Interfaces::deinstallPack($pack);
            
            // Скриване от core_Classes
            $res .= core_Classes::deinstallPack($pack);
            
            // Премахване от core_Cron
            $res .= core_Cron::deinstallPack($pack);
            
            // Премахване от core_Plugins
            $res .= core_Plugins::deinstallPack($pack);
            
            $this->delete($rec->id);
            $res .= "<li class='debug-error'>Успешно премахване на пакета '{$pack}'.</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Деинсталиране на пакет
     */
    public function act_Deinstall()
    {
        $this->requireRightFor('deinstall');
        
        $pack = Request::get('pack', 'identifier');
        
        $res = $this->deinstall($pack);

        core_Cache::eraseFull();
        
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
        requireRole('admin');
        
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
            foreach ($inv as $key) {
                $migName = 'migration_' . $key;
                $data[$migName] = false;
            }
            $inv = arr::make($rec->nonValid);
            foreach ($inv as $key) {
                $migName = 'migration_' . $key;
                $data[$migName] = true;
            }
            
            self::setConfig('core', $data);
        }
        
        foreach ($data as $key => $true) {
            if (substr($key, 0, 10) == 'migration_') {
                $key = substr($key, 10);
                if ($true) {
                    $migrations[$key] = $key;
                } else {
                    $nonValid[$key] = $key;
                }
            }
        }
        
        if (count($migrations)) {
            $form->setSuggestions('migrations', $migrations);
        } else {
            $form->setField('migrations', 'input=none');
        }
        
        if (count($nonValid)) {
            $form->setSuggestions('nonValid', $nonValid);
        } else {
            $form->setField('nonValid', 'input=none');
        }
        
        if (count($migrations) || count($nonValid)) {
            $form->toolbar->addSbBtn('Инвалидирай');
        } else {
            $form->info = 'Все още няма минали миграции';
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
    public function loadSetupData()
    {
        $packsName = $this->getAllPacksNamesArr();
        
        $installedPacksName = self::getInstalledPacksNamesArr();
        
        // Изтриваме премахнатите пакети
        $removedPacksArr = array_diff($installedPacksName, $packsName);
        foreach ((array) $removedPacksArr as $packName) {
            $res .= $this->deinstall($packName);
        }
        
        foreach ($packsName as $pack => $desc) {
            $setupName = $pack . '_Setup';
            
            if (!cls::load($setupName, true)) {
                continue;
            }
            
            $setup = cls::get($setupName);
            
            $rec = $this->fetch(array("#name = '[#1#]'", $pack));
            
            if (!is_object($rec)) {
                $rec = new stdClass();
                $rec->name = $pack;
            }
            
            $rec->info = $setup->info;
            $rec->version = $setup->version;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            
            if ($setup->deprecated) {
                if ($rec->state != 'deprecated' && $rec->id) {
                    $res .= $this->deinstall($pack);
                }
                
                $rec->state = 'deprecated';
            } elseif ($setup->noInstall) {
                $rec->state = 'hidden';
            } else {
                if ($rec->state != 'active') {
                    $rec->state = 'draft';
                }
            }
            
            $res .= "<li class='debug-info'>Заредена/обновена информацията за {$rec->name}</li>";
            
            self::save($rec);
        }
        
        return $res;
    }
    
    
    /**
     * Връща всички не-инсталирани пакети
     *
     * @return array
     */
    public function getAllPacksNamesArr()
    {
        $opt = array();
        $path = EF_APP_PATH . '/core/Setup.class.php';
        
        if (file_exists($path)) {
            $opt['core'] = 'core';
        }
        
        $reposArr = core_App::getRepos();
        foreach (array_keys($reposArr) as $dir) {
            $appDirs = $this->getSubDirs($dir);
            if (count($appDirs)) {
                foreach ($appDirs as $subDir => $dummy) {
                    $path = rtrim($dir, '/\\') . '/' . $subDir . '/' . 'Setup.class.php';
                    if (file_exists($path)) {
                        // Ако този пакет не е инсталиран -
                        // добавяме го като опция за инсталиране
                        $opt[$subDir] = $subDir;
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
    public static function on_AfterPrepareListFilter($mvc, &$data)
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
        
        $data->listFilter->input(null, 'silent');
        
        if ($filter = $data->listFilter->rec) {
            $isAll = false;
            if (($filter->state != 'all') && $filter->state) {
                $data->query->where(array("#state = '[#1#]'", $filter->state));
            } else {
                $isAll = true;
            }
            
            if ($filter->state != 'hidden') {
                if (!$filter->search && $isAll) {
                    $data->query->where("#state != 'hidden'");
                }
            }
        }
        
        $data->query->orderBy('#name');
        $data->query->where("#state != 'deprecated'");
    }
    
    
    /**
     *
     *
     * @param core_Packs $mvc
     * @param object     $res
     * @param object     $data
     */
    public function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn(
                        'Обновяване на системата',
                        array('core_Packs', 'systemUpdate'),
                        'ef_icon = img/16/download.png, title=Сваляне на най-новия код и инициализиране на системата, class=system-update-btn'
                        );
    }
    
    
    /**
     * Връща съдържанието на кеша за посочения обект
     */
    public function getSubDirs($dir)
    {
        $dirs = array();
        
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while ($file = readdir($dh)) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    
                    if (is_dir($dir . '/' . $file)) {
                        $dirs[$file] = true;
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
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->STATE_CLASS = trim($row->STATE_CLASS);
        
        $imageUrl = sbf('img/100/default.png', '');
        
        $filePath = getFullPath("{$rec->name}/icon.png");
        
        if ($filePath) {
            $imageUrl = sbf("{$rec->name}/icon.png", '');
        }
        
        $row->img = ht::createElement('img', array('src' => $imageUrl, 'alt' => 'icon-' . $rec->name));
        
        $row->name = new ET('<b>' . $row->name . '</b>');
        
        if ($rec->startCtr) {
            try {
                $makeLink = false;
                
                $startCtrMvc = cls::get($rec->startCtr);
                
                // Слага се линк към пакета, само ако потребителя има права за него
                if (method_exists($startCtrMvc, 'haveRightFor')) {
                    
                    //@TODO да се проверява имали права за default екшъна а не конкретно list
                    if ($startCtrMvc->haveRightFor('list')) {
                        $makeLink = true;
                    }
                } else {
                    
                    // Ако няма изискване за права се слага линк към пакета
                    $makeLink = true;
                }
                
                if ($makeLink && ($rec->state != 'draft') && ($rec->state != 'hidden')) {
                    $row->name = ht::createLink($row->name, array($rec->startCtr, $rec->startAct), null, 'class=pack-title');
                    $row->img = ht::createLink($row->img, array($rec->startCtr, $rec->startAct));
                }
            } catch (ErrorException $e) {
                // Възможно е да липсва кода на пакета
            }
        }
        
        $row->deinstall = '';
        
        try {
            $conf = self::getConfig($rec->name);
        } catch (core_exception_Expect $e) {
            $row->install = 'Липсва кода на пакета!';
            $row->STATE_CLASS = 'missing';
            
            $row->deinstall = ht::createLink('', array($mvc, 'deinstall', 'pack' => $rec->name, 'ret_url' => true), 'Наистина ли искате да изтриете пакета?', array('id' => $rec->name.'-deinstall', 'class' => 'deinstall-pack', 'ef_icon' => 'img/16/reject.png', 'title' => 'Изтриване на пакета'));
            $row->name .= $row->deinstall;
            $row->name .= "<div class=\"pack-info\">{$row->info}</div>";
            
            return;
        }
        
        $installUrl = array($mvc, 'install', 'pack' => $rec->name, 'status' => 'initialize', 'ret_url' => true);
        
        $canDeinstall = true;
        $setupName = $rec->name . '_Setup';
        if (cls::load($setupName, true)) {
            $setup = cls::get($setupName);
            $canDeinstall = $setup->canDeinstall();
        }
        
        if ($rec->state == 'active') {
            if ($canDeinstall) {
                $row->deinstall = ht::createLink('', array($mvc, 'deinstall', 'pack' => $rec->name, 'ret_url' => true), 'Наистина ли искате да деактивирате пакета?', array('id' => $rec->name.'-deinstall', 'class' => 'deinstall-pack', 'ef_icon' => 'img/16/reject.png', 'title' => 'Деактивиране на пакета'));
            } else {
                $row->deinstall = ht::createHint('', 'Пакетът не може да бъде де-инсталиран, защото има системни функции.', 'notice', false, '', 'style=float:right;');
            }
            
            $row->install = ht::createLink(tr('Инициализиране'), $installUrl, null, array('id' => $rec->name.'-install', 'title' => 'Обновяване на пакета'));
        } elseif ($rec->state == 'draft') {
            $installUrl['status'] = 'install';
            $row->install = ht::createLink(tr('Инсталирай'), $installUrl, 'Наистина ли искате да инсталирате пакета?', array('id' => $rec->name.'-install', 'title' => 'Начално инсталиране на пакета'));
        } elseif ($rec->state == 'closed') {
            $installUrl['status'] = 'activate';
            $row->install = ht::createLink(tr('Активирай'), $installUrl, 'Наистина ли искате да активирате пакета?', array('id' => $rec->name.'-install', 'title' => 'Активиране и инициализиране на пакета'));
        }
        
        if ($rec->state == 'active' || $rec->state == 'hidden') {
            $cls = $rec->name . '_Setup';
            $row->config = '';
            
            if ($conf->getConstCnt()) {
                $row->config = ht::createLink(tr('Настройки'), array($mvc, 'config', 'pack' => $rec->name, 'ret_url' => true), null, array('id' => $rec->name.'-config', 'title' => 'Конфигуриране на пакета'));
            }
            
            if (cls::load($cls, true)) {
                $setup = cls::get($cls);
                if (method_exists($setup, 'checkConfig') && ($errMsg = $setup->checkConfig())) {
                    $row->config = ht::createLink(tr('Настройки'), array($mvc, 'config', 'pack' => $rec->name, 'ret_url' => true), null, array('id' => $rec->name.'-config', 'style' => 'background:red;color:white'));
                    $row->config = ht::createHint("<span style='color:red;'>" . $row->config . "</b>", $errMsg, 'noicon');
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
     * Проверява дали сетъпваме на празна база
     *
     * (1) дали таблицата на този модел съществува
     * (2) дали е установен пакета 'core'
     * (3) дали е установен пакета EF_APP_CODE_NAME
     * което и да не е изпълнено - предизвиква начално установяване
     *
     * @return bool
     */
    public static function isFirstSetup()
    {
        static $res;
        
        $me = cls::get('core_Packs');
        
        if (isset($res)) {
            
            return $res;
        }
        
        if (!$me->db->tableExists($me->dbTableName)) {
            $res = true;
        } elseif (!$me->fetch("#name = 'core'") || (!$me->fetch("#name = '" . EF_APP_CODE_NAME . "'") && cls::load(EF_APP_CODE_NAME . '_Setup', true))) {
            $res = true;
        } else {
            $res = false;
        }
        
        return $res;
    }
    
    
    /**
     * Прави начално установяване на посочения пакет. Ако в
     * Setup-а на пакета е указано, че той зависи от други пакети
     * (var $depends = ... ), прави се опит и те да се установят
     */
    public function setupPack($pack, $version = 0, $force = true, $loadData = false, $verbose = true)
    {
        // Максиламно време за инсталиране на пакет
        set_time_limit(400);
        
        // Забраняваме кеша на кода
        ini_set('opcache.enable', false);
        
        static $f = 0;
        
        DEBUG::startTimer("Инсталиране на пакет '{$pack}'");
        
        
        // Имената на пакетите са винаги с малки букви
        $pack = strtolower($pack);
        
        // Предпазване срещу рекурсивно зацикляне
        if ($this->alreadySetup[$pack . $force]) {
            
            return;
        }
        
        // Отбелязваме, че на текущия хит, този пакет е установен
        $this->alreadySetup[$pack . $force] = true;
        
        global $setupFlag;
        
        // Ако е пуснат от сетъп-а записваме в Лог-а
        if ($setupFlag) {
            do {
                $res = @file_put_contents(EF_SETUP_LOG_PATH, "<h2>Инсталиране на {$pack} ... <h2>", FILE_APPEND | LOCK_EX);
                if ($res !== false) {
                    break;
                }
                usleep(1000);
            } while ($i++ < 100);
        }
        
        // Проверка дали Setup класа съществува
        if (!cls::load($pack . '_Setup', true)) {
            if ($verbose) {
                
                return "<h4>Невъзможност да се инсталира <span class=\"debug-error\">{$pack}</span>. " .
                'Липсва <span class="debug-error">Setup</span> клас.</h4>';
            }
            
            return "<span class='debug-error'>Грешка при инсталиране на пакета '{$pack}'.</span>";
        }
        
        // Вземаме Setup класа, за дадения пакет
        $setup = cls::get($pack . '_Setup');
        
        // Ако има зависимости, проследяваме ги
        // Първо инсталираме зависимостите
        if ($setup->depends) {
            $depends = arr::make($setup->depends, true);
            
            foreach ($depends as $p => $v) {
                $res .= $this->setupPack($p, $v, false, $loadData, $verbose);
            }
        }
        
        // Започваме самото инсталиране
        if ($setup->startCtr && !$setupFlag) {
            $res .= '<h2>Инициализиране на пакета "<a href="' .
                            toUrl(array($setup->startCtr, $setup->startAct)) . "\"><b>{$pack}</b></a>\"&nbsp;";
        } else {
            $res .= "<h2>Инициализиране на пакета \"<b>{$pack}</b>\"&nbsp;";
        }
        
        try {
            $conf = self::getConfig($pack);
            if ($conf->getConstCnt() && !$setupFlag) {
                $res .= ht::createBtn('Конфигуриране', array('core_Packs', 'config', 'pack' => $pack), null, null, 'class=btn-settings,title=Настройки на пакета');
            }
        } catch (core_exception_Expect $e) {
            // Не показваме буотона
        }
        
        $res .= '</h2>';
        
        $res .= '<ul>';
        
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
            if ($rec && ($rec->version != $setup->version)) {
                Request::push(array('Full' => 1), 'full');
            }
            
            // Правим началното установяване
            $res .= $setup->install();
            
            if ($loadData) {
                $res .= $setup->loadSetupData();
            }
            
            Request::pop('full');
            
            // Де-форсираме системния потребител
            core_Users::cancelSystemUser();
            
            $rec = $this->fetch("#name = '{$pack}'");
            
            // Правим запис на факта, че пакетът е инсталиран
            if (!is_object($rec)) {
                $rec = new stdClass();
            }
            $rec->name = $pack;
            $rec->version = $setup->version;
            $rec->info = $setup->info;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            $rec->state = 'active';
            
            $this->save($rec);
        } else {
            $res .= '<li>Пропускаме, има налична инсталация</li>';
        }
        
        if (method_exists($setup, 'checkConfig')) {
            if ($checkRes = $setup->checkConfig()) {
                $res .= "<li style='color: red;'>" . $checkRes . '</li>';
            }
        }
        
        $res .= '</ul>';
        
        if ($setupFlag) {
            // Махаме <h2> тага на заглавието
            $res = substr($res, strpos($res, '</h2>'), strlen($res));
            
            do {
                $res = @file_put_contents(EF_SETUP_LOG_PATH, $res, FILE_APPEND | LOCK_EX);
                if ($res !== false) {
                    break;
                }
                usleep(1000);
            } while ($i++ < 100);
            
            unset($res);
        }
        
        
        DEBUG::stopTimer("Инициализация на пакет '{$pack}'");
        
        if ($setupFlag && $pack == 'bgerp') {
            // в setup-a очакваме резултат
            return;
        }
        
        if ($verbose) {
            
            return $res;
        }
        
        return "<div>Успешна инициализация на пакета '{$pack}'</div>";
    }
    
    
    /**
     * Стартира обновяване на системата през УРЛ
     */
    public function act_systemUpdate()
    {
        requireRole('admin');
        
        self::logRead('Обновяване на системата');
        
        return self::systemUpdate();
    }
    
    
    /**
     * Стартира обновяване на системата
     */
    public function systemUpdate()
    {
        $SetupKey = setupKey();
        
        //$SetupKey = md5(BGERP_SETUP_KEY . round(time()/10));
        
        return new Redirect(array('core_Packs', 'systemUpdate', 'SetupKey' => $SetupKey, 'step' => 2, 'bgerp' => 1));
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *     Функции за работа с конфигурацията                                               *
     *                                                                                      *
     ****************************************************************************************/
    
    /**
     * Връща конфигурационните данни за даден пакет
     */
    public static function getConfig($packName)
    {
        $rec = static::fetch("#name = '{$packName}'");
        $setup = cls::get("{$packName}_Setup");
        
        // В Setup-a се очаква $configDescription в следната структура:
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
     * @param mixed  $packConfig - Инстанция на пакета или името на пакета
     * @param string $key        - Името на полето
     */
    public static function getConfigKey($packConfig, $key)
    {
        if (!is_object($packConfig)) {
            $packConfig = static::getConfig($packConfig);
        }
        
        return $packConfig->_data[$key];
    }
    
    
    /**
     * Връща стойността на конфига, в зависимост от езика
     *
     * @param mixed  $packConfig - Инстанция на пакета или името на пакета
     * @param string $key        - Името на полето
     *
     * @return string
     */
    public static function getConfigValue($packConfig, $key)
    {
        $value = null;
        
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
     * @return bool
     */
    public static function setIfNotConfigKey($pack, $dataKey, $dataVal, $force = false)
    {
        // Вземаме конфига
        $confWebkit = core_Packs::getConfig($pack);
        
        $oldVal = core_Packs::getConfigKey($confWebkit, $dataKey);
        
        // Ако не е избрана нищо
        if (!isset($oldVal) || $force) {
            $data[$dataKey] = $dataVal;
            
            // Добавяме в конфигурацията
            core_Packs::setConfig($pack, $data);
            
            return true;
        }
    }
    
    
    /**
     * Конфирурира даден пакет
     */
    public function act_Config()
    {
        requireRole('admin');
        
        expect($packName = Request::get('pack', 'identifier'));
        
        $rec = static::fetch("#name = '{$packName}'");
        
        $cls = $packName . '_Setup';
        
        if (cls::load($cls, true)) {
            $setup = cls::get($cls);
        } else {
            error('@Липсващ клас', $cls);
        }
        
        if (!($description = $setup->getConfigDescription())) {
            error('@Пакета няма нищо за конфигуриране', $packName);
        }
        
        if ($rec->configData) {
            $data = unserialize($rec->configData);
        } else {
            $data = array();
        }
        
        $form = cls::get('core_Form');
        
        $form->title = "Настройки на пакета|* <b style='color:green;'>{$packName}</b>";
        
        foreach ($description as $field => $arguments) {
            $type = $arguments[0];
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
                if ($params['readOnly']) {
                    $params['hint'] = "Тази стойност може да бъде променена във файла \n`" . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php' . '`';
                } else {
                    $params['hint'] .= ($params['hint'] ? "\n" : '') . 'Стойност по подразбиране|*: "' . $defVal . '"';
                }
            }
            
            $form->FNC($field, $type, $params);
            
            if (($data[$field] || $data[$field] === (double) 0 || $data[$field] === (int) 0) &&
                            (!defined($field) || ($data[$field] != constant($field)))) {
                $form->setDefault($field, $data[$field]);
            } elseif (defined($field)) {
                $form->setDefault($field, constant($field));
                $form->setField($field, array('attr' => array('class' => 'const-default-value')));
            }
            
            if ($params['readOnly']) {
                $form->setReadOnly($field);
            }
        }
        
        $form->setHidden('pack', $rec->name);
        $setup->manageConfigDescriptionForm($form);
        $form->input();
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array($this);
        }
        
        if ($form->isSubmitted()) {
            
            $callOnConfigChange = array();
            foreach ($description as $field => $params) {
                
                $sysDefault = defined($field) ? constant($field) : '';
                $fType = $form->getFieldType($field, false);
                if ($sysDefault != $form->rec->{$field}) {
                    
                    // Да може да се зададе автоматичната стойност
                    if ((($fType instanceof type_Class) || ($fType instanceof type_Enum) || ($fType instanceof color_Type))
                                    && ($fType->params['allowEmpty']) && ($form->rec->{$field} === null)) {
                        $data[$field] = null;
                    } elseif ($form->rec->{$field} !== null) {
                        $data[$field] = $form->rec->{$field};
                    }
                } else {
                    $data[$field] = '';
                }
                
                // Ако полето има зададена ф-я, която да се вика при промяна на уеб константата
                $callOnChange = $form->getFieldParam($field, 'callOnChange');
                if($callOnChange){
                    
                    // И стойността на уеб константата е променена
                    $oldValue = self::getConfigValue($packName, $field);
                    if($oldValue !== $data[$field]){
                        $callOnConfigChange[$field] = (object)array('method' => $callOnChange, 'type' => $fType, 'oldValue' => $oldValue, 'newValue' => $data[$field]);
                    }
                }
            }

            self::setConfig($packName, $data);
            
            // Правим запис в лога
            $this->logWrite('Промяна на конфигурацията на пакет', $rec->id);
            
            $msg = 'Конфигурацията е записана';
            
            // Ако е инсталиран, обновяваме пакета
            if (self::isInstalled($packName)) {
                $setupClass = $packName . '_Setup';
                if ($setupClass::INIT_AFTER_CONFIG) {
                    $msg .= '<br>' . $this->setupPack($packName, $rec->version, true, true, false);

                    core_Cache::eraseFull();
                }
            }
            
            // Ако има заопашено методи, които да се викат при промяна на уеб константа, изпълняват се
            if(countR($callOnConfigChange)){
                foreach ($callOnConfigChange as $constData){
                    $callOn = dt::addSecs(60);
                    core_CallOnTime::setOnce($this->className, 'callOnChange', $constData, $callOn);
                }
            }
            
            return new Redirect($retUrl, $msg);
        }
        
        $form->toolbar->addSbBtn('Запис', 'default', 'ef_icon = img/16/disk.png, title=Съхраняване на настройките');
        
        // Добавяне на допълнителни системни действия
        if (countR($setup->systemActions)) {
            foreach ($setup->systemActions as $sysActArr) {
                $form->toolbar->addBtn($sysActArr['title'], $sysActArr['url'], $sysActArr['params']);
            }
        }
        
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        if (method_exists($setup, 'checkConfig') && ($errMsg = $setup->checkConfig(true))) {
            $errMsg = tr($errMsg);
            $form->info = "<div style='padding:10px;border:dotted 1px red;background-color:#ffff66;color:red;'>{$errMsg}</div>";
        }
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Метод викащ се след промяна на уеб константа, на която и е зададен метод за викане
     * 
     * @param stdClass $data
     * @return mixed
     */
    public function callback_callOnChange($data)
    {
        if(isset($data->method)){
            
            return call_user_func_array($data->method, array($data->type, $data->oldValue, $data->newValue));
        }
    }
    
    
    /**
     * Задава конфигурация на пакет
     *
     * @param string $name
     * @param array  $data
     */
    public static function setConfig($name, $data)
    {
        $rec = self::fetch("#name = '{$name}'");
        if (!$rec) {
            $rec = new stdClass();
            $rec->name = $name;
        }
        
        if ($rec->configData) {
            $exData = unserialize($rec->configData);
        } else {
            $exData = array();
        }
        
        if (count($data)) {
            foreach ($data as $key => $value) {
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
    public static function toArray($conf)
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
            if ($conf !== '') {
                
                // Добавяме в масива
                $resArr[$conf] = $conf;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Променяме Списъчния изглед на пакетите
     */
    public function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if ($data->rows) {
            $res = new ET(getFileContent('core/tpl/ListPack.shtml'));
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
        
        return false;
    }
}
