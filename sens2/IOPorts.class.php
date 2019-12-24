<?php


/**
 * Детайл за входни/изходни портове в sens2
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://www.unipi.technology/
 */
class sens2_IOPorts extends embed_Detail
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Портове';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Порт';
    
    
    /**
     * Интерфейса на вътрешните обекти
     */
    public $driverInterface = 'sens2_ioport_Intf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'slot,name,driverClass=Тип,portIdent,state';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State2,plg_RowTools2,sens2_Wrapper';
    
    
    /**
     * Поле - ключ към мастера
     */
    public $masterKey = 'controllerId';
    
    
    /**
     * Добавя задължителни полета към модела
     *
     * @param bgerp_ProtoParam $mvc
     *
     * @return void
     */
    public function description()
    {
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name)', 'caption=Контролер');
        
        $this->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory,smartCenter');
        $this->FLD('slot', 'varchar(16)', 'caption=Слот,smartCenter');
        $this->FLD('portIdent', 'varchar(64)', 'caption=Идентификатор,input=none,smartCenter');
        
        $this->setDbIndex('controllerId');
        
        $this->setDbUnique('name, controllerId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->driverClass) {
            $driver = sens2_Controllers::getDriver($rec->controllerId);
            $portClass = cls::get($rec->driverClass);
            
            // Трябва да отделим слотовете за този вид порт
            $opt = $driver->getSlotOpt($portClass::SLOT_TYPES, true);
            
            // Добавяме индикация след името на слота, колко пъти е използван до сега
            $usedSlots = self::getUsedSlots($rec->controllerId);
            if ($rec->slot) {
                $opt[$rec->slot] = $rec->slot;
                $usedSlots[$rec->slot]--;
            }
            
            foreach ($usedSlots as $slot => $cnt) {
                if ($opt[$slot] && $cnt > 0) {
                    $opt[$slot] = $slot . ' (' . $cnt . ')';
                }
            }
            
            $form->setOptions('slot', $opt);
        } else {
            $form->setField('slot', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            $driver = $mvc->getDriver($rec);
            $rec->portIdent = $driver->getPortIdent($rec);
        }
    }
    
    
    /**
     * Връща броя на използваните слотове
     *
     * @return array (slot => cnt)
     */
    public static function getUsedSlots($controllerId)
    {
        $pQuery = sens2_IOPorts::getQuery();
        $res = array();
        while ($pRec = $pQuery->fetch("#controllerId = {$controllerId}")) {
            $res[$pRec->slot]++;
        }
        
        return $res;
    }
}
