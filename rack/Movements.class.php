<?php



/**
 * Движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_Movements extends core_Manager
{
    
 	
    /**
     * Заглавие
     */
    var $title = 'Движения';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper, plg_RefreshRows, plg_State, plg_Sorting';
    
    
    /**
     * Време за опресняване информацията при лист
     */
    var $refreshRowsTime = 10000;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,rack';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,rack';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,rack';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';


    /**
     * Кой може да започне движение
     */
    var $canStart = 'ceo,admin,rack,storeWorker';
    
    
    /**
     * Кой може да приключи движение
     */
    var $canDone = 'ceo,admin,rack,storeWorker';
    
    
    /**
     * Кой може да откаже движение
     */
    var $canCancel = 'ceo,admin,rack,storeWorker';
    

    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 50;
    
    
    /**
     *
     */
    public $listFields = 'palletId,position,positionTo,workerId,note,created=Създаване';
     
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,column=none');
        $this->FLD('palletId', 'key(mvc=rack_Pallets, select=label)', 'caption=Палет,smartCenter');
        
        $this->FLD('position', 'rack_PositionType', 'caption=От,smartCenter');
        $this->FLD('positionTo', 'rack_PositionType', 'caption=Към,smartCenter');
        
        $this->FLD('state', 'enum(pending=Чакащо, active=Активно, closed=Приключено)', 'caption=Състояние,smartCenter,input=hidden');
        $this->FLD('workerId', 'user(roles=storeWorker,ceo)', 'caption=Товарач,smartCenter');
        $this->FNC('created', 'varchar(64)', 'caption=Създаване,smartCenter');

        $this->FLD('note', 'varchar(64)', 'caption=Забележка,column=none');
    }
    

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($mvc->haveRightFor('start', $rec)) {
            $state .= ht::createBtn('Вземи', array($mvc, 'start', $rec->id));
        }
        if($mvc->haveRightFor('done', $rec)) {
            $state .= ht::createBtn('Готово', array($mvc, 'done', $rec->id));
        }
        if($mvc->haveRightFor('cancel', $rec)) {
            $state .= ht::createBtn('Отказ', array($mvc, 'cancel', $rec->id));
        }

        if($state) {
            $row->workerId .= ' ' . $state;
        }

        if($rec->note) {
            $row->note = '<div style="font-size:0.8em;">' . $mvc->getVerbal($rec, 'note') . '</div>';
        }

        $row->created = '<div style="font-size:0.8em;">' . $mvc->getVerbal($rec, 'createdOn') . ' ' . crm_Profiles::createLink($rec->createdBy) . '</div>';
    }

    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'start' && $rec && $rec->state) {
            if($rec->state != 'pending') {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'cancel' && $rec && $rec->state) {
            if($rec->state != 'active' || $rec->workerId != $userId) {
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'done' && $rec && $rec->state) {
            if($rec->state != 'active' || $rec->workerId != $userId) {
                $requiredRoles = 'no_one';
            }
        }
    }



    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Движения на палети в склад |*<b style="color:green">' . store_Stores::getTitleById($storeId) . "</b>";
    }


    public function act_Start()
    {
        $this->requireRightFor('start');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('start', $rec);

        $rec->state = 'active';
        $rec->workerId = core_Users::getCurrent();
        $this->save($rec, 'state,workerId');

        redirect(array($this));
    }
    
    public function act_Cancel()
    {
        $this->requireRightFor('Cancel');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('Cancel', $rec);

        $rec->state = 'pending';
        $rec->workerId = NULL;
        $this->save($rec, 'state,workerId');

        redirect(array($this));
    }
    
    
    public function act_Done()
    {
        $this->requireRightFor('Done');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('Done', $rec);

        $pRec = rack_Pallets::fetch($rec->palletId);
        $pRec->position = $rec->positionTo;

        $pMvc = cls::get('rack_Pallets');
        $pMvc->save_($pRec, 'position');
 
        $rec->state = 'closed';
        $this->save($rec, 'state');
        $rMvc = cls::get('rack_Racks');

        if($rec->positionTo) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->positionTo] = TRUE;
        }

        if($rec->position) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->position] = TRUE;
        }

        core_Cache::remove('UsedRacksPossitions', $rec->storeId);

        $rMvc->on_Shutdown($rMvc);

        redirect(array($this));
    }


    /**
     * Връща масив с всички използвани палети
     */
    public static function getExpected($storeId = NULL)
    {
        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        $res = array();
        $res[0] = array();
        $res[1] = array();

        $query = self::getQuery();
        while($rec = $query->fetch("#storeId = {$storeId} AND #state != 'closed'")) {
            if($rec->position) {
                $pRec = rack_Pallets::fetch($rec->palletId);
                $res[0][$rec->position] = $pRec->productId;
            }
            if($rec->positionTo) {
                $pRec = rack_Pallets::fetch($rec->palletId);
                $res[1][$rec->positionTo] = $pRec->productId;
            }

        }
  
        return $res;
    }

}
