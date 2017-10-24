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
    
    var $singleTitle = 'Стелаж';

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, rack_Wrapper,plg_SaveAndNew';
    
    
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
    var $listFields = 'num,total,used,reserved,free=Палет-места->Свободни,rows,columns';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'rack_RackDetails';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutRack.shtml';


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/rack.png';
    

    var $rowToolsSingleField = 'num';
    

    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,storeId';


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
        $this->FLD('num', 'int(max=100)', 'caption=Стелаж,mandatory,smartCenter');
        $this->FLD('rows', 'enum(A,B,C,D,E,F,G,H,I,J,K,L,M)', 'caption=Редове,mandatory,smartCenter');
        $this->FLD('columns', 'int(max=100)', 'caption=Колони,mandatory,smartCenter');
        $this->FLD('comment', 'richtext(rows=5, bucket=Comments)', 'caption=Коментар');
        $this->FLD('total', 'int', 'caption=Палет-места->Общо,smartCenter,input=none');
        $this->FLD('used', 'int', 'caption=Палет-места->Използвани,smartCenter,input=none');
        $this->FLD('reserved', 'int', 'caption=Палет-места->Запазени,smartCenter,input=none');

        $this->FLD('constrColumnsStep', 'int', 'caption=Брой палети между две колони->Палети,smartCenter');
        
        $this->setDbUnique('storeId,num');
    }
    


    public function act_Show()
    {
        $storeId = store_Stores::getCurrent();
        $pos = Request::get('pos');

        list($n, $r, $c) = explode('-', $pos);

        $n = (int) $n;

        if($n) {
            $rec = rack_Racks::fetch(array("#storeId = [#1#] AND #num = [#2#]", $storeId, $n));
            if($rec) {
                return Request::forward(array('Act' => 'single', 'id' => $rec->id, 'pos' => "{$r}-{$c}"));
            }
        }
        
        return new Redirect(array($this), "Позицията не може да бъде открита", 'error');
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
        $rec = &$form->rec;

        if(!$rec->id) {
            $storeId = store_Stores::getCurrent();
            $query = self::getQuery();
            $query->orderBy("#num", 'DESC');
            $lastRec = $query->fetch();
            
            if($lastRec) {
                $rec->num = $lastRec->num+1;
                $rec->rows = $lastRec->rows;
                $rec->columns = $lastRec->columns;
                $rec->constrColumnsStep = $lastRec->constrColumnsStep;
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
        $data->title = 'Стелажи в склад |*<b style="color:green">' . store_Stores::getTitleById($storeId) . "</b>";
        
        $data->query->orderBy('#num', 'ASC');
    }


    /**
     * Използваемо ли е посоченото стелажно място?
     */
    public static function isPlaceUsable($position, $productId = NULL, $storeId = NULL, &$error = NULL, &$status = NULL)
    {
        expect($position);

        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        list($num, $row, $col) = explode('-', $position);

        expect($num && $row && $col, $num, $row, $col);

        $rec = self::fetch("#storeId = {$storeId} AND #num = {$num}");

        if(!$rec) {
            $error = "Несъществуващ номер на стилаж в този склад";
            $status = 'bad_rack_num';

            return FALSE;
        }

        if($row < 'A' || $row > $rec->rows) {
            $error = "Несъществуващ ред на стилажа";
            $status = 'bad_row';

            return FALSE;
        }
        
        
        if($col < 1 || $col > $rec->columns) {
            $error = "Несъществуваща колона на стилажа";
            $status = 'bad_column';

            return FALSE;
        }

        $dRec = rack_RackDetails::fetch("#rackId = {$rec->id} && #row = '{$row}' AND #col = {$col}");

        if($dRec) {
            if($dRec->status == 'unusable') {
                $error = "Мястото е неизползваемо";
                $status = 'unusable';

                return FALSE;
            }

            if($dRec->status == 'reserved' && $dRec->productId != $productId) {
                $error = "Мястото е запазено";
                $status = 'reserved';

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

            if(!rack_Pallets::isEmptyOut($rec->num, $rec->rows, $rec->columns, NULL, $error)) {
                $form->setError("rows,columns", $error);
            }
            
            if($rec->id && rack_RackDetails::fetch("#rackId = {$rec->id} AND (#row > '{$rec->rows}' OR #col > {$rec->columns}) AND #status != 'usable'")) {
                $form->setWarning("rows,columns", 'Информацията за запазени или неизползваеми места извън новите размери ще бъде изтрита');
            }
        }
    }


    /**
     * Преобразува запис от модела в обект с вербални стойности
     */
    static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);

        $row->num = " №" . $row->num;
        
        return $row;
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
            $row->places = self::renderRack($rec);
            $row->comment .= "<div style='font-size:0.8em;color:999;'>" .
                tr("Двоен клик върху клетка, за да я редактирате или задръжте мишката за информация") .
                "</div>";
        }

        if($rec->total) {
            $row->free = $rec->total - $rec->reserved - $rec->used;
            $row->free .= ' (' . round(100 * $row->free/$rec->total, 2) . '%)';
            $row->free = "<span style='color:green;'>" . $row->free . "</div>";
        }
    }


    /**
     *
     */
    static function renderRack($rec)
    {
        $row = $rec->rows;
        $hlPos = Request::get('pos');
        

        $hlFullPos = "{$rec->num}-{$hlPos}";

        
        list($unusable, $reserved) = rack_RackDetails::getunUsableAndReserved();
        $used = rack_Pallets::getUsed();
        list($movedFrom, $movedTo) = rack_Movements::getExpected();
        
        $hlProdId = $used[$hlFullPos];


        while($row >= 'A') {

            $res .= "<tr>";

            for($i = 1; $i <= $rec->columns; $i++) {
                
                $attr = array();
                
                $attr['style'] = 'color:#ccc;';
                 
                $pos = "{$row}-{$i}";
                $posFull = "{$rec->num}-{$row}-{$i}";

                $hint = '';
                
                $title = NULL;
                
                $url = toUrl(array('rack_RackDetails', 'add', 'rackId' => $rec->id, 'row' => $row, 'col' => $i));
                $attr['ondblclick'] = "document.location='{$url}';";
                $pId = NULL;

                // Ако е заето с нещо
                if(!isset($title) && ($pId = $used[$posFull])) {
                    $prodRec = rack_Products::fetch($pId);
                    $prodTitle = rack_Products::getVerbal($prodRec, 'productId');
                    $attrA = array();
                    $attrA['title'] = $prodTitle;
                    $color = self::getColor($prodTitle, 0, 110);
                    $bgColor = self::getColor($prodTitle, 130, 240);
   
                    $attrA['style'] = "color:#{$color};background-color:#{$bgColor};";

                    $title = ht::createLink($pos, array('rack_Pallets', 'list', 'pos' => "{$rec->num}-{$pos}"), NULL, $attrA);
                }

                // Ако е неизползваемо
                if(!isset($title) && $unusable[$posFull]) {
                    $title = "&nbsp;";
                }

                // Ако е резервирано за нещо
                if(!isset($title) && ($pId = $reserved[$posFull])) { 
                    $title = $pos;
                    $attr['style'] = 'color:#fbb;';
                    $hint = tr('Запазано място');

                    if($pId > 0) {
                        $prodRec = rack_Products::fetch($pId);
                        $prodTitle = rack_Products::getVerbal($prodRec, 'productId');
                        $hint = tr('Запазано място за') . ' ' . $prodTitle;
                    }
                }
                
                // Ако се очаква палет
                if(!isset($title) && $movedTo[$posFull]) {
                    $title = $pos;
                    $attr['style'] = 'color:#6c6;';
                    $hint = tr('Очаква се палет');
                }
                
                // Ако ще се премества палет
                if($movedFrom[$posFull]) {
                    $attr['style'] .= ';text-decoration:underline;';
                    $hint = tr('Предстои преместване');
                }
              
                if(!isset($title)) {
                    $title = $pos;
                    $attr['style'] = 'color:#ccc;';
                }

                if($pos == $hlPos) {
                    $attr['class'] .= ' rack-hl';
                } elseif(isset($pId) && $hlProdId == $pId) {
                    $attr['class'] .= ' rack-same';
                }
                
                if($c = $rec->constrColumnsStep) {
                    if($i % $c == 1) {
                        $attr['style'] .= 'border-left:solid 2px #999;';
                    }
                    if($i % $c == 0) {
                        $attr['style'] .= 'border-right:solid 2px #999;';
                    }
                }

                $attr['nowrap'] = 'nowrap';
                $attr['style'] .= 'font-size:0.8em;';

                if($hint) {
                    $attr['title'] = "{$hint}";
                }

                $res .= ht::createElement('td', $attr, $title);
            }

            $res .= "</td>";

            $row = chr(ord($row)-1);
        }

        $res = "<table class='listTable'>{$res}</table>";

        return $res;
    }
    


    /**
     *
     */
    public static function getColor($title, $min = 0, $max=255)
    {
        $hash = md5($title);

        $r = hexdec(substr($hash, 0, 4));
        $g = hexdec(substr($hash, 4, 4));
        $b = hexdec(substr($hash, 8, 4));
        $m = $max - $min;

        expect($min < $max);

        $r = $r % $m + $min;
        $g = $g % $m + $min;
        $b = $b % $m + $min;

    
        $r = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
        
        return "{$r}{$g}{$b}";
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
        if($action == 'delete' && isset($rec->id)) {
            if(!$rec->num) {
                $rec->num = $mvc->fetch($rec->id)->num;
            }
            if(!rack_Pallets::isEmptyOut($rec->num)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща масив със всички възможно позиции за палети в дадения склад
     */
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
                if(!$res[$dRec->row][$dRec->col]) {
                    if($dRec->status == 'unusable') {
                        $res[$dRec->row][$dRec->col] = 'N';
                    } elseif($dRec->status == 'reserved') {
                        $res[$dRec->row][$dRec->col] = 'R';
                    }
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
                if($rec) {
                    $rec->used = $usedCnt;
                    $mvc->save_($rec, 'used');
                }
            }
            unset($mvc->updateRacks[$position]);
        }
    }
    

    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $mvc->clearDetails($id);

        $mvc::on_AfterUpdateMaster($mvc, $res, $id);
    }


    /**
     * Обновява статистиката за стелажа
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        $rec = $mvc->fetch($id);
        
        if($rec) {
            $rec->total = $rec->columns * (ord($rec->rows) - ord('A') + 1);

            $dQuery = rack_RackDetails::getQuery();

            $dQuery->where("#rackId = {$rec->id} && #status = 'unusable'");

            $rec->total -= $dQuery->count();
            
            $dQuery = rack_RackDetails::getQuery();

            $dQuery->where("#rackId = {$rec->id} && #status = 'reserved'");

            $rec->reserved = $dQuery->count();
            
            if(!$rec->used) {
                $rec->used = 0;
            }

            $mvc->save_($rec, 'total,reserved,used');

            core_Cache::remove('getUnusableAndReserved', $rec->storeId);
        }
    }
    
    
    /**
     * След изтриване на записи
     *
     * @param core_Mvc $mvc
     * @param int $numRows  
     * @param core_Query $query
     * @param string|array
     *
     * @return bool Дали да продължи обработката на опашката от събития
     */
    public static function on_AfterDelete($mvc, $numRows, $query, $cond)
    {   
        $dR = $query->getDeletedRecs();
 
        if(is_array($dR)) {
            foreach($dR as $rec) {
                core_Cache::remove('getUsableAndReserved', $rec->storeId);
                rack_RackDetails::delete("#rackId = {$rec->id}");
            }
        }
    }


    /**
     * Изтрива излишните детайли, след преоразмеряване
     */
    function clearDetails($id)
    {
        $rec = self::fetch($id);

        if($rec) {
            $dQuery = rack_rackDetails::getQuery();
            while($dRec = $dQuery->fetch("#rackId = {$rec->id}")) {
                if(($dRec->row > $rec->rows) || ($dRec->col > $rec->columns) || ($dRec->status == 'usable')) {
                    rack_rackDetails::delete($dRec->id);
                }
            }
        }
    }

    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr('Стелаж') . " №{$rec->id}";
    }
}
