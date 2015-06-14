<?php


/**
 * Клас 'colab_Setup'
 *
 * Исталиране/деинсталиране на colab
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ielin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class colab_Setup extends core_ProtoSetup
{
	

	/**
	 * Версия на пакета
	 */
	public $version = '0.1';
	
	
	/**
	 * Описание на модула
	 */
	public $info = "Пакет за работа с партньори";
	
	
	// Инсталиране на мениджърите
    var $managers = array(
        'colab_FolderToPartners',
    	'migrate::migrateVisibleforPartners',
        'colab_DocumentLog'
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    
    	// Зареждаме мениджъра на плъгините
    	$Plugins = cls::get('core_Plugins');
    
    	// Закачане на плъгин за споделяне на папки с партньори към фирмите
    	$html .= $Plugins->installPlugin('Споделяне на папки на фирми с партньори', 'colab_plg_FolderToPartners', 'crm_Companies', 'private');
    
    	// Закачане на плъгин за споделяне на папки с партньори към лицата
    	$html .= $Plugins->installPlugin('Споделяне на папки на лица с партньори', 'colab_plg_FolderToPartners', 'crm_Persons', 'private');
        
    	$html .= $Plugins->installPlugin('Colab sales', 'colab_plg_Document', 'sales_Sales', 'private');
    	
        return $html;
    }
    
    
    /**
     * Миграция за обновяване на полето в контейнера, определящо дали документа е видим за партньори
     */
    function migrateVisibleforPartners()
    {
    	$Containers = cls::get('doc_Containers');
    	
    	core_App::setTimeLimit(600);
    	
    	$containersQuery = $Containers->getQuery();
    	$containersQuery->where("#visibleForPartners IS NULL");
    	$containersQuery->show('visibleForPartners,docClass');
    	
    	while($cRec = $containersQuery->fetch()){
    		if(cls::load($cRec->docClass, TRUE)){
    			$Class = cls::get($cRec->docClass);
    			$cRec->visibleForPartners = ($Class->visibleForPartners) ? 'yes' : 'no';
    			$Containers->save($cRec, 'visibleForPartners');
    		}
    	}
    }
}

