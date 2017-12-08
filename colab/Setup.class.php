<?php


/**
 * Кои документи могат да бъдат създавани от контрактор
 */
defIfNot('COLAB_CREATABLE_DOCUMENTS_LIST', '');


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
        'colab_DocumentLog',
    );
    
    
    /**
     * Кои документи могат да бъдат създавани по дефолт от контрактори
     */
    private static $defaultCreatableDocuments = 'sales_Sales,doc_Comments,doc_Notes,marketing_Inquiries2';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'COLAB_CREATABLE_DOCUMENTS_LIST' => array('keylist(mvc=core_Classes,select=name)', "caption=Кои документи могат да се създават от партньори->Документи,optionsFunc=colab_Setup::getDocumentOptions"),
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
    	$html .= $Plugins->installPlugin('Споделяне системи с партньори', 'colab_plg_FolderToPartners', 'support_Systems', 'private');
    	
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
//     	$html .= $Plugins->installPlugin('Colab за сигнали', 'colab_plg_Document', 'support_Issues', 'private');
    	$html .= $Plugins->installPlugin('Colab за резолюция на сигнал', 'colab_plg_Document', 'support_Resolutions', 'private');
     	$html .= $Plugins->installPlugin('Colab за коментар', 'colab_plg_Document', 'doc_Comments', 'private');
    	$html .= $Plugins->installPlugin('Colab за бележка', 'colab_plg_Document', 'doc_Notes', 'private');
        
    	$html .= $Plugins->installPlugin('Плъгин за споделяне с партьори на коментар', 'colab_plg_VisibleForPartners', 'doc_Comments', 'private');
    	$html .= $Plugins->installPlugin('Плъгин за споделяне с партьори на бележка', 'colab_plg_VisibleForPartners', 'doc_Notes', 'private');
    	
    	$defaultCreatableDocuments = arr::make(self::$defaultCreatableDocuments);
    	
    	foreach ($defaultCreatableDocuments as $docName){
    		$Doc = cls::get($docName);
    		$title = mb_strtolower($Doc->title);
    		$html .= $Plugins->installPlugin("Colab плъгин за {$title}", 'colab_plg_CreateDocument', $docName, 'private');
    	}
    	
    	$html .= core_Roles::addOnce('distributor', NULL, 'external');
    	$html .= core_Roles::addOnce('agent', NULL, 'external');
    	
        return $html;
    }
    
    
    /**
     * Помощна функция връщаща всички класове, които са документи
     */
    public static function getDocumentOptions()
    {
    	$options = core_Classes::getOptionsByInterface('colab_CreateDocumentIntf', 'title');
    	 
    	return $options;
    }
    
    
    /**
     * Зареждане на начални данни
     */
    public function loadSetupData($itr = '')
    {
    	$config = core_Packs::getConfig('colab');
    	$res = '';
    	
    	if(strlen($config->COLAB_CREATABLE_DOCUMENTS_LIST) === 0){
    		$arr = array();
    		$defaultCreatableDocuments = arr::make(self::$defaultCreatableDocuments);
    		foreach ($defaultCreatableDocuments as $docName){
    			$Doc = cls::get($docName);
    			if(cls::haveInterface('colab_CreateDocumentIntf', $Doc)){
    				$classId = $Doc->getClassId();
    				$arr[$classId] = $classId;
    			}
    		}
    	
    		// Записват се ид-та на документите, които могат да се създават от контрактори
    		if(count($arr)){
    			core_Packs::setConfig('colab', array('COLAB_CREATABLE_DOCUMENTS_LIST' => keylist::fromArray($arr)));
    			$res .= "<li style='color:green'>Задаване на дефолт документи, които могат да се създават от партньори";
    		}
    	}
    	
    	return $res;
    }
}

