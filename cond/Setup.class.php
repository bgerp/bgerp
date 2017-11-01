<?php


/**
 * class cond_Setup
 *
 * Инсталиране/Деинсталиране на
 * админ. мениджъри с общо предназначение
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Setup  extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cond_DeliveryTerms';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'crm=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Търговски условия по сделките";
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
			'cond_Texts',
			'cond_Groups',
        	'cond_PaymentMethods',
        	'cond_DeliveryTerms',
        	'cond_Parameters',
        	'cond_ConditionsToCustomers',
    		'cond_Payments',
    		'cond_Countries',
            'cond_TaxAndFees',
        );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.9, 'Система', 'Дефиниции', 'cond_DeliveryTerms', 'default', "ceo, admin"),
        );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cond_type_Double,cond_type_Text,cond_type_Varchar,cond_type_Time,cond_type_Date,cond_type_Component,cond_type_Enum,cond_type_Set,cond_type_Percent,cond_type_Int,cond_type_Delivery,cond_type_PaymentMethod,cond_type_Image,cond_type_File,cond_type_Store,cond_type_PriceList,cond_type_PurchaseListings,cond_type_SaleListings,cond_type_Url,cond_type_YesOrNo";
    
    
	/**
     * Инсталиране на пакета
     * @TODO Да се премахне след като кода се разнесе до всички бранчове
     * и старата роля 'salecond' бъде изтрита
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Ако има роля 'salecond'  тя се изтрива (остаряла е)
    	if($roleRec = core_Roles::fetch("#role = 'salecond'")){
    		core_Roles::delete("#role = 'salecond'");
    	}

		$Plugins = cls::get('core_Plugins');

		// Замества handle' ите на документите с линк към документа
		$html .= $Plugins->installPlugin('Плъгин за пасажи в RichEdit', 'cond_RichTextPlg', 'type_Richtext', 'private');

		// Кофа за файлове от тип параметър
		$Bucket = cls::get('fileman_Buckets');
		$Bucket->createBucket('paramFiles', 'Прикачени файлови параметри', NULL, '1GB', 'user', 'user');
		
    	return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}