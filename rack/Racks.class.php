<?php



/**
 * Стелажи
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_Racks extends core_Master
{
	
    /**
     * Заглавие
     */
    var $title = 'Стелажи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, rack_Wrapper';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,rows,columns,total,used,reserved';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'rack_RackDetails';
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/rack.png';
    

    var $rowToolsSingleField = 'num';
    

    /**
     * Масив със стелажи за обновяване
     */
    var $updateRacks = array();

    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('num', 'int(max=100)', 'caption=Стелаж №,mandatory,smartCenter');
        $this->FLD('rows', 'enum(A,B,C,D,E,F,G,H,I,J,K,L,M)', 'caption=Редове,mandatory,smartCenter');
        $this->FLD('columns', 'int(max=100)', 'caption=Колони,mandatory,smartCenter');
        $this->FLD('comment', 'richtext(rows=5)', 'caption=Коментар');
        $this->FLD('total', 'int', 'caption=Палет-места->Общо,smartCenter');
        $this->FLD('used', 'int', 'caption=Палет-места->Използвани,smartCenter');
        $this->FLD('reserved', 'int', 'caption=Палет-места->Резервирани,smartCenter');

        $this->FLD('constrColumnsStep', 'int', 'caption=Брой палети между две колони->Палети,smartCenter');
        
        $this->setDbUnique('storeId,num');
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
        $data->title = 'Стелажи в склад |*<b style="color:green">' . store_Stores::getTitleById($storeId) . "</b>";
        
        $data->query->orderBy('#num', 'ASC');
    }


    /**
     * Използваемо ли е посоченото стелажно място?
     */
    public static function isPlaceUsable($place, $storeId = NULL, &$error = NULL)
    {
        expect($place);

        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        list($num, $row, $col) = explode('-', $place);

        expect($num && $row && $col, $num, $row, $col);

        $rec = self::fetch("#storeId = {$storeId} AND #num = {$num}");

        if(!$rec) {
            $error = "Несъществуващ номер на стилаж в този склад";

            return FALSE;
        }

        if($row < 'A' || $row > $rec->rows) {
            $error = "Несъществуващ ред на стилажа";

            return FALSE;
        }
        
        
        if($col < 1 || $col > $rec->columns) {
            $error = "Несъществуваща колона на стилажа";

            return FALSE;
        }

        $dRec = rack_RackDetails::fetch("#rackId = {$rec->id} && #row = '{$row}' AND #col = {$col}");

        if($dRec) {
            if($dRec->status == 'unusable') {
                $error = "Мястото е неизползваемо";

                return FALSE;
            }

            if($dRec->status == 'reserved') {
                $error = "Мястото е резервирано";

                return FALSE;
            }
        }

        return TRUE;
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
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        if($fields['-single']) {
            $row->comment .= self::renderRack($rec);
        }
    }


    static function renderRack($rec)
    {
        $pallets = self::getPalletsOnRack($rec->storeId, $rec->num);

        $row = $rec->rows;

        while($row >= 'A') {

            $res .= "<tr>";

            for($i = 1; $i <= $rec->columns; $i++) {
                $style = 'color:#ccc;';
                
                $title = "{$row}-{$i}";
                $hint = '';
                
                switch($pallets[$row][$i]) {
                    case 'U':
                        $style = 'color:black;';
                        break;
                    case 'F':
                        $style = 'color:blue;';
                        $hint = tr('Ще бъде преместен');
                        break;
                    case 'M':
                        $style = 'color:green;';
                        $hint = tr('Очаква се палет');
                        break;
                    case 'R':
                        $style = 'color:#fbb;';
                        $hint = tr('Запазано място');
                        break;
                    case 'N':
                        $title = '&nbsp;';
                        break;
                }

                if($style) {
                    $style = " style='{$style}'";
                }
                if($hint) {
                    $hint = " title='{$hint} {$pallets[$row][$i]}'";
                }
                $res .= "<td{$style}{$hint}>{$title}</td>";
            }

            $res .= "</td>";

            $row = chr(ord($row)-1);
        }

        $res = "<table class='listTable'>{$res}</table>";

        return $res;
    }

    static function getPalletsOnRack($storeId, $num)
    {
        $pQuery = rack_Pallets::getQuery();
        $pQuery->where("#storeId = {$storeId} AND #position LIKE '{$num}-%'");
        while($pRec = $pQuery->fetch()) {
            list($num, $row, $col) = explode('-', $pRec->position);
            $res[$row][$col] = 'U';
        }
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#storeId = {$storeId} AND #state != 'closed' AND (#positionTo LIKE '{$num}-%' OR #position LIKE '{$num}-%')");
        while($mRec = $mQuery->fetch()) {
            if($mRec->positionTo) {
                list($num1, $row, $col) = explode('-', $mRec->positionTo);  
                if($num1 == $num) {
                    $res[$row][$col] = 'M';
                }
            }
            if($mRec->position) {
                list($num1, $row, $col) = explode('-', $mRec->position);
                if($num1 == $num) {
                    $res[$row][$col] = 'F';
                }
            }

        }

        $rec = self::fetch("#storeId = {$storeId} AND #num = {$num}");

        if($rec) {
            $dQuery = rack_RackDetails::getQuery();
            $dQuery->where("#rackId = {$rec->id}");
            while($dRec = $dQuery->fetch()) {
                if($dRec->status == 'unusable') {
                    $res[$dRec->row][$dRec->col] = 'N';
                } elseif($dRec->status == 'reserved') {
                    $res[$dRec->row][$dRec->col] = 'R';
                }
            }
        }


        return $res;
    }


    /**
     * Обновява броя на използваните места на шътдаун
     */
    public static function on_Shutdown($mvc)
    {
        foreach($mvc->updateRacks as $position => $true) {
            list($storeId, $num, $row, $col) = explode('-', $position);
            if($storeId > 0 && $num > 0) {
 
                // Изчисляваме заетите палети
                $pQuery = rack_Pallets::getQuery();
                $pQuery->where("#storeId = {$storeId} AND #position LIKE '{$num}-%'");
                $usedCnt = $pQuery->count();
                
                // Записваме в информацията за палета
                $rec = $mvc->fetch("#storeId = {$storeId} AND #num = {$num}");
                $rec->used = $usedCnt;
                $mvc->save_($rec, 'used');
            }
            unset($mvc->updateRacks[$position]);
        }
    }


    /**
     * Обновява статистиката за стелажа
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        $rec = $mvc->fetch($id);

        $rec->total = $rec->columns * (ord($rec->rows) - ord('A') + 1);

        $dQuery = rack_RackDetails::getQuery();

        $dQuery->where("#rackId = {$rec->id} && #status = 'unusable'");

        $rec->total -= $dQuery->count();
        
        $dQuery = rack_RackDetails::getQuery();

        $dQuery->where("#rackId = {$rec->id} && #status = 'reserved'");

        $rec->reserved = $dQuery->count();

        $mvc->save_($rec, 'total,reserved');
    }

}
