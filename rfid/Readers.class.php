<?php



/**
 * class Readers
 *
 * Съхранява данни за четците - действие, което описват
 * /вход, изход, маркиране на вал, преминаване на палет и др./.
 * Четеца има драйвер, от който се разбира с какво действие е обвързан,
 * както и попълва данните в Events.
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Readers extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Четци';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin,rfid';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,rfid';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,rfid';
    
    
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
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,rfid_Wrapper,plg_RowTools2';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar', 'caption=Име,mandatory');
        $this->FLD('settins', 'varchar', 'caption=Състояние,mandatory');
        $this->FLD('driver', 'class(interface=rfid_ReaderIntf)', 'caption=Драйвер,mandatory');
        
        /*
        Формата на серийният номер на картата се изчислява по следните начини и зависи от четеца
        Трябва да се реализира в драйвера
        
        Internal Number: Every single EM format transponder (card) has one unique Internal ID number which is a 10 digits of hexadecimal number (10H).  The Internal Number is divided into three parts:            

        Version Code [V]: H9       Customer Code [C]: H8        ID Code [ID]: H7 ~ H0 

        External Number: The number printed on the surface of transponder (card) is External Number. External Number is converted from Internal Number. The following are the most popular External Number formats. 

        10H>13D: Convert [V]+[C]+[ID] to 13 digits of decimal number.
        08H>10D: Convert [ID] to 10 digits of decimal number.
        08H>55D: First divide [ID] into two parts (4H+4H), then convert each part to 5 digits of decimal number. 
               *Or named WEG32.
        06H>08D: Convert lowest 6 digits of [ID] to 8 digits of decimal number.
        2.4H>3.5D(A): Convert [V]+[C] to 3 digits decimal number. 
                      Convert lowest 4 digits of [ID] to 5 digits of decimal number.
        2.4H>3.5D(B): Convert highest 2 digits of [ID] to 3 digits decimal number. 
                      Convert lowest 4 digits of [ID] to 5 digits of decimal number.
        2.4H>3.5D(C): Convert H5 and H4 of [ID] to 3 digits decimal number. 
                      Convert lowest 4 digits of [ID] to 5 digits of decimal number. 
                      *Or named WEG24.

        Example: Supposed the Internal Number is 01013EB28D (10H)
              then  10H>13D: 0004315853453
                    08H>10D: 0020886157
                    08H>55D: 00318,45709
                    06H>08D: 04108941
                    2.4H>3.5D(A): 001,45709
                    2.4H>3.5D(B): 001,45709
                    2.4H>3.5D(C): 062,45709
    */
    
    }
}
