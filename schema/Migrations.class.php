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
    public $canDelete = 'no_one';
    
    
    public $canScan = 'admin';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields;
    
    
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
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('pack', 'varchar', 'caption=Пакет');
        $this->FLD('description', 'text', 'caption=Описание');
    }
    
    
    public function act_Scan()
    {
        $this->requireRightFor('scan');
        
        $count = $this->scan();
        $msg   = $count ? "{$count} нови миграции" : 'Няма нови миграции';
        
        return new core_Redirect(array($this, 'list'), $msg);
    }
    
    
    public static function on_AfterPrepareListToolbar(core_Mvc $mvc, $data)
    {
        if (!$mvc->haveRightFor('scan')) {
            return;
        } 
        
        $data->toolbar->addBtn('Сканиране', array($mvc, 'scan'), 'id=btnScan,class=btn-refresh');
    }
    
    protected function scan()
    {
        $count = 0;
        
        /* @var $packQuery core_Query */
        $packQuery = core_Packs::getQuery();
        
        $packs = $packQuery->fetchAll();
        
        foreach ($packs as $packRec) {
            if (($mPath = getFullPath($packRec->name . '/migrations')) !== FALSE) {
                $existingMigrations = $this->fetchPackMigrations($packRec->name);
                $fsMigrations       = $this->scanDir($mPath);
                
                $newMigrations = array_diff_key($fsMigrations, $existingMigrations);
                
                foreach ($newMigrations as $migrationRec) {
                    $migrationRec->pack = $packRec->name;
                    
                    if ($this->save($migrationRec)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    
    public function fetchPackMigrations($packName)
    {
        /* @var $query core_Query */
        $query = $this->getQuery();
        $query->where(array("#pack = '[#1#]'", $packName));
        
        $migrations = array();
        
        while ($rec = $query->fetch()) {
            $migrations[$rec->name] = $rec;
        }
        
        return $migrations;
    }
    
    
    protected function scanDir($path)
    {
        $dir = new DirectoryIterator($path);
        $migrations = array();
        
        /* @var $item DirectoryIterator */
        foreach ($dir as $item) {
            if (!$this->isValidMigrationFile($item)) {
                continue;
            }
            
            $name = $item->getFilename();
            
            $migrations[$name] = (object)array(
                'name' => $name,
            );
        }
        
        return $migrations;
    }
    

    /**
     * 
     * @param $item DirectoryIterator
     */
    protected function isValidMigrationFile($item)
    {
        if (!$item->isFile()) {
            return FALSE;
        }
        
        return TRUE;
    }
}
