<?php
/**
 * 
 * Складове
 * 
 * Мениджър на складове
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 * @TODO Това е само примерна реализация за тестване на продажбите. Да се вземе реализацията
 * от bagdealing.
 * 
 */
class store_Racks extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Стелажи';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
                     acc_plg_Registry, store_Wrapper, plg_Selected';
    
    
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
    /* var $listFields = 'id, name, tools=Пулт'; */
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('number',  'int',        'caption=Стелаж');
        $this->FLD('rows',    'int(max=8)', 'caption=Редове,mandatory');
        $this->FLD('columns', 'int',        'caption=Колони,mandatory');
        $this->FLD('specification',   'varchar(255)', 'caption=Спецификация');
        // $this->FLD('productGroupsId', 'key(mvc=cat_ProductGroups,select=name)', 'caption=Вид товари');
        $this->FLD('comment', 'text', 'caption=Коментар');        
    }
    
    
/*
    function on_PrepareEditForm($mvc, $form)
    {
        $currentStoreId = $mvc->Stores->getCurrent();
        $query = $mvc->getQuery();

        $query->where("#storeId = {$currentStoreId}");
        $query->orderBy('#number', 'DESC');

        $query->limit(1);
        $rec = $query->fetch();

        $form->renderVars['number'] = $rec->number+1;
        $form->renderVars['rows'] = $rec->rows;
        $form->renderVars['columns'] = $rec->columns;

        $storeRec = $mvc->Stores->fetch($currentStoreId);
        
        $form->setOptions('storeId', array($currentStoreId => $storeRec->name));
    }

    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        static $pallets, $spec;

        if(!$pallets) {
            $pallets = $mvc->Pallets->fetchAll();
        }

        $spec = toArray($rec->specification, TRUE);


        $row->description = "<table cellspacing=0 border=0>";
        $row->description .= "<caption style='text-align:left;background-color:#FFCC66;padding:3px;border:solid 1px #aaa;'>[#ACT#]" . ' ' . $row->comment .  '</caption>';
        
 
        for($x=$rec->rows-1; $x>=0; $x--) {
            $row->description .= "<tr>";
            for($y=1; $y<=$rec->columns; $y++) {
                
                if($y%3 == 1) {
                    $border = "border:solid 1px #bbb; border-left:solid 2px #888;";
                } else {
                    $border = "border:solid 1px #bbb; ";
                }
                
                $xc = chr($x+ord('A'));
                $yc = $y;

                $coord = $Xcoord . $y;
                
                if($spec[$xc . $yc] == '*' || ($spec[$xc] == '*' && !$spec[$xc . $yc] )) {
                    $color = "background-color:#CCC;color:#AAA;";
                } else {
                    $color = "color:#999;";
                }
                
                if($spec[$xc]) { 
                    if($spec[$xc] > 1.2 ) { 
                        $border .= " padding-top:5px;padding-bottom:6px;";
                    } elseif($spec[$xc] < 1.2) {
                        $border .= " padding-top:1px;padding-bottom:1px;";
                    }
                }

                if($spec[$xc . $yc]) {
                    if($spec[$xc . $yc] > 1.2  ) {
                        $border .= " padding-top:5px;padding-bottom:6px;";
                    } elseif($spec[$xc . $yc] < 1.2 ) {
                        $border .= " padding-top:1px;padding-bottom:1px;";
                    }
                }

                if($pallet = $pallets[$rec->number. $xc . $yc]) {
                    $used = TRUE;
                    $pallet = $mvc->Pallets->recToVerbal($pallet);
                    $caption = HT::getLink( $xc . $yc, array('Pallets', 'list', 'palletId' => $pallet->id), FALSE, array('title' => "{$pallet->product}, {$pallet->dimensions}"));
                    $caption = $caption->getContent();
                } else {
                    $caption = $xc . $yc;
                }
                
                $row->description .= "<td style='font-family:Arial;font-weight:bold;font-size:0.7em;{$color}{$border}'>";
                
                $row->description .= $caption;

                $row->description .= "</td>";
            }
            $row->description .= "</tr>";
        }
        $row->description .= "</table>";


        $row->description = new ET($row->description);
        
        if(haveRole('admin,store_manager')) {
            $url = toUrl(array($mvc->className, 'view', 'id' => $rec->id, 'ret_url' =>  getCurrentUrl() ));
            $row->description->append(HT::getLink($rec->number, $url), 'ACT'); 
            
            if(!$used) {
                $row->description->append('&nbsp;', 'ACT'); 
                $url = array($mvc->className, 'delete', 'id' => $rec->id, 'ret_url' =>  getCurrentUrl() ) ;
                $row->description->append(HT::getLink("<img src=" . sbf('img/cross.png') . " border=0 align=absmiddle>",  $url, tr('Наистина ли желаете записът да бъде изтрит?')), 'ACT');
            }
        } else {
            $row->description->append($row->number, 'ACT');
        }

    }


    function on_PrepareListFields($mvc, &$data)
    {
        $data->listFields = "description=Стелажи";
    }


    function on_PrepareListQuery($mvc, $data)
    {
        $currentStoreId = $mvc->Stores->getCurrent();
        $data->query->where("#storeId = {$currentStoreId}");

        $storeName = $mvc->Stores->getTitleById($currentStoreId);

        $mvc->title = "Стелажи в \"$storeName\"";
    }


    function getCoordinates($pos)
    {
        $pos = strtoupper(trim($pos));

        $pos = str_replace( array(' ', '-', '.', ':'), array('', '', '', ''), $pos);

        $pos = str_replace( array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'), array('-A-', '-B-', '-C-', '-D-', '-E-', '-F-', '-G-', '-H-'), $pos);

        $coords = explode('-', $pos);

        $storeId = $this->Stores->getCurrent();
        $number  = $coords[0];
        $row     = 1+ord($coords[1]) - ord('A');
        $column  = $coords[2];

        if(!$this->fetch("#storeId = {$storeId} AND #number = {$number} AND #rows >= {$row} AND #columns >= $column")) error("Не съсществува място с координати|* {$pos}");

        return $coords;
    }

    function canonic($pos)
    {   
        $c = $this->getCoordinates($pos);

        return $c[0] . '-' . $c[1] . '-' . $c[2];
    } 
 */    
	
    
    /**
     * Имплементация на @see intf_Register::getAccItemRec()
     * 
     */
    function getAccItemRec($rec)
    {
    	return (object)array(
    		'title' => $rec->name
    	);
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