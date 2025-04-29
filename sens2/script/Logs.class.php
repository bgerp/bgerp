<?php


/**
 * Логане на промените от скриптовете
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_script_Logs extends core_Manager
{
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, sens2_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Лог на промените от скриптове';
    
    
    public $singleTitle = 'Лог';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,sens';
    
    
    //public $listFields = '№,scriptId,type,actionId,name,value,modifiedOn=Модифициране';
    
    public $rowToolsField = '№';
    
    
    /**
     * Runtime съхраняване на контекстите за всеки скрипт
     */
    public static $contex = array();

 
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('scriptId', 'key(mvc=sens2_Scripts,title=name)', 'caption=Блок');
        $this->FLD('actionId', 'key(mvc=sens2_script_Actions, select=order)', 'caption=Ред');
        $this->FLD('type', 'enum(setVar=Задаване на променлива,setOut=Задаване на изход)', 'caption=Тип,column=none');
        $this->FLD('name', 'varchar(64,ci)', 'caption=Var/Out,column=none');
        $this->FLD('value', 'double', 'caption=Стойност,column=none');
        $this->FNC('action', 'varchar', 'caption=Действие,column');

        $this->setDbIndex('scriptId');        
        $this->setDbIndex('name');
        $this->setDbIndex('createdOn');
    }
    
    /**
     * Добавя един запис в лога
     */
    public static function add($type, $scriptId, $actionId, $name, $value) {
        $rec = (object) array(
                 'type' => $type,
                 'scriptId' =>  $scriptId,
                 'actionId' => $actionId,
                 'name' => $name,
                 'value' => $value,
            );
 
        return self::save($rec);
    }


    /**
     * Генерира стринговото представяне на действието
     */
    public static function on_CalcAction($mvc, $rec)
    {
        $rec->action = strtolower(substr($rec->type, -3)) . ' ' . $rec->name . ' = ' . $rec->value;
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'name';
        $data->listFilter->input();
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('id', 'DESC');
        if($field = $data->listFilter->rec->name) {  
 
            $data->query->where(array("#name LIKE '%[#1#]%'", $field));
            
        }


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
        if($rec->actionId) {
            $actionRec = sens2_script_Actions::fetch($rec->actionId);
            $row->actionId = ht::createLink($row->actionId, array('sens2_Scripts', 'Single', $rec->scriptId, 'order' => $actionRec->order)); 
        }
    }
}
