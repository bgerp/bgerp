<?php
/**
 * Клас 'schema_Migrations'
 *
 * Миграции на схемата на базата данни
 *
 * @category  bgerp
 * @package   core
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class schema_Migrations extends core_Manager
{
    /**
     * Заглавие в множествено число
     * 
     * @var string
     */
    public $title = 'Миграции';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_Created, plg_Modified, plg_State, plg_SystemWrapper, plg_RowTools';


    /**
     * Поддържани интерфейси
     * 
     * var string|array
     */
    public $interfaces;
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage;
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'admin';
    
    
    public $canScan = 'admin';
    
    public $canApply = 'admin';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, pack, name, when, stamp, createdOn=Време на->получаване, appliedOn';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     * @see plg_RowTools
     */
    public $rowToolsField;

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие');
        $this->FLD('description', 'text', 'caption=Описание');
        $this->FLD('pack', 'varchar', 'caption=Пакет');
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('when', 'enum(beforeSetup=преди setup,afterSetup=след setup)', 'caption=Кога');
        $this->FLD('stamp', 'datetime', 'caption=Време на->създаване');
        $this->FLD('appliedOn', 'datetime', 'caption=Време на->изпълнение');
        $this->FNC('className', 'varchar', 'caption=Клас');
        $this->FLD('fileName', 'varchar', 'caption=Файл');
    }
    
    
    public static function on_CalcClassName(schema_Migrations $mvc, $rec)
    {
        $rec->className = $rec->pack . '_migrations_' . $rec->name;
    }
    
    
    public function act_Scan()
    {
        $this->requireRightFor('scan');
        
        $count = $this->scan();
        $msg   = $count ? "{$count} нови миграции" : 'Няма нови миграции';
        
        return new core_Redirect(array($this, 'list'), $msg);
    }
    
    
    public function act_Apply()
    {
        $this->requireRightFor('apply');
        
        $when = Request::get('when');
        
        $feedback = '';
        
        $count = $this->applyPending($when, $feedback);
        $msg   = $count ? "Приложени {$count} миграции" : 'Не бяха приложени миграции';
        
        $tpl = new core_ET("<h1>$msg</h1>" . $feedback);
        
        $tpl = $this->renderWrapping($tpl, NULL);
        
        return $tpl;
    }
    
    
    public static function on_AfterPrepareListToolbar(core_Mvc $mvc, $data)
    {
        if ($mvc->haveRightFor('scan')) {
            $data->toolbar->addBtn('Сканиране', array($mvc, 'scan'), 'id=btnScan,class=btn-refresh');
        } 

        if ($mvc->haveRightFor('apply')) {
            $data->toolbar->addBtn('Прилагане (преди setup)', array($mvc, 'apply', 'when'=>'beforeSetup'), 'id=btnApplyBefore,class=btn-apply');
            $data->toolbar->addBtn('Прилагане (след setup)', array($mvc, 'apply', 'when'=>'afterSetup'), 'id=btnApplyAfter,class=btn-apply');
        } 
    }
    
    
    public static function on_AfterGetRequiredRoles(schema_Migrations $mvc, &$requiredRoles, $action, $rec = NULL)
    {
        if ($action == 'apply') {
            if (!$mvc->countPending()) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Брои неприложените миграции
     * 
     * @param string $when кой тип миграции?
     * @return int
     */
    protected function countPending($when = NULL)
    {
        $pending = $this->fetchPending($when);
        
        return count($pending);
    }
    
    
    /**
     * Сканира всички инсталирани пакети и записва новопоявилите се миграции
     */
    protected function scan()
    {
        $count = 0;
        
        /* @var $packQuery core_Query */
        $packQuery = core_Packs::getQuery();
        
        $packs = $packQuery->fetchAll();
        
        foreach ($packs as $packRec) {
            if (($mPath = static::getPackMigrationsDir($packRec->name)) !== FALSE) {
                $existingMigrations = $this->fetchPackMigrations($packRec->name);
                $fsMigrations       = $this->scanDir($mPath);
                
                if (empty($fsMigrations)) {
                    continue;
                }
                
                $newMigrations = array_diff_key($fsMigrations, $existingMigrations);
                
                foreach ($newMigrations as $fileName=>$migrationName) {
                    $rec = (object)array(
                        'pack'     => $packRec->name,
                        'name'     => $migrationName,
                        'fileName' => $fileName,
                    );
                    
                    // Изчисляваме className за новопоявила се миграция
                    $this->on_CalcClassName($this, $rec);
                    
                    if (!$this->getDetails($rec)) {
                        // Невалиден миграционен клас
                        continue;
                    }
                    
                    if ($this->save($rec)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    
    /**
     * Извлича данни от миграционен клас
     * 
     * @param stdClass $rec
     * @return boolean
     */
    protected function getDetails($rec)
    {
        try {
            /* @var $Migration schema_Migration */
            $Migration = cls::get($rec->className);
            
            expect($rec->stamp = $Migration::$time, 'Липсва време на създаване на миграцията', $rec);
            
            if (!$rec->when = $Migration::$when) {
                $rec->when = 'afterSetup';
            }
            
            expect(isset($this->getField('when')->type->options[$rec->when]), 'Неизвестен тип миграция', $rec);
            
        } catch (core_exception_Expect $ex) {
            return $ex;
        }
        
        return TRUE;
    }
    
    
    /**
     * Пълното име на директорията, където се очакват миграциите на даден пакет
     * 
     * @param string $packName
     * @return boolean FALSE, ако липсва директорията за миграции на пакета
     */
    protected static function getPackMigrationsDir($packName)
    {
        return core_App::getFullPath($packName . '/migrations');
    }
    
    
    /**
     * Изпълнява неприложените миграции
     * 
     * @param string $when кой тип миграции да се изпълнят?
     * @return int брой успешно изпълнени миграции
     */
    protected function applyPending($when, &$feedback)
    {
        $recs = $this->fetchPending($when);
        
        $appliedCnt = 0;
        
        if (count($recs)) {
            foreach ($recs as $rec) {
                try {
                    $feedback .= '<h3>Прилагане на ' . $rec->className . '</h3>';
                    $feedback .= $this->doApply($rec);
                } catch (core_exception_Expect $ex) {
                    continue;
                }
                
                $appliedCnt++;
            }
        }
        
        return $appliedCnt;
    }
    
    
    /**
     * Прилага една миграция
     * 
     * @param stdClass $rec запис на модела schema_Migrations
     * @return boolean
     */
    protected function doApply($rec)
    {
        $migrationClass = cls::get($rec->className);
        
        if ($success = $migrationClass::apply()) {
            $rec->state     = 'active';
            $rec->appliedOn = dt::now(TRUE);
            $this->save($rec);
        }
        
        return $success;
    }
    
    
    /**
     * Извлича от БД неприложените миграции
     *
     * @param string $when кой тип миграции да се извлекат?
     * @return array
     */
    protected function fetchPending($when = NULL)
    {
        /* @var $query core_Query */
        $query = $this->getQuery();
        $query->orderBy('stamp');
        $query->where("#state = 'draft'");
        
        if (isset($when)) {
            $query->where(array("#when = '[#1#]'", $when));
        }
        
        $pending = $query->fetchAll();
        
        return $pending;
        
    }
    
    
    protected function fetchPackMigrations($packName)
    {
        /* @var $query core_Query */
        $query = $this->getQuery();
        $query->where(array("#pack = '[#1#]'", $packName));
        
        $migrations = array();
        
        while ($rec = $query->fetch()) {
            $migrations[$rec->fileName] = $rec;
        }
        
        return $migrations;
    }
    
    
    /**
     * Сканира директория за налични миграционни файлове
     * 
     * @param string $path пълно име на директория
     * @return array
     */
    protected static function scanDir($path)
    {
        $dir = new DirectoryIterator($path);
        
        if (!$dir->isDir()) {
            return FALSE;
        }
        
        $migrations = array();
        
        /* @var $item DirectoryIterator */
        foreach ($dir as $item) {
            if (!$item->isFile()) {
                continue;
            }
            
            $fileName = $item->getFilename();
            $parsed   = array();
            
            if (!preg_match('/^(.*)\.class\.php$/', $fileName, $parsed)) {
                continue;
            }
            
            $migrations[$fileName] = $parsed[1];
        }
        
        return $migrations;
    }
}
