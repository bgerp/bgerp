<?php 


/**
 * Мениджира детайлите на стелажите (rack_RackDetails)
 *
 *
 * @category  bgerp
 * @package   rack
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_RackDetails extends core_Detail
{	
	
    /**
     * Заглавие
     */
    var $title = "Детайли на стелаж";
    

    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Състояние на клетка";

    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, rack_Wrapper';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'rackId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, rackMaster';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, rackMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, rackMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, rackMaster';
    

    var $listFields = 'row,col,status';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('rackId', 'key(mvc=rack_Racks)', 'caption=Стелаж, input=hidden,silent');
        $this->FLD('row', 'varchar(1)', 'caption=Ред,smartCenter,silent');
        $this->FLD('col', 'int', 'caption=Колона,smartCenter,silent');
        $this->FLD('status', 'enum(usable=Използваемо,
                                   unusable=Неизползваемо,
                                   reserved=Запазено                                     
                                   )', 'caption=Състояние,smartCenter,silent,refreshForm');
        $this->FLD('productId', 'key(mvc=store_Products, select=productId,allowEmpty)', 'caption=Продукт,input=none');
        $this->setDbUnique('rackId,row,col');
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
        
        $form->FLD('nextRow', 'varchar(1,select2MinItems=1000)', 'caption=Повторение на състоянието до:->Ред,smartCenter,autohide,class=w25');
        $form->FLD('nextCol', 'int(select2MinItems=1000)', 'caption=Повторение на състоянието до:->Колона,smartCenter,autohide,class=w25');

        expect($rec->rackId);
        $rRec = rack_Racks::fetch($rec->rackId);
        expect($rRec);
        store_Stores::selectCurrent(store_Stores::fetch($rRec->storeId));

        $r = 'A';
        $o = array('' => '');
        do {
            $o[$r] = $r;
            $r = chr(ord($r)+1);
        } while($r <= $rRec->rows);
        $form->setOptions('row', $o);
        $form->setOptions('nextRow', $o);

        $c = 1;
        $o2 = array('' => '');
        do {
            $o2[$c] = $c;
            $c++;
        } while($c <= $rRec->columns);
        $form->setOptions('col', $o2);
        $form->setOptions('nextCol', $o2);

        expect($rec->rackId && $rec->row && $rec->col, $rec);
        $form->setReadOnly('row');
        $form->setReadOnly('col');

        if(!$rec->id) {
            if($exRec = self::fetch(array("#rackId = [#1#] AND #row = '[#2#]' AND #col = [#3#]", $rec->rackId, $rec->row, $rec->col))) {
                $rec = $exRec;
            }
        }
        
        if($rec->status == 'reserved') {
            $form->setField('productId', 'input=input');
        }

        // Ако има палет на това място, или към него е насочено движение
        // То статусът може да е само 'Използваемо'
        if(!rack_Pallets::isEmpty("{$data->masterRec->num}-{$rec->row}-{$rec->col}")) {
            setIfNot($rec->status, 'usable');
            $form->setReadOnly('status');
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

            if($rec->nextRow || $rec->nextCol) {
                if(empty($rec->nextRow)) {
                    $rec->nextRow = $rec->row;
                }
                if(empty($rec->nextCol)) {
                    $rec->nextCol = $rec->col;
                }

                // Вземаме параметрите на стелажа
                $rRec = rack_Racks::fetch($rec->rackId);
     
                $maxX = $rRec->columns;
                $maxY = ord($rRec->rows);

                $x1 = $rec->col;
                $y1 = ord($rec->row);
                
                $x2 = $rec->nextCol;
                $y2 = ord($rec->nextRow);
                
                $_toSave = array();
                
                list($unusable, $reserved) = rack_RackDetails::getunUsableAndReserved();
                $used = rack_Pallets::getUsed();
                list($movedFrom, $movedTo) = rack_Movements::getExpected();

                for($x = 1; $x <= $maxX; $x++) {
                    for($y = ord('A'); $y <= $maxY; $y++) {
                        $pos = $rRec->num . '-' . chr($y) . '-' . $x;
                        

                        // Ако текущата позиция е в очертанията, добавяме я в масива
                        if(($x1-$x)*($x2-$x) <= 0 && ($y1-$y)*($y2-$y) <= 0) {
                            
                            if($movedFrom[$pos] || $movedTo[$pos]) {
                                $form->setError('nextCol,nextRow', "Има текущи движения, които засягат посочената област" . "|* [{$pos}]");
                            }

                            if($rec->status == 'unsusable') {
                                if($used[$pos]) {
                                    $form->setError('nextCol,nextRow', "В посочената област има заети позиции" . "|* [{$pos}]");
                                }
                                if($reserved[$pos]) {
                                    $form->setError('nextCol,nextRow', "В посочената област има резервирани позиции" . "|* [{$pos}]");
                                }

                            }
                        
                            if($used[$pos] ) {
                                if($rec->status == 'reserved' && $used[$pos] != $rec->productId) {
                                    $form->setError('nextCol,nextRow', "В посочената област има други продукти" . "|* [{$pos}]");
                                }
                                if($rec->status != 'reserved') {
                                    $form->setError('nextCol,nextRow', "В посочената област има заети позиции" . "|* [{$pos}]");
                                }
                            }
                         
                            if($unusable[$pos] && $rec->status != 'usable') continue;

                            $add = clone $rec;
                            unset($add->id, $add->_toSave);
                            $add->col = $x;
                            $add->row = chr($y);
                            $rec->_toSave[] = $add;
                        }
                    }
                }                
            }
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
        if(is_array($rec->_toSave) && count($rec->_toSave)) {
            foreach($rec->_toSave as $r) {
                
                $r->id = self::fetch("#col = $r->col AND #row = '{$r->row}' AND #rackId = {$r->rackId}")->id; 

                self::save($r);
            }
        }
    }


    /**
     * Връща масив с масиви, отговарящи на запазените и неизползваемите места в склада
     */
    public static function getUnusableAndReserved($storeId = NULL)
    {

        if(!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        if(TRUE || !($res = core_Cache::get('getUnusableAndReserved', $storeId))) {
            $res = array();
            $res[0] = array();
            $res[1] = array();
            $rQuery = rack_Racks::getQuery();
            while($rRec = $rQuery->fetch("#storeId = {$storeId}")) {
                $query = self::getQuery(); 
                while($rec = $query->fetch("#rackId = {$rRec->id}")) {
                    $pos = "{$rRec->num}-{$rec->row}-{$rec->col}";
                    if($rec->status == 'reserved') {
                        $res[1][$pos] = $rec->productId ? $rec->productId : -1;
                    } elseif($rec->status == 'unusable') {
                        $res[0][$pos] = TRUE;
                    }
                }
            }

            core_Cache::set('getUnusableAndReserved', $storeId, $res, 1440);
        }
        
        return $res;
    }
}