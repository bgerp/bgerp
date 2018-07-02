<?php


/**
 * Детайл за входни/изходни портове в sens2
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://www.unipi.technology/
 */
class sens2_IOPorts extends embed_Detail
{

    /**
     * Заглавие на драйвера
     */
    public $title = 'I/O ports';
    

    /**
     * Интерфейса на вътрешните обекти
     */
    public $driverInterface = 'sens2_ioport_Intf';
    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'slot,name,driverClass=Тип,state';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_State';

    
    /**
     * Поле - ключ към мастера
     */
    public $masterKey = 'controllerId';
    

    /**
     * Добавя задължителни полета към модела
     *
     * @param bgerp_ProtoParam $mvc
     * @return void
     */
    public function description()
    {
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name)', 'caption=Контролер');

        $this->FLD('name', 'varchar(64,ci)', 'caption=Име, mandatory');
        $this->FLD('slot', 'varchar(16)', 'caption=I/O Слот');
     
        $this->setDbUnique('name, controllerId');
    }
}
