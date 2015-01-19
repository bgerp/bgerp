<?php



/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'mp_Resources';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Производствено планиране";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'mp_Jobs',
            'mp_Tasks',
    		'mp_Resources',
    		'mp_Stages',
    		'mp_ObjectResources',
    		'mp_ConsumptionNotes',
    		'mp_ConsumptionNoteDetails',
    		'mp_ProductionNotes',
    		'mp_ProductionNoteDetails',
    		'migrate::removeOldDefResource5',
    		'migrate::updateResourceState',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'mp';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.21, 'Производство', 'Планиране', 'mp_Resources', 'default', "mp, ceo"),
        );   
   
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'mp/tpl/styles.css';
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Премахване на стария ресурс
     */
    public function removeOldDefResource5()
    {
    	mp_Resources::delete("#title = 'Общ'");
    }
    
    
    /**
     * Миграция за обновяване на състоянието на ресурсите
     */
    public function updateResourceState()
    {
    	$query = mp_Resources::getQuery();
    	$query->where("#state != 'rejected'");
    	while($rec = $query->fetch()){
    		$rec->state = 'active';
    		cls::get('mp_Resources')->save_($rec);
    	}
    }
}
