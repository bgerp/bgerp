<?php
/**
 * 
 * Палети
 */
class store_Pallets extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Палети';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
                     acc_plg_Registry, store_Wrapper';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'productId, quantity, storeId, rackNum, row, column, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        // $this->FLD('productId', 'key(mvc=cat_Products, select=title)', 'caption=Съдържание->Продукт');
        $this->FLD('quantity',  'int',                                 'caption=Съдържание->Количество');
        $this->FLD('comment',   'varchar(256)',                        'caption=Коментар');
        $this->FLD('width',     'double(decimals=2)', 'caption=Дименсии->Широчина [м]');
        $this->FLD('height',    'double(decimals=2)', 'caption=Дименсии->Височина [м]');
        $this->FLD('weight',    'double(decimals=2)', 'caption=Дименсии->Тегло [kg]');
        $this->FLD('storeId',   'key(mvc=store_Stores,select=name)',   'caption=Място->Склад');
        $this->FLD('rackNum',   'int',                                 'caption=Място->Стелаж');
        $this->FLD('row',       'enum(A,B,C,D,E,F,G,H)',               'caption=Място->Ред');
        $this->FLD('column',    'int',                                 'caption=Място->Колонa');
    }
    

/*    
    function on_PrepareEditForm($mvc, $form)
    {
        $form->FNC('possition', 'string(10)', 'caption=Качване->Позиция,input');
        $form->setOptions('possition', array(''=>'', 'авто'=>'авто'), 'editable'); 
        
        if(!$productId = Request::get('productId', 'int')) error('Липсващо id на продукта');

        $form->setReadOnly('productId', Request::get('productId', 'int'));

        $query = $mvc->getQuery();

        $query->orderBy("createdOn DESC");
        $query->limit(1);

        $rec = $query->fetch("#productId = {$productId} AND #storeId = " .  $mvc->Stores->getCurrent());
        
        if($rec->quantity) {
            $form->setDefault('quantity', $rec->quantity);
        }
    }

    function on_AfterSave($mvc, $rec)
    {
        // След като пороменяме информацията за палетите, трябва да ре-калкулираме 
        // количествата в записа на продукта
        $mvc->updateOnPallets($rec->productId);

        // Ако имаме зададени координати, трябва да генерираме 
        // Заявка за преместване на тези координати

        if($rec->possition) {
            if($rec->possition != 'авто') {
                $move->possition = $mvc->Racks->canonic($rec->possition);
            }
            $move->palletId  = $rec->id;
            $move->quantity  = $rec->quantity;
            $move->kind = 'upload';
            $mvc->Movements->save($move);
        }
    }

    function on_BeforeSave($mvc, $rec)
    {
        $rec->storeId = $mvc->Stores->getCurrent();
    }

    function on_BeforeDelete($mvc, $query, $cond)
    {
        $tmpQuery = $mvc->getQuery();
        while($rec = $tmpQuery->fetch($cond)) {
            $query->deleted[$rec->productId] = $rec->productId;
        }
    }

    function on_AfterDelete($mvc, $query, $cond)
    {
        if(count($query->deleted)) {
            foreach($query->deleted as $productId) {
                $mvc->updateOnPallets($productId);
            }
        }
    }

    function on_PrepareListQuery($mvc, $data)
    {
        $currentStoreId = $mvc->Stores->getCurrent();
        
        $storeName = $mvc->Stores->getTitleById($currentStoreId);

        if($productId = Request::get('productId')) {
            $data->query->where("#productId = {$productId}");
            if(Request::get('all')) {
                $mvc->title  = 'Палети с|* "' . $mvc->Products->getTitleById($productId) .'"| във всички складове';
            } else {
                $data->query->where("#storeId = {$currentStoreId}");
                $mvc->title  = 'Палети с|* "' . $mvc->Products->getTitleById($productId) ."\" |в|* \"{$storeName}\"" ;
            }
        } else {
            $data->query->where("#storeId = {$currentStoreId}");
            $mvc->title = "Палети в|* \"{$storeName}\"";
        }

        if($palletId = Request::get('palletId')) {
            $data->query->where("#id = {$palletId}");
            $mvc->title = "Палет |*{$palletId} |в|* \"{$storeName}\"";
        }
        
        $data->query->orderBy("#createdOn=DESC,#rackNum,#row,#column");
    }


    function on_PrepareListFields($mvc, $data)
    {
        if( Request::get('productId') &&  Request::get('all') ) {
            $data->listFields = 'id,product=Продукт,dimensions=Дименсии,storeId=Склад,possition=Позиция,actions=Действия,comment';
        } else {
            $data->listFields = 'id,product=Продукт,dimensions=Дименсии,possition=Позиция,actions=Действия,comment';
        }
    }





    function on_beforeRenderToolbar($mvc, &$tpl)
    {
        if( $productId = Request::get('productId') ) {
            if( !Request::get('all') )  {
                $tpl = HT::makeBtn("Във всички складове", array($mvc->className, 'productId' =>$productId, 'all' => 1) );
            } else {
                $tpl = HT::makeBtn("Само в текущия склад", array($mvc->className, 'productId' =>$productId) );
            }
        }
        return EF_SIGNAL_CANCEL;
    }


    function on_AfterRecToVerbal($mvc, $row, $rec)
    {

        // Продукт, Габарити, Позиция, Коментар

        $row->product = $row->productId . " x " . $row->quantity;

        // Габарити
        $width  = $rec->width?$rec->width:STORES_PALLET_WIDTH;
        $height = $rec->height?$rec->height:STORES_PALLET_HEIGHT;
        
        $row->dimensions = "{$width}x{$height} m";
        if($rec->weight) {
            $row->dimensions .= ", {$rec->weight} кг";
        }

        // Позиция
        if($rec->rackNum) {
            $row->possition = $rec->rackNum . '-' . $rec->row . '-' . $rec->column;
        } else {
            $row->possition = tr('на пода');
            $row->ROW_ATTR  = " style='background-color:#DDDDFF'";
        }

        // Действия
        if($rec->rackNum) {
            $row->actions = HT::getLink("<img width=16 height=16 border=0 style='margin-left:10px;' src=" . sbf('img/down.gif') . ">", array('Movements', 'new', 'kind' => 'download', 'palletId' => $rec->id, 'ret_url' => getCurrentUrl() ));
            $row->actions->append(HT::getLink("<img width=16 height=16 border=0 style='margin-left:10px;' src=" . sbf('img/move.gif') . ">", array('Movements', 'new', 'kind' => 'move', 'palletId' => $rec->id, 'ret_url' => getCurrentUrl() )));
        } else {
            $row->actions = HT::getLink("<img width=16 height=16 border=0 style='margin-left:10px;' title='Качване' src=" . sbf('img/upload.gif') . ">", 
                                                                    array('Movements', 'new', 'kind' => 'upload', 'palletId' => $rec->id, 'ret_url' => getCurrentUrl()));
            $row->actions->append( HT::getLink("<img width=16 height=16 border=0 style='margin-left:10px;' title='Премахване' src=" . sbf('img/cross.png') . ">", array(), 'Наистина ли желаете да премахнете палета?') );  
        }

    }

 
        // Връща разбираемо за човека заглавие, отговарящо на записа
    function recGetTitle(&$rec) 
    {

        $productRec = $this->Products->fetch($rec->productId);

        $title = "{$rec->id} {$productRec->name} " ;

        return $title;
 
    }

    function getPossition($palletId)
    {   
        $rec = $this->fetch($palletId);
 
        return strtoupper("{$rec->rackNum}-{$rec->row}-{$rec->column}");
    }


    function setNewPossition($id, $pos)
    {
        $rec = $this->fetch($id);
        
        if($pos) {
            $coords = $this->Racks->getCoordinates($pos);

            $rec->rackNum = $coords[0];
            $rec->row = $coords[1];
            $rec->column = $coords[2];
        } else {
            $rec->rackNum = NULL;
            $rec->row = NULL;
            $rec->column = NULL;
        }

        $this->save($rec);
    }

    
    function isOccupate($pos)
    {  
        $coords = $this->Racks->getCoordinates($pos);
        
        $storeId = $this->Stores->getCurrent();
 
        if($this->fetch( "#storeId = {$storeId} AND #rackNum = " . $coords[0] . " AND #row = '" . $coords[1] . "' AND #column  = " . $coords[2] . "") ) {
            return TRUE;
        }
        
        return FALSE;
    }

    
    function fetchAll()
    {
        $storeId = $this->Stores->getCurrent();

        $query = $this->getQuery();

        while($rec = $query->fetch("#storeId = {$storeId}")) {
            $pallets[$rec->rackNum . $rec->row . $rec->column] =  $rec;
        }
        
        return $pallets;
    }
  
    
    function updateOnPallets($productId)
    {
        $rec = $this->Products->fetch($productId);
        $this->Products->save($rec, 'onPallets');
    }
    
 
    // Колко от този продукт е на палети?
    function howManyOnPallets($productId)
    {
        if(!$productId) return 0;

        $onPallets = 0;
        $query = $this->getQuery();
        while($r = $query->fetch("#productId = {$productId}")) {
            $onPallets += $r->quantity;
        }

        return $onPallets;
    }

    function findLastPalletByProduct($productId)
    {
        $query = $this->getQuery();
        $query->limit(1);
        $query->orderBy('createdOn DESC');
        $rec = $query->fetch("#productId = {$productId}");
        
        return $rec;
    }
*/        
	
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec  = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */        
    
    
}