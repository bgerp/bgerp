<?php


/**
 * Клас 'cond_Allergens' - Списък с хранителни алергени
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Allergens extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Printing';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'num,title';


    /**
     * Заглавие
     */
    public $title = 'Списък на алергените в храните';


    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Алерген';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('num', 'int', 'caption=№,mandatory');
        $this->FLD('title', 'varchar', 'caption=Алерген');
        $this->FNC('titleNum', 'varchar', 'caption=Алерген,mandatory');

        $this->setDbUnique('num');
        $this->setDbUnique('title');
    }


    /**
     * Изчислимо поле за алергени
     */
    protected static function on_CalcTitleNum($mvc, $rec)
    {
        $rec->titleNum = "{$rec->num}. {$rec->title}";
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-active";
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cond/csv/Allergens.csv';

        $fields = array(
            0 => 'num',
            1 => 'title',
        );

        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;

        return $res;
    }
}