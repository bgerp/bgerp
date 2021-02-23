<?php


/**
 * Обекти в плановете на помещенията
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_Objects extends core_Detail {


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'planId';


   /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper, plg_SaveAndNew';
    
    
    /**
     * Заглавие
     */
    public $title = 'Обекти';
    

    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'Обект';
    

    /**
     * Права за писане
     */
    public $canWrite = 'floor,admin,ceo';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'floor,admin,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'floor,admin,ceo';
    
      
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    // public $listFields = 'order,name,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('planId', 'key(mvc=floor_Plans,select=name)', 'caption=План');
        $this->FLD('name', 'varchar(50)', 'caption=Наименование, mandatory,remember=info');

        $this->FLD('x', 'float(m=0,decimals=2)', 'caption=Позиция->X,unit=m');
        $this->FLD('y', 'float(m=0,decimals=2)', 'caption=Позиция->Y,unit=m');

        $this->FLD('width', 'float(m=0,decimals=2)', 'caption=Фигура->Широчина,unit=m,mandatory');
        $this->FLD('height', 'float(m=0,decimals=2)', 'caption=Фигура->Дълбочина,unit=m,mandatory');
        $this->FLD('round', 'percent', 'caption=Фигура->Заобленост,remember');

        $this->FLD('borderWidth', 'enum(0,1,2,3,4,5)', 'caption=Рамка->Дебелина,unit=px');
        $this->FLD('borderColor', 'color_Type', 'caption=Рамка->Цвят,mandatory');

        $this->FLD('image', 'fileman_FileType(bucket=pictures)', 'caption=Фон->Изображение');
        $this->FLD('backgroundColor', 'color_Type', 'caption=Фон->Цвят');
        $this->FLD('opacity', 'percent(min=0.0,max=1.0)', 'caption=Фон->Непрозрачност');

        $this->FLD('text', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');

        $this->setDbUnique('name');
    }


    /**
     * @TODO описание
     *
     * След потготовка на формата за добавяне / редактиране.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
 
        if($rec->id) {
            $form->toolbar->addSbBtn('Дубликат', 'duplicate', 'id=duplicate,order=10.0002,ef_icon=img/16/duplicate.png');
            if($form->isSubmitted()) {
                 if($form->cmd == 'duplicate') { 
                    $form->cmd = 'save';
                    $rec->_duplicate = true;
                    if(!strlen($rec->name)) {
                        $rec->name = 'Obj1';
                    }
                    while(self::fetch(array("#name = '[#1#]'", $rec->name))) {
                        self::increaseName($rec->name);  
                    }
                    $rec->_duplicate = $rec->name;
                }
            }
         }
    }


    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(isset($rec->_duplicate)) {
            unset($rec->id);
            $rec->modifiedOn = $rec->createdOn = dt::now();
            $rec->modifiedBy = $rec->createdBy = core_Users::getCurrent();

            $rec->name = $rec->_duplicate;
        }
    }


    /**
     * Инкрементира с 1 стоящата накрая числова част на стринга
     */
    static function increaseName(&$name)
    {
        preg_match("/^(.*[^0-9]+|)(\\d+)$/", $name, $matches);
        if(count($matches)) {
            $name = $matches[1] . ($matches[2]+1);
        } else {
            $name = $name . '_1';
        }
    }

}