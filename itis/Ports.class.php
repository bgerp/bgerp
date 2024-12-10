<?php


/**
 * Каталог на обичайното използване на мрежовите портове
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 */
class itis_Ports extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, itis_Wrapper, plg_Sorting, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Стандартни портове';
    
    
    /**
     * Права за запис
     */
    public $canWrite = 'ceo,itis,admin';
    
    
    /**
     * Права за четене
     */
    public $canRead = 'ceo,itis,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,itis';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,itis';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Полета за еденичен изглед
     */
    // public $listFields = 'id,indicatorId, value, time';
    

    /**
     * Без броене на редовете, по време на страницирането
     */
    // public $simplePaging = true;

    
    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 500000;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('port', 'int', 'caption=TCP/IP порт');
        $this->FLD('status', 'enum(ok,warning,alert)', 'caption=Статус,value=ok,notNull');
        $this->FLD('info', 'varchar(255)', 'caption=Информация');

        $this->setDbUnique('port');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'itis/csv/Ports.csv';
        $fields = array(0 => 'port', 1 => 'info');
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }

}
