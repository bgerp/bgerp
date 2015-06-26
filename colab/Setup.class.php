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
    	
    	// 
    	$html .= $Plugins->installPlugin('Настройка на профилите на партньори', 'colab_plg_Settings', 'core_Settings', 'private');
        
    	// Закачаме плъгина към документи, които са видими за партньори
    	$html .= $Plugins->installPlugin('Colab за приходни банкови документи', 'colab_plg_Document', 'bank_IncomeDocuments', 'private');
    	$html .= $Plugins->installPlugin('Colab за разходни банкови документи', 'colab_plg_Document', 'bank_SpendingDocuments', 'private');
    	$html .= $Plugins->installPlugin('Colab за приходни касови ордери', 'colab_plg_Document', 'cash_Pko', 'private');
    	$html .= $Plugins->installPlugin('Colab за разходни касови ордери', 'colab_plg_Document', 'cash_Rko', 'private');
    	$html .= $Plugins->installPlugin('Colab за артикули в каталога', 'colab_plg_Document', 'cat_Products', 'private');
    	$html .= $Plugins->installPlugin('Colab за декларации за съответствие', 'colab_plg_Document', 'dec_Declarations', 'private');
    	$html .= $Plugins->installPlugin('Colab за входящи имейли', 'colab_plg_Document', 'email_Incomings', 'private');
    	$html .= $Plugins->installPlugin('Colab за изходящи имейли', 'colab_plg_Document', 'email_Outgoings', 'private');
    	$html .= $Plugins->installPlugin('Colab за запитвания', 'colab_plg_Document', 'marketing_Inquiries2', 'private');
    	$html .= $Plugins->installPlugin('Colab за ценоразписи', 'colab_plg_Document', 'price_ListDocs', 'private');
    	$html .= $Plugins->installPlugin('Colab за фактури за продажби', 'colab_plg_Document', 'sales_Invoices', 'private');
    	$html .= $Plugins->installPlugin('Colab за проформа фактури', 'colab_plg_Document', 'sales_Proformas', 'private');
    	$html .= $Plugins->installPlugin('Colab за изходящи оферти', 'colab_plg_Document', 'sales_Quotations', 'private');
    	$html .= $Plugins->installPlugin('Colab за договори за продажба', 'colab_plg_Document', 'sales_Sales', 'private');
    	$html .= $Plugins->installPlugin('Colab за предавателни протоколи', 'colab_plg_Document', 'sales_Services', 'private');
    	$html .= $Plugins->installPlugin('Colab за протоколи за отговорно пазене', 'colab_plg_Document', 'store_ConsignmentProtocols', 'private');
    	$html .= $Plugins->installPlugin('Colab за складови разписки', 'colab_plg_Document', 'store_Receipts', 'private');
    	$html .= $Plugins->installPlugin('Colab за експедиционни нареждания', 'colab_plg_Document', 'store_ShipmentOrders', 'private');
    	$html .= $Plugins->installPlugin('Colab за сигнали', 'colab_plg_Document', 'support_Issues', 'private');
    	$html .= $Plugins->installPlugin('Colab за резолюция на сигнал', 'colab_plg_Document', 'support_Resolutions', 'private');
    	
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

