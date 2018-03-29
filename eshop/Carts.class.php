<?php



/**
 * Мениджър за кошница на онлайн магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Carts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Кошници на онлайн магазина";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_Rejected, doc_ActivatePlg, plg_Modified';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'payments';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //public $listFields = 'code,name,groupId,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Кошница на онлайн магазина";
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo,admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'eshop,ceo,admin';
    
    
    /**
     * Кой може да добавя в кошницата
     */
    public $canAddtocart = 'every_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none1');
    	$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none1');
    	$this->FLD('domainId', 'key(mvc=cms_Domains, select=domain)', 'caption=Домейн,silent');
    	$this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,silent');
    	
    	$this->FLD('total', 'double', 'caption=Общи данни->Стойност,silent');
    	$this->FLD('paymentId', 'key(mvc=eshop_Payments,select=title,allowEmpty)', 'caption=Общи данни->Плащане');
    	$this->FLD('termId', 'key(mvc=eshop_DeliveryTerms,select=title,allowEmpty)', 'caption=Общи данни->Доставка');
    	$this->FLD('timeId', 'key(mvc=eshop_DeliveryTimes,select=title,allowEmpty)', 'caption=Общи данни->Време');
    	$this->FLD('info', 'richtext(rows=2)', 'caption=Общи данни->Забележка');
    	
    	$this->FLD('invoiceNames', 'varchar(255)', 'caption=Данни на фирма за фактура->Наименование,class=contactData,hint=Имате на фирмата');
    	$this->FLD('invoiceVatNo', 'drdata_VatType', 'caption=Данни на фирма за фактура->VAT/EIC');
    	$this->FLD('invoiceAddress', 'varchar(255)', 'caption=Данни на фирма за фактура->Адрес,class=contactData,hint=Адрес на регистрация на фирмата');
    	$this->FLD('invoicePCode', 'varchar(16)', 'caption=Данни на фирма за фактура->П. код,class=contactData,hint=Пощенски код на фирмата');
    	$this->FLD('invoicePlace', 'varchar(64)', 'caption=Данни на фирма за фактура->Град,class=contactData,hint=Населено място: град или село и община');
    	$this->FLD('invoiceCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни на фирма за фактура->Държава,hint=Фирма на държавата');
    	
    	$this->FLD('deliveryAddress', 'varchar(255)', 'caption=Данни за доставка->Адрес,class=contactData,hint=Вашият адрес');
    	$this->FLD('deliveryPCode', 'varchar(16)', 'caption=Данни за доставка->П. код,class=contactData,hint=Пощенски код за доставка');
    	$this->FLD('deliveryPlace', 'varchar(64)', 'caption=Данни за доставка->Град,class=contactData,hint=Населено място: град или село и община');
    	$this->FLD('deliveryCountry', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за доставка->Държава,hint=Държава на доставка');
    	$this->FLD('instruction', 'richtext(rows=2)', 'caption=Данни за доставка->Инструкции');
    	
    	$this->FLD('personNames', 'varchar(255)', 'caption=Данни на лице->Имена,class=contactData,hint=Вашето име||Your name,mandatory');
    	$this->FLD('salutation', 'varchar(255)', 'caption=Данни на лице->Обръщение,class=contactData,hint=Обръщение||Salutation');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Данни на лице->Имейл,hint=Вашият имейл||Your email,mandatory');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Данни на лице->Телефони,hint=Вашият телефон,mandatory');
    	
    	$this->setDbIndex('brid');
    	$this->setDbIndex('userId');
    }
    
    
    public function act_addToCart()
    {
    	$this->requireRightFor('addtocart');
    	
    	
    }
    
    
}