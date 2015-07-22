<?php


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER1', '0');


/**
 * Краен номер на фактурите
 */
defIfNot('SALE_INV_MAX_NUMBER1', '2000000');


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER2', '2000000');


/**
 * Краен номер на фактурите
*/
defIfNot('SALE_INV_MAX_NUMBER2', '3000000');


/**
 * Максимален срок за бъдещи цени с които да работи офертата
 */
defIfNot('SALE_MAX_FUTURE_PRICE', type_Time::SECONDS_IN_MONTH);


/**
 * Максимален срок за минали цени с които да работи офертата
 */
defIfNot('SALE_MAX_PAST_PRICE', type_Time::SECONDS_IN_MONTH * 2);


/**
 * Колко време след като не е платена една продажба, да се отбелязва като просрочена
 */
defIfNot('SALE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Колко време да се изчака след активиране на продажба, да се приключва автоматично
 */
defIfNot('SALE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Колко продажби да се приключват автоматично брой
 */
defIfNot('SALE_CLOSE_OLDER_NUM', 15);


/**
 * Кой да е по подразбиране драйвера за фискален принтер
 */
defIfNot('SALE_FISC_PRINTER_DRIVER', '');


/**
 * Кой да е по подразбиране драйвера за фискален принтер
 */
defIfNot('SALE_INV_VAT_DISPLAY', 'no');


/**
 * Системата върана ли е с касови апарати или не
 */
defIfNot('SALE_INV_HAS_FISC_PRINTERS', 'yes');


/**
 * Продажби - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'sales_Sales';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Продажби на артикули";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'SALE_OVERDUE_CHECK_DELAY'    => array("time", "caption=Толеранс за просрочване на продажбата->Време"),
			'SALE_MAX_FUTURE_PRICE'       => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В бъдещето'),
			'SALE_MAX_PAST_PRICE'         => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В миналото'),
			'SALE_CLOSE_OLDER_THAN'       => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на продажбата->Дни'),
			'SALE_CLOSE_OLDER_NUM'        => array("int", 'caption=По колко продажби да се приключват автоматично на опит->Брой'),
			'SALE_FISC_PRINTER_DRIVER'    => array('class(interface=sales_FiscPrinterIntf,allowEmpty,select=title)', 'caption=Фискален принтер->Драйвър'),
			'SALE_INV_VAT_DISPLAY'        => array('enum(no=Не,yes=Да)', 'caption=Фактури изчисляване на ддс-то като процент от сумата без ддс->Избор'),
			'SALE_INV_MIN_NUMBER1'        => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER1'        => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_MIN_NUMBER2'        => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER2'        => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_HAS_FISC_PRINTERS'  => array('enum(no=Не,yes=Да)', 'caption=Фактури->Има ли фирмата касови апарати'),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'sales_Sales',
            'sales_SalesDetails',
        	'sales_Routes',
        	'sales_Quotations',
        	'sales_QuotationsDetails',
    		'sales_ClosedDeals',
    		'sales_Services',
    		'sales_ServicesDetails',
    		'sales_Invoices',
            'sales_InvoiceDetails',
    		'sales_Proformas',
    		'sales_ProformaDetails',
    		'migrate::transformProformas1',
    		'migrate::updateQuotations',
    		'migrate::updateSales',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'sales';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Продажби', 'sales_Sales', 'default', "sales, ceo"),
        );

    
    /**
     * Път до js файла
     */
//    var $commonJS = 'sales/js/ResizeQuoteTable.js';
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'sales/tpl/invoiceStyles.css, sales/tpl/styles.css';
    
    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна продажна цена"
        $html .= core_Classes::add('sales_SalesLastPricePolicy');
        
        // Добавяне на роля за старши продавач
        $html .= core_Roles::addOnce('salesMaster', 'sales');
        
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addOnce('invoicer');
        
        // acc наследява invoicer
        $html .= core_Roles::addOnce('acc', 'invoicer');
        
        // sales наследява invoicer
        $html .= core_Roles::addOnce('sales', 'invoicer');
        
        return $html;
    }
    
    
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
     * Миграция на старите проформи към новите
     */
    function transformProformas1()
    {
    	$mvc = cls::get('sales_Proformas');
    	$query = $mvc->getQuery();
    	$query->where("#state = 'active'");
    	$query->where("#saleId IS NOT NULL");
    	 
    	while($rec = $query->fetch()){
    		$saleRec = sales_Sales::fetch($rec->saleId);
    	
    		$rec->contragentClassId = $saleRec->contragentClassId;
    		$rec->contragentId = $saleRec->contragentId;
    	
    		$ContragentClass = cls::get($rec->contragentClassId);
    		$cData = $ContragentClass->getContragentData($rec->contragentId);
    	
    		$rec->contragentName = ($cData->person) ? $cData->person : $cData->company;
    		$rec->contragentAddress = $cData->address;
    	
    		$conf = core_Packs::getConfig('crm');
    		if(!$cData->countryId){
    			$cData->countryId = drdata_Countries::fetchField("#commonName = '{$conf->BGERP_OWN_COMPANY_COUNTRY}'", 'id');
    		}
    	
    		$rec->contragentCountryId = $cData->countryId;
    		$rec->contragentVatNo = $cData->vatNo;
    	
    		if(strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		} else {
    			$rec->uicNo = $cData->uicId;
    		}
    	
    		$rec->contragentPCode = $cData->pCode;
    		$rec->contragentPlace = $cData->place;
    	
    		$rec->vatRate = $saleRec->chargeVat;
    		$rec->paymentMethodId = $saleRec->paymentMethodId;
    		$rec->currencyId = $saleRec->currencyId;
    		$rec->rate = $saleRec->currencyRate;
    		$rec->deliveryId = $saleRec->deliveryTermId;
    		$rec->deliveryPlaceId = $saleRec->deliveryLocationId;
    		$rec->bankAccountId = $saleRec->accountId;
    	
    		$rec->saleId = NULL;
    		$mvc->save($rec);
    	
    		sales_ProformaDetails::delete("#proformaId = {$rec->id}");
    		$dQuery = sales_SalesDetails::getQuery();
    		$dQuery->where("#saleId = {$saleRec->id}");
    		while($dRec = $dQuery->fetch()){
    			unset($dRec->id, $dRec->saleId);
    			$dRec->proformaId = $rec->id;
    			$dRec->quantity /= $dRec->quantityInPack;
    			
    			sales_ProformaDetails::save($dRec);
    		}
    	}
    }
    
    
    /**
     * Ъпдейтва старите оферти
     */
    public function updateQuotations()
    {
    	if(sales_QuotationsDetails::count()){
    		$query = sales_QuotationsDetails::getQuery();
    		$query->where("#quantityInPack IS NULL");
    		while($rec = $query->fetch()){
    			try{
    				$rec->quantityInPack = 1;
    				sales_QuotationsDetails::save($rec, 'quantityInPack');
    			} catch(core_exception_Expect $e){
    				 
    			}
    		}
    	}
    }
    
    
    /**
     * Обновяваме продажбите
     */
    public function updateSales()
    {
    	if(sales_Sales::count()){
    		$Sales = cls::get('sales_Sales');
    		
    		$sQuery = sales_Sales::getQuery();
    		$sQuery->where("#makeInvoice IS NULL || #makeInvoice = ''");
    		$sQuery->show('id,makeInvoice');
    		while($rec = $sQuery->fetch()){
    			$rec->makeInvoice = 'yes';
    			try{
    				$Sales->save_($rec, 'makeInvoice');
    			} catch(core_exception_Expect $e){
    				
    			}
    		}
    	}
    }
}
