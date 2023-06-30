<?php


/**
 * Логове в палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Logs extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Логове на движения';


    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Лог';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, rack_Wrapper, plg_SelectPeriod, plg_Search, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,rack';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rackSee';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кои полета ще се виждат в листовия изглед
     */
    public $listFields = 'message,createdOn=На,createdBy=От,productId';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'movementId,position,productId,message';


    /**
     * Информация за позволени движения
     */
    protected static $actionClasses = array('create'   => 'state-opened',
                                            'waiting'  => 'state-waiting',
                                            'edit'     => 'state-edited',
                                            'start'    => 'state-active',
                                            'return'   => 'state-hidden',
                                            'reject'   => 'state-stopped',
                                            'close'    => 'state-closed',
                                            'revision' => 'rackRevisionRow');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('movementId', 'key(mvc=rack_Movements,select=id)', 'caption=Движение');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts)', 'caption=Артикул');
        $this->FLD('message', 'text', 'caption=Текст');
        $this->FLD('action', 'enum(,create=Създаване,edit=Редактиране,waiting=Запазване,start=Започване,close=Приключване,return=Връщане,reject=Отказване,revision=Ревизия)', 'caption=Действие,after=to,placeholder=Всички');

        $this->setDbIndex('storeId');
        $this->setDbIndex('action');
    }


    /**
     * Добавя Лог на историята на движението
     *
     * @param int $storeId
     * @param int $productId
     * @param string $action
     * @param string $position
     * @param int|null $movementId
     * @param text $message
     */
    public static function add($storeId, $productId, $action, $position, $movementId, $message)
    {
        $rec = (object)array('position' => $position, 'message' => $message, 'storeId' => $storeId, 'productId' => $productId, 'action' => $action);

        if(isset($movementId)){
            $movementRec = rack_Movements::fetchRec($movementId);
            if(is_object($movementRec)){
                $rec->movementId = $movementRec->id;
                $Movements = cls::get('rack_Movements');
                Mode::push('text', 'plain');
                $description = strip_tags($Movements->getMovementDescription($movementRec, false, false));
                Mode::pop('text');
                $rec->message .= " / {$description}";
            }
        }

        static::save($rec);
    }


    /**
     * След обработка на лист филтъра
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Логове в склад |*<b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        $data->query->orderBy('createdOn=DESC');

        $newOptions = array();
        $actionOptions = $data->listFilter->getFieldType('action')->options;

        foreach ($actionOptions as $action => $actionCaption){
            $actionOptionRec = new stdClass();
            $actionOptionRec->attr = array('class' => static::$actionClasses[$action]);
            $actionOptionRec->title = $actionCaption;
            $newOptions[$action] = $actionOptionRec;
        }
        $data->listFilter->setOptions('action', array('' => '') + $newOptions);
        if($movementId = Request::get('movementId', 'int')){
            $data->query->where("#movementId = {$movementId}");
        }

        $data->listFilter->FLD('from', 'date', 'caption=От');
        $data->listFilter->FLD('to', 'date', 'caption=До');
        $data->listFilter->showFields = 'selectPeriod, from, to, createdBy, search, productId, action';
        $data->listFilter->setField('createdBy', 'caption=Потребител,placeholder=Всички');
        $data->listFilter->setFieldTypeParams('createdBy', array('allowEmpty' => 'allowEmpty'));
        $users = core_Users::getUsersByRoles('ceo,rack,store');
        $users = array(core_Users::SYSTEM_USER => core_Users::getTitleById(core_Users::SYSTEM_USER, 'nick')) + $users;
        $data->listFilter->setOptions('createdBy', $users);
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));

        $data->listFilter->input();
        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->from)){
                $data->query->where("#createdOn >= '{$filterRec->from} 00:00:00'");
            }

            if(!empty($filterRec->to)){
                $data->query->where("#createdOn <= '{$filterRec->to} 23:59:59'");
            }

            foreach (array('createdBy', 'action', 'productId') as $fld){
                if(!empty($filterRec->{$fld})){
                    $data->query->where("#{$fld} = '{$filterRec->{$fld}}'");
                }
            }
        }

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
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
        $row->ROW_ATTR['class'] = static::$actionClasses[$rec->action];
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
    }
}