<?php 


/**
 * Модел за Товарителници към спиди
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_BillOfLadings extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Товарителници към спиди';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'speedy,admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да прави чакащи?
     */
    public $canRequest = 'speedy,admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_Created, plg_Select, plg_RowTools2, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
     public $listFields = "id,containerId,number,takingDate,file,data,state,createdOn,createdBy";


    /**
     * Действия с избраните
     */
    public $doWithSelected = 'request=Заявяване';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ,input=hidden');
        $this->FLD('number', 'varchar', 'caption=Товарителница,input=hidden');
        $this->FLD('takingDate', 'datetime(format=smartTime)', 'caption=Дата,input=hidden');
        $this->FLD('file', 'fileman_FileType(bucket=billOfLadings)', 'caption=Файл,input=hidden');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=hidden');
        $this->FLD('state', 'enum(pending=Незаявени,active=Заявени)', 'caption=Състояние,notNull,value=active');

        $this->setDbIndex('containerId');
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
        $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FLD('date', 'date', 'caption=Дата');
        $data->listFilter->showFields = 'date,search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy('createdOn', "DESC");

        if($filter = $data->listFilter->rec){
            if(!empty($filter->date)){
                $data->query->where("#takingDate >= '{$filter->date}'");
            }
        }
    }


    /**
     * Заявяване на пратки
     */
    function act_Request()
    {
        $this->requireRightFor('request');
        $selArr = arr::make(Request::get('Selected'));
        if (countR($selArr)) {
            $query = static::getQuery();
            $query->in('id', $selArr);

            $numbers = arr::extractValuesFromArray($query->fetchAll(), 'number');
            $list = array_values($numbers);

            try {
                $params = array('pickupScope' => "EXPLICIT_SHIPMENT_ID_LIST", 'explicitShipmentIdList' => $list, 'visitEndTime' => '17:30');
                $res = speedy_Adapter::pickUp($params);
            } catch(core_exception_Expect $e){
                followRetUrl(null, $e->getMessage(), 'error');
                bp($e->getMessage());
            }

            if(is_object($res)){
                if(countR($res->orders)){
                    $uRecs = array();
                    foreach ($selArr as $id) {
                        $selRec = (object)array('id' => $id, 'state' => 'active');
                        $uRecs[] = $selRec;
                    }

                    $count = count($uRecs);
                    if($count){
                        $this->saveArray($uRecs, 'id,state');
                    }
                }

                followRetUrl(null, 'Успешно заявяване на товарителниците');
            }

            followRetUrl(null, 'Неуспешно заявяване', 'error');
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'request') {
            if(isset($rec)){
                if($rec->state != 'pending'){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}