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
    public $listFields = 'label,productId,quantity,position,createdBy,createdOn';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden,column=none');
        $this->FLD('productId', 'key(mvc=store_Products, select=productId,allowEmpty)', 'caption=Продукт,silent,refreshForm,mandatory');
        $this->FLD('quantity', 'int', 'caption=Количество,mandatory');
        $this->FLD('label', 'varchar(32)', 'caption=Етикет');
        $this->FLD('comment', 'varchar', 'caption=Коментар,column=none');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция');
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
        
        // Дефолт за последното количество
        if($rec->productId && !$rec->quantity) {
            $query = self::getQuery();
            $query->orderBy('#createdOn', 'DESC');
            $exRec = $query->fetch("#productId = {$rec->productId}");
            if($exRec) {
                $rec->quantity = $exRec->quantity;
            }
        }
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
                
                if(!rack_Racks::isPlaceUsable($rec->positionTo, $rec->storeId, $error)) {

                    $form->setError('positionTo', $error);
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
        
        $data->query->orderBy('#createdOn', 'DESC');

        $data->listFilter->showFields = 'productId';  //, HistoryResourceId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $rec = $data->listFilter->input();
        if(!$rec->productId) {
            $rec->productId = Request::get('productId', 'int');
            $data->listFilter->setDefault('productId', $rec->productId);
        }

        if($rec->productId) {
            $data->query->where("#productId = {$rec->productId}");
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

            if(!$rec->label) {
                $rec->label = '#' . $rec->id;
                $mvc->save_($rec, 'label');
            }

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
            $q += $rec->quantity;
        }

        rack_Products::save((object) array('id' => $productId, 'quantityOnPallets' => $q));
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
        
        $mRec = rack_Movements::fetch("#storeId = {$storeId} AND #positionTo = '{$position}'");

        if($rec) {
            $error = "Към тази позиция има насочено движение";

            return FALSE;
        }

        return TRUE;
    }

    
}
