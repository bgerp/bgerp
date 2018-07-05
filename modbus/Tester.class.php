<?php



/**
 * Тестер за Modbus IP устройство
 *
 *
 * @category  vendors
 * @package   modbus
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class modbus_Tester extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Sorting,recently_Plugin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Тестер за Modbus IP устройство';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('ip', 'varchar(16)', 'caption=IP,recently');
        $this->FLD('port', 'int', 'caption=Port,value=502');
        $this->FLD('unit', 'int', 'caption=Unit,value=1');
        $this->FLD('startAddr', 'varchar', 'caption=Адрес');
        $this->FLD('quantity', 'int', 'caption=Количество');
        $this->FLD('type', 'enum(words,float,double)', 'caption=Тип');
        $this->FLD('mode', 'enum(normal,debug,simulation)', 'caption=Режим');
        $this->FLD('data', 'text', 'caption=Данни');
        $this->FLD('note', 'varchar(256)', 'caption=Забележка');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setSuggestions('startAddr', ',400001,300001,100001,000001');
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->data = new ET($row->data);
        $row->data->append(ht::createLink('Read', array($mvc, 'Read', $rec->id)));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Read()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        
        $driver = new modbus_Driver((array) $rec);
        
        $values = $driver->read($rec->startAddr, $rec->quantity);
        
        foreach ($values as $addr => $val) {
            $text .= "{$addr} : {$val}\n";
        }
        
        $rec->data = $text;
        
        $this->save($rec, 'data');
        
        return new Redirect(array($this), '|Данните са прочетени');
    }
}
