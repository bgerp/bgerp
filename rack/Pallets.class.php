<?php



/**
 * Палети
 *
 *
 * @category  bgerp
 * @package   rack
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_Pallets extends core_Manager
{
    
	
    /**
     * Заглавие
     */
    var $title = 'Палети';
    
    var $singleTitle = 'Палет';
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper,plg_SaveAndNew,recently_Plugin,plg_Sorting';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'storeId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,rack';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,rack';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,rack';
    
    
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
    var $canDelete = 'ceo,rack';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,storeId,productId,quantity';

    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 50;
    

    /**
     * Кои полета ще се виждат в листовия изглед
     */
    public $listFields = 'label,productId,quantity,position,created=Създаване';
    


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden,column=none');
        $this->FLD('productId', 'key(mvc=store_Products, select=productId,allowEmpty)', 'caption=Продукт,silent,remember,refreshForm,mandatory,smartCenter');
        $this->FLD('quantity', 'int', 'caption=Количество,mandatory');
        $this->FLD('label', 'varchar(32)', 'caption=Етикет,tdClass=rightCol');
        $this->FLD('comment', 'varchar', 'caption=Коментар,column=none');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция,smartCenter');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if($rec->id) {
            $exRec = self::fetch($rec->id);
            setIfNot($rec->position, $exRec->position);
            $form->setHidden('exProductId', $rec->productId);
            $form->setHidden('exPosition', $rec->position);
        }

        if($rec->position) {
            $form->setReadOnly('position');
            $rec->positionTo = $rec->position;
        } else {
            $form->setField('position', 'input=none');
        }
        
        $form->setHidden('storeId', store_Stores::getCurrent());
        $form->FNC('positionTo', 'rack_PositionType', 'caption=Позиция на стелажите->Нова,input');
        $form->setField('position', 'caption=Позиция на стелажите->Текуща');
        $form->FNC('movementCreate', 'enum(off,on)', 'caption=Движение->Задаване,input,autohide,remember');
        $form->FNC('movementInfo', 'varchar', 'caption=Движение->Информация,input,autohide,recently');
        
        if($rec->productId) {
            $bestPos = self::getBestPos($rec->productId);
            $form->setSuggestions('positionTo', array('' => '', $bestPos => $bestPos)); 
        }

        // Дефолт за последното количество
        if($rec->productId && !$rec->quantity) {
            
            $prodRec = rack_Products::fetch($rec->productId);
            
            if($prodRec) {
                $rec->quantity = cat_products_Packagings::getQuantityInPack($prodRec->productId, 'палет');
     
                if(!$rec->quantity) {
                    $query = self::getQuery();
                    $query->orderBy('#createdOn', 'DESC');
                    $exRec = $query->fetch("#productId = {$rec->productId}");
                    if($exRec) {
                        $rec->quantity = $exRec->quantity;
                    }
                }
                
                $restQuantity = $prodRec->quantity - $prodRec->quantityOnPallets;
                $rec->quantity = min($rec->quantity, $restQuantity);

                if($rec->quantity <= 0) {
                    $rec->quantity = NULL;
                }
            }
        }

        $mode = Request::get('Mode');

        if($mode == 'down') {
            $form->rec->positionTo = '';
        }

        if($mode) {
            $form->setReadOnly('productId');
            $form->setReadOnly('quantity');
            $form->setReadOnly('label');
        }
    }


    /**
     * Връща най-добрата позиция за разполагане на дадения продукт
     */
    public static function getBestPos($productId, $storeId = NULL)
    {
        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        list($unusable, $reserved) = rack_RackDetails::getunUsableAndReserved();
        $used = rack_Pallets::getUsed();
        list($movedFrom, $movedTo) = rack_Movements::getExpected();

        // Ако намерим свободна резервирана позиция за този продукт - вземаме нея
        foreach($reserved as $pos => $pId) {
            if(($pId == $productId) && !$used[$pos]) {

                return $pos;
            }
        }

        // Ако намерим палет с този продукт и свободно място към края на стелажа - вземаме него
        $racks = array();
        foreach($used as $pos => $pId) {
            if($productId != $pId) {

                continue;
            }

            list($n, $r, $c) = explode('-', $pos);

            $haveInRacks[$n] = $n;
        }

        // Търсим най-доброто място
        $rQuery = rack_Racks::getQuery();
        $bestLen = 100000000;
        $bestPos = '';
        
        while($rRec = $rQuery->fetch("#storeId = {$storeId}")) {
            $dist = 20;
            for($cInd = 1; $cInd <= $rRec->columns; $cInd++) {
                for($rInd = 'A'; $rInd <= $rRec->rows; $rInd++) {
                    $pos = "{$rRec->num}-{$rInd}-{$cInd}";

                    if($used[$pos] == $productId) {
                        $dist = 0;
                    }
                    $dist++;

                    if($used[$pos] || $unusable[$pos] || $reserved[$pos] || $movedTo[$pos]) {
                        continue;
                    }
                    
                    if($dist < 20) {
                        $len = $dist;
                    } else {
                        $len = $rRec->num * 10000 + 100 * ord($rInd) + $cInd;
                    }

                    if($len < $bestLen) {
                        $bestPos = $pos;
                        $bestLen = $len;
                    }
                }
            }
        }

        return $bestPos;
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {

            $rec = $form->rec;
            
            $rec->storeId = store_Stores::getCurrent();

            if($rec->positionTo && ($rec->exPosition != $rec->positionTo)) {
                
                if(!rack_Racks::isPlaceUsable($rec->positionTo, $rec->productId, $rec->storeId, $error, $status)) {
                    if($status == 'reserved') {

                        $form->setWarning('positionTo', $error);
                    } else {
                        $form->setError('positionTo', $error);
                    }
                }

                if(!self::isEmpty($rec->positionTo, $rec->storeId, $error)) {

                    $form->setError('positionTo', $error);
                }
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
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Палетизирани наличности в склад |*<b style="color:green">' . store_Stores::getTitleById($storeId) . "</b>";
        
        
        $data->listFilter = cls::get('core_Form', array('method' => 'GET'));
        $data->listFilter->FLD('productId', 'key(mvc=store_Products, select=productId,allowEmpty)', 'caption=Продукт');
        $data->listFilter->FLD('pos', 'varchar(10)', 'caption=Позиция', array('attr' => array('style' => 'width:5em;')));

        $data->listFilter->showFields = 'productId,pos';  //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $rec = $data->listFilter->input();
        if(!$rec->productId) {
            $rec->productId = Request::get('productId', 'int');
            $data->listFilter->setDefault('productId', $rec->productId);
        }
        if($rec->productId) {
            $data->query->where("#productId = {$rec->productId}");
            if(!Request::get('Sort')) {
                $data->query->orderBy("position", 'ASC');
                $order = TRUE;
            }
        }

        if(!$rec->pos) {
            $rec->pos = Request::get('pos');
            $data->listFilter->setDefault('pos', $rec->pos);
        }
        if($rec->pos) {
            $data->query->where(array("#position LIKE UPPER('[#1#]%')", $rec->pos));
            if(!Request::get('Sort')) {
                $data->query->orderBy("position", 'ASC');
                $order = TRUE;
            }
        }

        if(!$order) {
            $data->query->orderBy('#createdOn', 'DESC');
        }

    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    { 
        if(($rec->position || $rec->positionTo) && ($rec->position != $rec->positionTo) && $rec->storeId && $rec->id) {
            
            $mRec = new stdClass();
            $mRec->palletId = $rec->id;
            $mRec->position = $rec->position;
            $mRec->positionTo = $rec->positionTo;
            $mRec->storeId = $rec->storeId;
            $mRec->note = $rec->movementInfo;

            if($rec->movementCreate) {
                $mRec->state = 'pending';
            } else {
                // Моментален запис на позицията
                $rec->position = $rec->positionTo;
                $mvc->save_($rec, 'position');
                $mRec->state = 'closed';
            }

            rack_Movements::save($mRec);
        }
        
        if(!$rec->label) {
            $rec->label = '#' . $rec->id;
            $mvc->save_($rec, 'label');
        }
        
        self::recalc($rec->productId);
 
        if($rec->exProductId && $rec->exProductId != $rec->productId) {
            self::recalc($rec->exProductId);
        }
        
        $rMvc = cls::get('rack_Racks');

        if($rec->exPosition) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->exPosition] = TRUE;
        }

        if($rec->position) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->position] = TRUE;
        }
        
        $rMvc->on_Shutdown($rMvc);
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Ако изтриваме етап, изтриваме всичките редове от този етап
    	foreach ($query->getDeletedRecs() as $id => $rec) {

            // Рекалкулираме количествата на продукта от изтрития палет
            self::recalc($rec->productId);

            // Премахваме записите в "Движения" за този палет
            rack_Movements::delete("#palletId = {$id}");
    	}
    }


    /**
     * Преизчислява наличността на палети за посочения продукт
     */
    public static function recalc($productId)
    {
        expect($productId);
        $query = self::getQuery();
        while($rec = $query->fetch("#productId = {$productId}")) {
            if(!$storeId) {
                $storeId = $rec->storeId;
            }
            $q += $rec->quantity;
        }
        
        rack_Products::save((object) array('id' => $productId, 'quantityOnPallets' => $q), 'quantityOnPallets');
 
        // Премахваме кеша за този склад
        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        core_Cache::remove('UsedRacksPossitions', $storeId);
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
        if(($action == 'delete' || $action == 'edit') && isset($rec->id)) {
            if(rack_Movements::fetch("#palletId = {$rec->id} && #state != 'closed'")) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Проверява дали указаната позиция е празна
     */
    public static function isEmpty($position, $storeId = NULL, &$error = NULL)
    {
        expect($position);

        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        $rec = self::fetch("#storeId = {$storeId} AND #position = '{$position}'");

        if($rec) {
            $error = "Тази позиция е заета";

            return FALSE;
        }
        
        $mRec = rack_Movements::fetch("#storeId = {$storeId} AND #positionTo = '{$position}' AND #state != 'closed'");
 
        if($mRec) {
            $error = "Към тази позиция има насочено движение";

            return FALSE;
        }

        return TRUE;
    }

    
    /**
     * Проверява дали указаната позиция е празна
     */
    public static function isEmptyOut($num, $row = NULL, $col = NULL, $storeId = NULL, &$error = NULL)
    {
        
        if(!$row) {
            $row = chr(ord('A')-1);
        }

        if(!$col) {
            $col = 0;
        }

        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }
        
        $query = self::getQuery();

        while($rec = $query->fetch("#storeId = {$storeId} AND #position LIKE '{$num}-%'")) {
            
            if(!$rec->position) continue;

            list($n, $r, $c) = explode('-', $rec->position);
            if($r > $row || $c > $col) {
                $error = "Има използвани палети извън тези размери";

                return FALSE;
            }
        }
        
        $mQuery = rack_Movements::getQuery();

        while($mRec = $mQuery->fetch("#storeId = {$storeId} AND #positionTo LIKE '{$num}-%'")) {

            if(!$mRec->positionTo) continue;

            list($n, $r, $c) = explode('-', $mRec->positionTo);
            if($r > $row || $c > $col) {
                $error = "Има насочени движения извън тези размери";

                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($mvc->haveRightFor('edit', $rec)) {
            if($rec->position) {
                $row->label = ht::createLink('⇔', array($mvc, 'edit', $rec->id, 'Mode' => 'move'), NULL, 'title=Преместване') . '&nbsp;' . $row->label;
                $row->label = ht::createLink('⇓', array($mvc, 'edit', $rec->id, 'Mode' => 'down'), NULL, 'title=Сваляне') . '&nbsp;' . $row->label;
            } else {
                $row->label = ht::createLink('⇑', array($mvc, 'edit', $rec->id, 'Mode' => 'up'), NULL, 'title=Качване') . '&nbsp;' . $row->label;
            }
        }

        $row->created = '<div style="font-size:0.8em;">' . $mvc->getVerbal($rec, 'createdOn') . ' ' . crm_Profiles::createLink($rec->createdBy) . '</div>';
    }
    
    
    
    /**
     * Връща масив с всички използвани палети
     */
    public static function getUsed($storeId = NULL)
    {
        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        if(!($res = core_Cache::get('UsedRacksPossitions', $storeId))) {
            $res = array();
            $query = self::getQuery();
            while($rec = $query->fetch("#storeId = {$storeId}")) {
                if($rec->position) {
                    $res[$rec->position] = $rec->productId;
                }
            }
            core_Cache::set('UsedRacksPossitions', $storeId, $res, 1440);
        }
        
        return $res;
    }

 
}
