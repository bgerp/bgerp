<?php


/**
 * Мениджър за методите в лабораторията
 *
 *
 * @category  bgerp
 * @package   lab
 *
 * @author    Milen Georgiev <milen@download.bg>
 *            Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class lab_Methods extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Методи за лабораторни тестове';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Метод за лабораторен тест';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State,
                             Params=lab_Parameters, plg_RowTools2, plg_Printing, 
                             lab_Wrapper, plg_Sorting, fileman_Files';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,abbreviatedName,equipment,paramId,
                             minVal,maxVal';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'lab,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'lab,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'lab,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'lab,ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'lab/tpl/SingleLayoutMethods.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD(
            'paramId',
            'key(mvc=lab_Parameters,select=name,allowEmpty,remember)',
            'caption=Параметър,notSorting,mandatory'
        );
        $this->FLD('name', 'varchar(255)', 'caption=Наименование');
        $this->FLD('abbreviatedName', 'varchar(255)', 'caption=Съкращение');
        $this->FLD('formula', 'text', 'caption=Формула');
        $this->FLD('equipment', 'varchar(255)', 'caption=Оборудване,notSorting');
        $this->FLD('description', 'richtext(bucket=Notes)', 'caption=Описание,notSorting');
        $this->FLD('minVal', 'double(decimals=2)', 'caption=Възможни стойности->Минимална,notSorting');
        $this->FLD('maxVal', 'double(decimals=2)', 'caption=Възможни стойности->Максимална,notSorting');
    }
    
    
    /**
     * Проверка и валидиране на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $form
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            if ($rec->formula) {
                $contex = array();
                
                preg_match_all('/\$[_a-z][a-z0-9_]*/i', $rec->formula, $matches);
                
                foreach ($matches[0] as $v) {
                    $contex += array(
                        $v => 1
                    );
                }
                
                if ((str::prepareMathExpr($rec->formula, $contex)) === false) {
                    $form->setError('formula', 'Некоректно въведена формула !');
                }
            }
        }
    }
    
    
    /**
     * Линк към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink(
            $row->name,
            array(
                $mvc,
                'single',
                $rec->id
            )
        );
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->state = 'active';
    }
}
