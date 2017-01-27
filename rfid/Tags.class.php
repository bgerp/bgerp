<?php



/**
 * class Tags
 *
 * Менажира номерата, които биха били прочетени от rfid четците.
 * Прави връзката между различните начини на прочитане от различните четци.
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Tags extends Core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Карти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin,rfid';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin,rfid';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,rfid';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,rfid';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,rfid';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_RowTools2,rfid_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        $this->FLD('rfid55d', 'varchar(16)', 'caption=Rfid номер->WEG32 55d, oldFieldName=rfid_55d');
        $this->FLD('rfid10d', 'varchar(16)', 'caption=Rfid номер->1:1 10d, oldFieldName=rfid_10d');
        
        $this->setDbUnique('rfid55d');
        $this->setDbUnique('rfid10d');
    }
    
    
    /**
     * Попълва непопълнения от 2-та номера преди да се запише в базата
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!empty($rec->rfid55d)) {
            $rec->rfid10d = $mvc->convert55dTo10d($rec->rfid55d);
            $rec->rfid55d = (int) $rec->rfid55d;
        } elseif (!empty($rec->rfid10d)) {
            $rec->rfid55d = $mvc->convert10dTo55d($rec->rfid10d);
            $rec->rfid10d = (int) $rec->rfid10d;
        }
    }
    
    
    /**
     * Конвертира тип показване 55d към 10d
     * @param string $num
     */
    function convert55dTo10d($num)
    {
        $numLast5d = sprintf("%04s", dechex(substr($num, -5)));
        $numFirst5d = dechex(substr($num, 0, strlen($num)-5));
        
        return hexdec($numFirst5d . $numLast5d);
    }
    
    
    /**
     * Конвертира тип показване 55d към 10d
     * @param int $num
     */
    function convert10dTo55d($num)
    {
        $numHex = dechex($num);
        $numLast5d = sprintf("%05d", hexdec(substr($numHex, -4)));
        $numFirst5d = hexdec(substr($numHex, 0, strlen($numHex)-4));
        
        return ($numFirst5d . $numLast5d);
    }
}