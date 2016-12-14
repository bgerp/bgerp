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
        $this->FLD('rackId', 'key(mvc=rack_Racks)', 'caption=Стелаж, input=hidden');
        $this->FLD('row', 'varchar(1)', 'caption=Ред,smartCenter');
        $this->FLD('col', 'int', 'caption=Колона,smartCenter');
        $this->FLD('status', 'enum(usable=Използваемо,
                                   unusable=Неизползваемо,
                                   reserved=Резервирано                                     
                                   )', 'caption=Състояние,smartCenter');
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

        expect($rec->rackId);
        $rRec = rack_Racks::fetch($rec->rackId);

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

    }
}