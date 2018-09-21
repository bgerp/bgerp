<?php


/**
 * Стелажи
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Racks extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Стелажи';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Стелаж';
    
    
    /**
     * Брой елементи на страница
     */
    public $listItemsPerPage = 100;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, rack_Wrapper,plg_SaveAndNew,plg_Sorting';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,rack';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,rack';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,rack';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'num,free=Палет-места->Свободни,used,reserved,total,rows,columns';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'rack_RackDetails';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutRack.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/rack.png';
    
    
    /**
     * Поле за единичния изглед
     */
    public $rowToolsSingleField = 'num';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,storeId';
    
    
    /**
     * Масив със стелажи за обновяване
     */
    public $updateRacks = array();
    
    
    /**
     * Шаблон за заглавието
     */
    public $recTitleTpl = '|Стелаж|* [#num#]';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('num', 'int(max=100)', 'caption=Стелаж,mandatory,smartCenter');
        $this->FLD('rows', 'enum(A,B,C,D,E,F,G,H,I,J,K,L,M)', 'caption=Редове,mandatory,smartCenter');
        $this->FLD('columns', 'int(max=100)', 'caption=Колони,mandatory,smartCenter');
        $this->FLD('comment', 'richtext(rows=5, bucket=Comments)', 'caption=Коментар');
        $this->FLD('total', 'int', 'caption=Палет-места->Общо,smartCenter,input=none');
        $this->FLD('used', 'int', 'caption=Палет-места->Използвани,smartCenter,input=none');
        $this->FLD('reserved', 'int', 'caption=Палет-места->Запазени,smartCenter,input=none');
        
        $this->FLD('constrColumnsStep', 'int', 'caption=Брой палети на една основа->Палети,smartCenter');
        
        $this->setDbUnique('storeId,num');
    }
    
    
    public function act_Show()
    {
        $pos = Request::get('pos');
        
        list($n, $r, $c) = explode('-', $pos);
        
        $n = (int) $n;
        
        if ($n) {
            $rec = rack_Racks::getByNum($n);
            if ($rec) {
                
                return Request::forward(array('Act' => 'single', 'id' => $rec->id, 'pos' => "{$r}-{$c}"));
            }
        }
        
        return new Redirect(array($this), "Позицията|* {$pos} |не може да бъде открита", 'error');
    }
    
    
    /**
     * Връща записа за стелажа, според номера му в текущия склад
     */
    public static function getByNum($num)
    {
        $storeId = store_Stores::getCurrent();
        $rec = rack_Racks::fetch(array('#storeId = [#1#] AND #num = [#2#]', $storeId, $num));
        
        return $rec;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = &$form->rec;
        
        if (!$rec->id) {
            $storeId = store_Stores::getCurrent();
            $query = self::getQuery();
            $query->orderBy('#num', 'DESC');
            $lastRec = $query->fetch("#storeId = {$storeId}");
            
            if ($lastRec) {
                $rec->num = $lastRec->num + 1;
                $rec->rows = $lastRec->rows;
                $rec->columns = $lastRec->columns;
                $rec->constrColumnsStep = $lastRec->constrColumnsStep;
            }
        } else {
            $form->setReadOnly('num');
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Стелажи в склад |*<b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        
        $data->query->orderBy('#num', 'ASC');
    }
    
    
    /**
     * Проверява съществуваща ли е позицията
     *
     * @param string      $position
     * @param int         $productId
     * @param int         $storeId
     * @param string|null $error
     * @param string|null $error
     *
     * @return bool
     */
    public static function checkPosition($position, $productId, $storeId, &$error = null, &$rec = null)
    {
        list($num, $row, $col) = explode('-', $position);
        if (!($num && $row && $col)) {
            $error = 'Невалиден синтаксис';
            
            return false;
        }
        
        $rec = self::fetch("#storeId = {$storeId} AND #num = {$num}");
        if (empty($rec)) {
            $error = 'Несъществуващ номер на стелаж в този склад';
            
            return false;
        }
        
        if ($row < 'A' || $row > $rec->rows) {
            $error = 'Несъществуващ ред на стелажа';
            
            return false;
        }
        
        
        if ($col < 1 || $col > $rec->columns) {
            $error = 'Несъществуваща колона на стелажа';
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Използваемо ли е посоченото стелажно място?
     */
    public static function isPlaceUsable($position, $productId = null, $storeId = null, &$error = null)
    {
        expect($position);
        $storeId = $storeId ?? store_Stores::getCurrent();
        
        if (!self::checkPosition($position, $productId, $storeId, $error, $rec)) {
            
            return;
        }
        list(, $row, $col) = explode('-', $position);
        
        $dRec = rack_RackDetails::fetch("#rackId = {$rec->id} && #row = '{$row}' AND #col = {$col}");
        if ($dRec) {
            if ($dRec->status == 'unusable') {
                $error = 'Мястото е неизползваемо';
                
                return false;
            }
            
            if ($dRec->status == 'reserved' && isset($productId) && $dRec->productId != $productId) {
                $reservedProductName = cat_Products::getTitleById($dRec->productId);
                $error = "Мястото е запазено за артикул|*: <b>{$reservedProductName}</b>";
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $rec->storeId = store_Stores::getCurrent();
            
            if (!rack_Pallets::isEmptyOut($rec->num, $rec->rows, $rec->columns, null, $error)) {
                $form->setError('rows,columns', $error);
            }
            
            if ($rec->id && rack_RackDetails::fetch("#rackId = {$rec->id} AND (#row > '{$rec->rows}' OR #col > {$rec->columns}) AND #status != 'usable'")) {
                $form->setWarning('rows,columns', 'Информацията за запазени или неизползваеми места извън новите размери ще бъде изтрита');
            }
        }
    }
    
    
    /**
     * Преобразува запис от модела в обект с вербални стойности
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $row = parent::recToVerbal_($rec, $fields);
        
        $row->num = ' №' . $row->num;
        
        $fields = arr::make($fields, true);
        
        if (isset($fields['-single'])) {
            $storeId = store_Stores::getCurrent();
            $row->num .= ' / ' . store_Stores::getTitleById($storeId);
        }
        
        return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        if ($fields['-single']) {
            $row->places = self::renderRack($rec);
            $row->comment .= "<div style='font-size:0.8em;color:999;'>" .
                tr('Клик върху клетка, за да я редактирате или задръжте мишката за информация') .
                '</div>';
        }
        
        if ($rec->total) {
            $row->free = $rec->total - $rec->reserved - $rec->used;
            $row->free .= ' (' . round(100 * $row->free / $rec->total, 2) . '%)';
            $row->free = "<span style='color:green;'>" . $row->free . '</div>';
        }
    }
    
    
    /**
     * Рендиране на стелажа
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function renderRack($rec)
    {
        $row = $rec->rows;
        $hlPos = Request::get('pos');
        $hlFullPos = "{$rec->num}-{$hlPos}";
        
        list($unusable, $reserved) = rack_RackDetails::getunUsableAndReserved();
        $used = rack_Pallets::getUsed();
        list($movedFrom, $movedTo) = rack_Movements::getExpected();
        
        $hlProdId = $used[$hlFullPos];
        
        while ($row >= 'A') {
            $res .= '<tr>';
            
            for ($i = 1; $i <= $rec->columns; $i++) {
                $attr = array();
                
                $attr['style'] = 'color:#ccc;';
                
                $pos = "{$row}-{$i}";
                $posFull = "{$rec->num}-{$row}-{$i}";
                
                $hint = '';
                
                $title = null;
                
                $url = toUrl(array('rack_RackDetails', 'add', 'rackId' => $rec->id, 'row' => $row, 'col' => $i));
                $attr['ondblclick'] = "document.location='{$url}';";
                $pId = null;
                
                // Ако е заето с нещо
                if (!isset($title) && ($pId = $used[$posFull])) {
                    $prodTitle = cat_Products::getTitleById($pId);
                    $attrA = array();
                    $attrA['title'] = $prodTitle;
                    $color = self::getColor($prodTitle, 0, 110);
                    $bgColor = self::getColor($prodTitle, 130, 240);
                    
                    $attrA['style'] = "color:#{$color};background-color:#{$bgColor};";
                    
                    $title = ht::createLink($pos, array('rack_Pallets', 'list', 'pos' => "{$rec->num}-{$pos}"), null, $attrA);
                }
                
                // Ако е неизползваемо
                if (!isset($title) && $unusable[$posFull]) {
                    $title = '&nbsp;';
                }
                
                // Ако е резервирано за нещо
                if (!isset($title) && ($pId = $reserved[$posFull])) {
                    $title = $pos;
                    $attr['style'] = 'color:#fbb;';
                    $hint = tr('Запазено място');
                    
                    if ($pId > 0) {
                        $prodTitle = cat_Products::getTitleById($pId);
                        $hint = tr('Запазено място за') . ' ' . $prodTitle;
                    }
                }
                
                // Ако се очаква палет
                if (!isset($title) && $movedTo[$posFull]) {
                    $title = $pos;
                    $attr['style'] = 'color:#6c6;';
                    $hint = tr('Очаква се палет');
                }
                
                // Ако ще се премества палет
                if ($movedFrom[$posFull]) {
                    $attr['style'] .= ';text-decoration:underline;';
                    $hint = tr('Предстои преместване');
                }
                
                if (!isset($title)) {
                    $title = $pos;
                    $attr['style'] = 'color:#ccc;';
                }
                
                if ($pos == $hlPos) {
                    $attr['class'] .= ' rack-hl';
                } elseif (isset($pId) && $hlProdId == $pId) {
                    $attr['class'] .= ' rack-same';
                }
                
                if ($c = $rec->constrColumnsStep) {
                    if ($i % $c == 1) {
                        $attr['style'] .= 'border-left:solid 2px #999;';
                    }
                    if ($i % $c == 0) {
                        $attr['style'] .= 'border-right:solid 2px #999;';
                    }
                }
                
                $attr['nowrap'] = 'nowrap';
                $attr['style'] .= 'font-size:0.8em;';
                
                if ($hint) {
                    $attr['title'] = "{$hint}";
                }
                
                $res .= ht::createElement('td', $attr, $title);
            }
            
            $res .= '</td>';
            
            $row = chr(ord($row) - 1);
        }
        
        $res = "<table class='listTable'>{$res}</table>";
        
        return $res;
    }
    
    
    public static function getColor($title, $min = 0, $max = 255)
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
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->id)) {
            if (!$rec->num) {
                $rec->num = $mvc->fetch($rec->id)->num;
            }
            if (!rack_Pallets::isEmptyOut($rec->num)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Връща масив със всички възможно позиции за палети в дадения склад
     */
    public static function getPalletsOnRack($storeId, $num)
    {
        $res = array();
        $pQuery = rack_Pallets::getQuery();
        $pQuery->where("#storeId = {$storeId} AND #position LIKE '{$num}-%'");
        while ($pRec = $pQuery->fetch()) {
            list($num, $row, $col) = explode('-', $pRec->position);
            $res[$row][$col] = 'U';
        }
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#storeId = {$storeId} AND #state != 'closed' AND (#positionTo LIKE '{$num}-%' OR #position LIKE '{$num}-%')");
        while ($mRec = $mQuery->fetch()) {
            if ($mRec->positionTo) {
                list($num1, $row, $col) = explode('-', $mRec->positionTo);
                if ($num1 == $num) {
                    $res[$row][$col] = 'M';
                }
            }
            if ($mRec->position) {
                list($num1, $row, $col) = explode('-', $mRec->position);
                if ($num1 == $num) {
                    $res[$row][$col] = 'F';
                }
            }
        }
        
        $rec = self::fetch("#storeId = {$storeId} AND #num = {$num}");
        
        if ($rec) {
            $dQuery = rack_RackDetails::getQuery();
            $dQuery->where("#rackId = {$rec->id}");
            while ($dRec = $dQuery->fetch()) {
                if (!$res[$dRec->row][$dRec->col]) {
                    if ($dRec->status == 'unusable') {
                        $res[$dRec->row][$dRec->col] = 'N';
                    } elseif ($dRec->status == 'reserved') {
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
        foreach ($mvc->updateRacks as $position => $true) {
            list($storeId, $num, $row, $col) = explode('-', $position);
            if ($storeId > 0 && $num > 0) {
                // Записваме в информацията за палета
                $rec = $mvc->fetch("#storeId = {$storeId} AND #num = {$num}");
                if ($rec) {
                    self::updateRack($rec);
                }
            }
            unset($mvc->updateRacks[$position]);
        }
    }
    
    
    /**
     * Обновява информацията за посочения стелаж
     */
    public static function updateRack($rec)
    {
        // Изчисляваме заетите палети
        $pQuery = rack_Pallets::getQuery();
        $pQuery->where("#storeId = {$rec->storeId} AND #position LIKE '{$rec->num}-%' AND #state = 'active'");
        $pQuery->XPR('count', 'int', 'count(#id)');
        
        $rec->used = $pQuery->fetch()->count;
        $rR = cls::get('rack_Racks');
        $rR->save_($rec, 'used');
    }
    
    
    /**
     * Изпълнява се по крон и обновява информацията за стелажите
     */
    public static function cron_Update()
    {
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            self::updateRack($rec);
        }
        
        $Products = cls::get('rack_Products');
        $pQuery = $Products->getQuery();
        $pQuery->where('#productId IS NOT NULL AND #storeId IS NOT NULL');
        $pQuery->groupBy('productId,storeId');
        $pQuery->show('productId,storeId');
        
        // Опресняване на палетираното
        $toUpdate = array();
        while ($rec = $pQuery->fetch()) {
            $palletQuery = rack_Pallets::getQuery();
            $palletQuery->where("#productId = {$rec->productId} AND #storeId = {$rec->storeId} AND #state != 'closed'");
            $palletQuery->XPR('sum', 'double', 'SUM(#quantity)');
            $palletQuery->show('sum');
            $sum = $palletQuery->fetch()->sum;
            
            $rec->quantityOnPallets = ($sum) ? $sum : null;
            
            $toUpdate[$rec->id] = $rec;
        }
        
        $Products->saveArray($toUpdate, 'id,quantityOnPallets');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        $mvc->clearDetails($id);
        $mvc::on_AfterUpdateMaster($mvc, $res, $id);
    }
    
    
    /**
     * Обновява статистиката за стелажа
     */
    protected static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        $rec = $mvc->fetch($id);
        
        if ($rec) {
            $rec->total = $rec->columns * (ord($rec->rows) - ord('A') + 1);
            
            $dQuery = rack_RackDetails::getQuery();
            $dQuery->where("#rackId = {$rec->id} && #status = 'unusable'");
            $rec->total -= $dQuery->count();
            
            $dQuery = rack_RackDetails::getQuery();
            $dQuery->where("#rackId = {$rec->id} && #status = 'reserved'");
            $rec->reserved = $dQuery->count();
            if (!$rec->used) {
                $rec->used = 0;
            }
            
            $mvc->save_($rec, 'total,reserved,used');
            
            core_Cache::remove('getUnusableAndReserved', $rec->storeId);
        }
    }
    
    
    /**
     * След изтриване на записи
     *
     * @param core_Mvc   $mvc
     * @param int        $numRows
     * @param core_Query $query
     * @param string|array
     *
     * @return bool Дали да продължи обработката на опашката от събития
     */
    protected static function on_AfterDelete($mvc, $numRows, $query, $cond)
    {
        $dR = $query->getDeletedRecs();
        
        if (is_array($dR)) {
            foreach ($dR as $rec) {
                core_Cache::remove('getUsableAndReserved', $rec->storeId);
                rack_RackDetails::delete("#rackId = {$rec->id}");
            }
        }
    }
    
    
    /**
     * Изтрива излишните детайли, след преоразмеряване
     */
    public function clearDetails($id)
    {
        $rec = self::fetch($id);
        
        if ($rec) {
            $dQuery = rack_rackDetails::getQuery();
            while ($dRec = $dQuery->fetch("#rackId = {$rec->id}")) {
                if (($dRec->row > $rec->rows) || ($dRec->col > $rec->columns) || ($dRec->status == 'usable')) {
                    rack_rackDetails::delete($dRec->id);
                }
            }
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (!count($data->rows) || empty($data->listSummary->query)) {
            
            return;
        }
        
        $data->listSummary->query->XPR('totalTotal', 'int', 'SUM(#total)');
        $data->listSummary->query->XPR('usedTotal', 'int', 'SUM(#used)');
        $data->listSummary->query->XPR('reservedTotal', 'int', 'SUM(#reserved)');
        
        $summaryRec = $data->listSummary->query->fetch();
        $Int = core_Type::getByName('int');
        $rowBefore = (object) array('totalTotal' => $Int->toVerbal($summaryRec->totalTotal), 'usedTotal' => $Int->toVerbal($summaryRec->usedTotal), 'reservedTotal' => $Int->toVerbal($summaryRec->reservedTotal), 'freeTotal' => $Int->toVerbal($summaryRec->totalTotal - $summaryRec->usedTotal - $summaryRec->reservedTotal));
        
        $rowBeforeTpl = new core_ET("<tr style='background-color:#aaa;color:white;text-align:center;'><td colspan='2'></td><td><b>[#freeTotal#]</b></td><td><b>[#usedTotal#]</b></td><td><b>[#reservedTotal#]</b></td><td><b>[#totalTotal#]</b></td><td colspan='2'></td></tr>");
        $rowBeforeTpl->placeObject($rowBefore);
        $tpl->replace($rowBeforeTpl, 'ROW_BEFORE');
    }
}
