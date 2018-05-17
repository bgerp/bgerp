<?php



/**
 * Клас 'uiext_Setup' 
 *
 *
 * @category  bgerp
 * @package   uiext
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class uiext_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'uiext_Labels';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = "Разширения на потребителския интерфейс";
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'uiext';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
    		array(1.9999, 'Система', 'Инструменти', 'uiext_Labels', 'default', "uiext, admin, ceo"),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'uiext_Labels',
            'uiext_DocumentLabels',
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    
    	$Plugins = cls::get('core_Plugins');
    	$html .= $Plugins->installPlugin('Добавяне на тагове към редовете на транспортните линии', 'uiext_plg_DetailLabels', 'trans_LineDetails', 'private');
    
    	return $html;
    }
}
