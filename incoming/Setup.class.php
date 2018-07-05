<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с incoming модула
 *
 *
 * @category  bgerp
 * @package   incoming
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'incoming_Documents';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Входящи документи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'incoming_Documents',
            'incoming_Types',
            'migrate::addTypes',
        );
    

    /**
     * Роли за достъп до модула
     */
    //var $roles = '';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(1.24, 'Документи', 'Входящи', 'incoming_Documents', 'default', 'ceo'),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
                
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }


    /**
     * Миграция за включването на типовете документи
     */
    public static function addTypes()
    {
        $ID = cls::get('incoming_Documents');

        if ($ID->db->isfieldExists($ID->dbTableName, 'title')) {
            $query = incoming_Documents::getQuery();
            $query->FLD('title', 'varchar', 'caption=Заглавие');
            while ($rec = $query->fetch()) {
                if ($rec->title) {
                    $tRec = incoming_Types::fetch(array("LOWER(#name) = LOWER('[#1#]')", $rec->title));
                    if (!$tRec) {
                        $tRec = new stdClass();
                        $tRec->name = $rec->title;
                        incoming_Types::save($tRec);
                    }
                    $rec->typeId = $tRec->id;
                    $ID->save_($rec, 'typeId');
                }
            }
        }
    }
}
