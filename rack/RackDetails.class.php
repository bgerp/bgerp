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

        expect($rec->rackId);
        $rRec = rack_Racks::fetch($rec->rackId);
        expect($rRec);
        store_Stores::selectCurrent(store_Stores::fetch($rRec->storeId));

        $r = 'A';
        do {
            $o[$r] = $r;
            $r = chr(ord($r)+1);
        } while($r <= $rRec->rows);
        $form->setOptions('row', $o);

        $c = 1;
        do {
            $o2[$c] = $c;
            $c++;
        } while($c <= $rRec->columns);
        $form->setOptions('col', $o2);
        
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