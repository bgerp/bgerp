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
class itis_Process extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'itis_Wrapper, plg_Sorting, plg_Created, plg_RowTools2';
    
    
    /**
     * Заглавие
     */
    public $title = 'Рънтайм процеси';
    
    
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
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'ceo,admin,itis';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Полета за еденичен изглед
     */
    public $listFields = 'process,info,status';
    

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
        $this->FLD('process', 'varchar(ci)', 'caption=Процес,smartCenter');
        $this->FLD('status', 'enum(ok,warning,alert)', 'caption=Статус,value=ok,notNull,smartCenter');
        $this->FLD('info', 'varchar(255)', 'caption=Информация,smartCenter');

        $this->setDbUnique('process');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'itis/csv/Process.csv';
        $fields = array(0 => 'process', 1 => 'info');
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }

    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        $form->setReadonly('process');
    }

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->process = "<a href='https://www.google.com/search?q=what+is+the+process+{$rec->process}' style='color:#0c0' target=_blank>{$row->process}</a>";
        $style = itis_Devices::getStyleByStatus($rec->status);
        $row->status = "<span {$style}>" . $row->status . "</span>";
    }
}
