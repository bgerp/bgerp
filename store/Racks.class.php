<?php
/**
 * Стелажи
 */
class store_Racks extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стелажи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_LastUsedKeys, 
                     acc_plg_Registry, store_Wrapper';
    
    
    var $lastUsedKeys = 'storeId';

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
    var $canDelete = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 10;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'rackView';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId',       'key(mvc=store_Stores,select=name)', 'caption=Склад, input=hidden');
        $this->FLD('num',           'int',                              'caption=Стелаж №');
        $this->FLD('rows',          'enum(1,2,3,4,5,6,7,8)',            'caption=Редове,mandatory');
        $this->FLD('columns',       'int(max=24)',                      'caption=Колони,mandatory');
        $this->FLD('specification', 'varchar(255)',                     'caption=Спецификация');
        $this->FLD('comment',       'text',                             'caption=Коментар');
        $this->FNC('rackView',      'text',                             'caption=Стелажи');
    }
    
    
    /**
     * Смяна на заглавието
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @
     */
    function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
    	$data->title = "Стелажи в СКЛАД № {$selectedStoreId}";
    }    
    

    /**
     * Форма за add/edit на стелаж 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        // Взема селектирания склад
    	$selectedStoreId = store_Stores::getCurrent();
    	
    	$form = $data->form;
        $form->setDefault('storeId', $selectedStoreId);
        
        // В случай на add
        if (!$data->form->rec->id) {
        	$query = $mvc->getQuery();
        	$where = "1=1";
            $query->limit(1);
            $query->orderBy('num', 'DESC');        
    
            while($recRacks = $query->fetch($where)) {
                $lastNum = $recRacks->num;
            }

            $data->form->setReadOnly('num', $lastNum + 1);        	
        	$data->form->setDefault('rows', 7);
            $data->form->setDefault('rows', 7);
            $data->form->setDefault('columns', 24);
        }
    }

    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('id');
    }
    
    
    /**
     * Визуализация на стелажите 
     *  
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $palletsInStoreArr = self::getPalletsInStore();
        
        // array letter to digit
        $rackRowsArr = array('A' => 1,
                             'B' => 2,
                             'C' => 3,
                             'D' => 4,
                             'E' => 5,
                             'F' => 6,
                             'G' => 7,
                             'H' => 8);
        
        // array digit to letter
        $rackRowsArrRev = array('1' => A,
                                '2' => B,
                                '3' => C,
                                '4' => D,
                                '5' => E,
                                '6' => F,
                                '7' => G,
                                '8' => H);
        
        // html
        $html = "<div style='border: solid 1px #cccccc; 
                             padding: 5px; 
                             background: #eeeeee;'>";
         
        $html .= "<div style='clear: left; 
                              padding: 5px; 
                              font-size: 20px; 
                              font-weight: bold; 
                              color: green;'>";
                              
        $html .= $rec->num;
        
        // Ако има права за delete добавяме линк с икона за delete
        if ($mvc->haveRightFor('delete', $rec)) {
	        $delImg = "<img src=" . sbf('img/16/delete-icon.png') . " style='position: relative; top: 1px;'>";
	        $delUrl = toUrl(array($mvc, 'delete', $rec->id, 'ret_url' => TRUE));
	        $delLink = ht::createLink($delImg, $delUrl);
	        
            $html .= " " . $delLink;
        }
        
        $html .= "</div>";
        
        $html .= "<table cellspacing='1' style='clear: left;'>";
     
        // За всеки ред от стелажа
        for ($r = $rec->rows; $r >= 1; $r--) {
            $html .= "<tr>";
            
            // За всяка колона от стелажа
            for ($c = 1; $c <= $rec->columns; $c++) {
                $html .= "<td style='font-size: 14px; text-align: center; width: 32px; background: #ffffff;'>";
                    
                $palletPlace = $rec->id . "-" . $rackRowsArrRev[$r] . "-" .$c;

                // Ако има палет на това палет място
                if (isset($palletsInStoreArr[$palletPlace])) {
                    $html .= "<b>" . Ht::createLink($rackRowsArrRev[$r] . $c, 
                                                    array('store_Pallets', 
                                                          'list',
                                                          $palletsInStoreArr[$palletPlace]['palletId']), 
                                                    FALSE, 
                                                    array('title' => $palletsInStoreArr[$palletPlace]['title'])) . "</b>";   
                // Ако няма палет на това палет място
                } else {
                    $html .= "<span style='color: #aaaaaa;'>" . $rackRowsArrRev[$r] . $c . "</span>";
                }
                    
                $html .= "</td>";               
            }
            
            $html .= "</tr>";                    
        }
        
        $html .= "</table>";
        
        $html .= "</div>";
        // END html

        $row->rackView = $html;
    }

    
    /*
     * Създава масив със всички палети от даден склад
     * 
     * @return array $palletsInStoreArr
     */
    function getPalletsInStore()
    {
        $selectedStoreId = store_Stores::getCurrent();
           
    	$queryPallets = store_Pallets::getQuery();
        $where = "#storeId = {$selectedStoreId}";

        while($recPallets = $queryPallets->fetch($where)) {
        	// Само тези палети, които са 'На място' и не са 'На пода'
        	if ($recPallets->position != 'На пода' && $recPallets->state == 'closed') {
	            $positionArr = explode("-", $recPallets->position);
	            
                $rackId     = $positionArr[0];
                $rackRow    = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition   = $rackId . "-" . $rackRow . "-" . $rackColumn;
                $palletDimensions = number_format($recPallet->width, 2) . "x" . number_format($recPallets->depth, 2) . "x" . number_format($recPallets->height, 2) . " м, max " . $recPallets->maxWeight . " кг";
                
                $recProducts = store_Products::fetch("#id = {$recPallets->productId}");
                $productName = cat_Products::fetchField("#id = {$recProducts->name}", 'name');
                
	            $palletsInStoreArr[$palletPosition]['palletId'] = $recPallets->id;

	            // title 
	            $title = "Продукт ID " . $recProducts->id . ", " . $productName . ", " . $recPallets->quantity . " бр., палет: " . $palletDimensions;
	            $palletsInStoreArr[$palletPosition]['title'] = $title; 	            
        	}     
        }
        
        return $palletsInStoreArr;
    }
    
    
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