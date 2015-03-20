<?php



/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'planning_Resources';
    
    
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
    		'planning_Jobs',
            'planning_Tasks',
    		'planning_Resources',
    		'planning_Stages',
    		'planning_ObjectResources',
    		'planning_ConsumptionNotes',
    		'planning_ConsumptionNoteDetails',
    		'planning_ProductionNotes',
    		'planning_ProductionNoteDetails',
    		'migrate::moveJobs'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'planning';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.21, 'Производство', 'Планиране', 'planning_Resources', 'default', "planning, ceo"),
        );   
   
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'planning/tpl/styles.css';
    
    
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
    	planning_Resources::delete("#title = 'Общ'");
    }
    
    
    /**
     * Миграция за обновяване на състоянието на ресурсите
     */
    public function updateResourceState()
    {
    	$query = planning_Resources::getQuery();
    	$query->where("#state != 'rejected'");
    	while($rec = $query->fetch()){
    		$rec->state = 'active';
    		cls::get('planning_Resources')->save_($rec);
    	}
    }
    
    
    /**
     * Преместване на старите задания
     */
    public function moveJobs()
    {
    	if(planning_Jobs::count()){
    		core_Classes::add('cat_GeneralProductDriver');
    		$Driver = cls::get('cat_GeneralProductDriver');
    		$folderId = doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => $Driver->getJobFolderName()));
    		
    		$query = planning_Jobs::getQuery();
    		$query->where("#folderId != {$folderId}");
    		while($rec = $query->fetch()){
    			try{
    				doc_Threads::move($rec->threadId, $folderId);
    			} catch(core_exception_Expect $e){
    				planning_Jobs::log("Проблем при местене на задание {$rec->id}: {$e->getMessage()}");
    			}
    		}
    	}
    }
}
