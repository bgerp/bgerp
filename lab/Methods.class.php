<?php



/**
 * Мениджър за методите в лабораторията
 *
 *
 * @category  all
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Methods extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Методи за лабораторни тестове";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State,
                             Params=lab_Parameters, plg_RowTools, plg_Printing, 
                             lab_Wrapper, plg_Sorting, fileman_Files';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,tools=Пулт,name,equipment,paramId,
                             minVal,maxVal';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'lab,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'lab,admin';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'lab/tpl/SingleLayoutMethods.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование');
        $this->FLD('equipment', 'varchar(255)', 'caption=Оборудване,notSorting');
        $this->FLD('paramId', 'key(mvc=lab_Parameters,select=name,allowEmpty,remember)', 'caption=Параметър,notSorting');
        $this->FLD('description', 'richtext', 'caption=Описание,notSorting');
        $this->FLD('minVal', 'double(decimals=2)', 'caption=Възможни стойности->Минимална,notSorting');
        $this->FLD('maxVal', 'double(decimals=2)', 'caption=Възможни стойности->Максимална,notSorting');
    }
    
    
    /**
     * Линк към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array($mvc, 'single', $rec->id));
    }
}