<?php



/**
 * Клас 'uiext_Setup' 
 *
 *
 * @category  bgerp
 * @package   uiext
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class uiext_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    //var $startCtr = 'transsrv_TransportModes';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Разширения на потребителския интерфейс";
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'uiext';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
    		array(1.9999, 'Система', 'Инструменти', 'uiext_Labels', 'default', "uiext, admin, ceo"),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'uiext_Labels',
            'uiext_DocumentLabels',
        );
}
