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
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,install=Обновяване,deinstall=Премахване';
    
    
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
        
        $this->load('plg_Created,plg_SystemWrapper');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Начална точка за инсталиране на пакети
     */
    function act_Install()
    {
        
        $this->requireRightFor('install');
        
        $pack = Request::get('pack', 'identifier');
        
        if(!$pack) error('Missing pack name.');
        
        $res = $this->setupPack($pack);
        
        return $this->renderWrapping($res);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Deinstall()
    {
        $this->requireRightFor('deinstall');
        
        $pack = Request::get('pack', 'identifier');
        
        if(!$pack) error('Липсващ пакет', $pack);
        
        if(!$this->fetch("#name = '{$pack}'")) {
            error('Този пакет не е инсталиран', $pack);
        }
        
        if($this->fetch("(#name = '{$pack}') AND (#deinstall = 'yes')")) {
            
            $cls = $pack . "_Setup";
            
            if(cls::load($cls, TRUE)) {
                
                $setup = cls::get($cls);
                
                if(!method_exists($setup, 'deinstall')) {
                    $res = "<h2>Пакета <font color=\"\">'{$pack}'</font> няма деинсталатор.</h2>";
                } else {
                    $res = "<h2>Деинсталиране на пакета <font color=\"\">'{$pack}'</font></h2>";
                    $res .= (string) "<ul>" . $setup->deinstall() . "</ul>";
                }
            } else {
                $res = "<h2 style='color:red;''>Липсва кода на пакета <font color=\"\">'{$pack}'</font></h2>";
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
        
        if(!$this->fetch("#name = 'core'")) {
            $path = EF_EF_PATH . "/core/Setup.class.php";
            
            if(file_exists($path)) {
                $opt['core'] = 'Ядро на EF "core"';
            }
        }
        
        $appDirs = $this->getSubDirs(EF_APP_PATH);
        
        $vendorDirs = $this->getSubDirs(EF_VENDORS_PATH);
        
        $efDirs = $this->getSubDirs(EF_EF_PATH);
        
        if(defined('EF_PRIVATE_PATH')) {
            $privateDirs = $this->getSubDirs(EF_PRIVATE_PATH);
        }
        
        if (count($appDirs)) {
            foreach($appDirs as $dir => $dummy) {
                $path = EF_APP_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    unset($vendorDirs[$dir]);
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Компонент на приложението "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($vendorDirs)) {
            foreach($vendorDirs as $dir => $dummy) {
                $path = EF_VENDORS_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Публичен компонент "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($efDirs)) {
            foreach($efDirs as $dir => $dummy) {
                $path = EF_EF_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Компонент на фреймуърка "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($privateDirs)) {
            foreach($privateDirs as $dir => $dummy) {
                $path = EF_PRIVATE_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Собствен компонент "' . $dir . '"';
                    }
                }
            }
        }
        
        return $opt;
    }
    
    
    /**
     * Изпълнява се преди извличането на редовете за листови изглед
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#name");
    }
    
    
    /**
     * Рендира лентата с инструменти за списъчния изглед
     */
    function renderListToolbar_($data)
    {
        if(! ($opt = $this->getNonInstalledPacks())) return "";
        
        $form = cls::get('core_Form', array('view' => 'horizontal'));
        $form->FNC('pack', 'varchar', 'caption=Пакет,input');
        
        $form->setOptions('pack', $opt);
        $form->toolbar = cls::get('core_Toolbar');
        $form->setHidden(array('Act' => 'install'));
        $form->toolbar->addSbBtn('Инсталирай', 'default', 'class=btn-install');
        
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
                    
                    if(is_dir($dir . "/" . $file)) {
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
        
        $row->name = "<b style='font-size:1.2em;'>" . $mvc->getVerbal($rec, 'name') . "</b>&nbsp;&nbsp;[v&nbsp;" . str_replace(',', '.', $rec->version) . "]";
        
        if($rec->startCtr) {
            $row->name = ht::createLink($row->name, array($rec->startCtr, $rec->startAct));
        }
        
        $row->name .= "<div><small>{$rec->info}</small></div>";
        
        $row->install = ht::createBtn("Обновяване", array($mvc, 'install', 'pack' => $rec->name), NULL, NULL, array('class' => 'btn-software-update'));
        
        if($rec->deinstall == 'yes') {
            $row->deinstall = ht::createBtn("Оттегляне", array($mvc, 'deinstall', 'pack' => $rec->name), NULL, NULL, 'class=btn-reject');
        } else {
            $row->deinstall = ht::createBtn("Оттегляне", NULL, NULL, NULL, 'class=btn-reject');
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
        
        if(!$this->db->tableExists($this->dbTableName)) {
            $this->firstSetup();
        }
        
        if(!$this->fetch("#name = 'core'") ||
            (!$this->fetch("#name = '" . EF_APP_CODE_NAME . "'") && cls::load(EF_APP_CODE_NAME . "_Setup", TRUE))) {
            $this->firstSetup();
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Setup()
    {
        if(isDebug()) {
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
        
        if(!$nextUrl) {
            // Ако нямаме нито един потребител, редиректваме за добавяне на администратор
            if(!$Users->fetch('1=1')) {
                $url = toUrl(array('core_Users', 'add', 'ret_url' => TRUE));
            } else {
                global $_GET;
                $get = $_GET;
                unset($get['virtual_url'], $get['ajax_mode']);
                $url = toUrl($get);
            }
        } else {
            $url = toUrl($nextUrl);
        }
        
        $html .= "<meta http-equiv='refresh' content='15;url={$url}' />";
        
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
        $html .= "</head><body>";
        
        $html .= $res;
        
        $html .= "</body></html>";
        
        echo $html;
        
        die;
    }
    
    
    /**
     * Прави начално установяване на посочения пакет. Ако в
     * Setup-а на пакета е указано, че той зависи от други пакети
     * (var $depends = ... ), прави се опит и те да се установят
     */
    function setupPack($pack, $version = 0, $force = TRUE)
    {
        // Максиламно време за инсталиране на пакет
        set_time_limit(300);
        
        DEBUG::startTimer("Инсталиране на пакет '{$pack}'");
        
        // Имената на пакетите са винаги с малки букви
        $pack = strtolower($pack);
        
        // Предпазване срещу рекурсивно зацикляне
        if($this->alreadySetup[$pack]) return;
        
        // Проверка дали Setup класа съществува
        if(!cls::load($pack . "_Setup", TRUE)) {
            return "<h4>Невъзможност да се инсталира <font color='red'>{$pack}</font>. " .
            "Липсва <font color='red'>Setup</font> клас.</h4>";
        }
        
        // Вземаме Setup класа, за дадения пакет
        $setup = cls::get($pack . '_Setup');
        
        // Ако има зависимости, проследяваме ги
        // Първо инсталираме зависимостите
        if($setup->depends) {
            $depends = arr::make($setup->depends, TRUE);
            
            foreach($depends as $p => $v) {
                $res .= $this->setupPack($p, $v, FALSE);
            }
        }
        
        // Започваме самото инсталиране
        if($setup->startCtr) {
            $res .= "<h2>Инсталиране на пакета \"<a href=\"" .
            toUrl(array($setup->startCtr, $setup->startAct)) . "\"><b>{$pack}</b></a>\"</h2>";
        } else {
            $res .= "<h2>Инсталиране на пакета \"<b>{$pack}</b>\"</h2>";
        }
        
        $res .= "<ul>";
        
        // Единственото, което правим, когато версията, която инсталираме
        // е по-малка от изискваната, е да сигнализираме за този факт
        if($version > 0 && $version > $setup->version) {
            $res .= "<li style='color:red'>За пакета '{$pack}' се изисква версия [{$version}], " .
            "а наличната е [{$setup->version}]</li>";
        }
        
        // Ако инсталирането е форсирано 
        //   или този пакет не е инсталиран до сега 
        //   или инсталираната версия е различна спрямо тази
        // извършваме инсталационна процедура
        if(!$force) {
            $rec = $this->fetch("#name = '{$pack}'");
        }
        
        if($force || empty($rec) || ($rec->version != $setup->version)) {
            
            // Форсираме системния потребител
            core_Users::forceSystemUser();
            
            // Правим началното установяване
            $res .= $setup->install() . "</ul>";
            
            // Де-форсираме системния потребител
            core_Users::cancelSystemUser();
            
            $rec = $this->fetch("#name = '{$pack}'");
            
            // Правим запис на факта, че приложението е инсталирано
            if(!is_object($rec)) $rec = new stdClass();
            $rec->name = $pack;
            $rec->version = $setup->version;
            $rec->info = $setup->info;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            $rec->deinstall = method_exists($setup, 'deinstall') ? 'yes' : 'no';
            $this->save($rec);
        } else {
            $res .= "<li>Пропускаме, има налична инсталация";
        }
        
        // Отбелязваме, че на текущия хит, този пакет е установен
        $this->alreadySetup[$pack] = TRUE;
        
        $res .= "</ul>";
        
        DEBUG::stopTimer("Инсталиране на пакет '{$pack}'");
        
        return $res;
    }
}