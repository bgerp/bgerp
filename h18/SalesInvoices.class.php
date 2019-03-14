<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Локален файлов архив
 */
class h18_SalesInvoices extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Точки на продажби';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST
            ));
        $this->dbTableName = 'sales_invoices';
        
        $this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
        $this->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('contragentName', 'varchar', 'caption=Контрагент->Име, mandatory, class=contactData');
        $this->FLD('responsible', 'varchar(255)', 'caption=Контрагент->Отговорник, class=contactData');
        $this->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контрагент->Държава,mandatory,contragentDataField=countryId');
        $this->FLD('contragentVatNo', 'drdata_VatType', 'caption=Контрагент->VAT №,contragentDataField=vatNo');
        $this->FLD('uicNo', 'type_Varchar', 'caption=Контрагент->Национален №,contragentDataField=uicId');
        $this->FLD('contragentPCode', 'varchar(16)', 'caption=Контрагент->П. код,recently,class=pCode,contragentDataField=pCode');
        $this->FLD('contragentPlace', 'varchar(64)', 'caption=Контрагент->Град,class=contactData,contragentDataField=place');
        $this->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес,class=contactData,contragentDataField=address');
        $this->FLD('changeAmount', 'double(decimals=2)', 'input=none');
        $this->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
        
        $this->FLD('dueTime', 'time(suggestions=3 дена|5 дена|7 дена|14 дена|30 дена|45 дена|60 дена)', 'caption=Плащане->Срок');
        $this->FLD('dueDate', 'date', 'caption=Плащане->Краен срок');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
        $this->FLD('rate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime,input=hidden,silent');
        $this->FLD('displayRate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime');
        $this->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,input=hidden');
        $this->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място,hint=Избор измежду въведените обекти на контрагента');
        $this->FLD('vatReason', 'varchar(255)', 'caption=Данъчни параметри->Основание,recently,Основание за размера на ДДС');
        $this->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъчни параметри->Дата на ДС,hint=Дата на възникване на данъчното събитие');
        $this->FLD('vatRate', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Данъчни параметри->ДДС,input=hidden');
        $this->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');
        $this->FLD('dealValue', 'double(decimals=2)', 'caption=Без ДДС, input=hidden,summary=amount');
        $this->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
        $this->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
        $this->FLD('sourceContainerId', 'key(mvc=doc_Containers,allowEmpty)', 'input=hidden,silent');
        $this->FLD('paymentMethodId', 'int', 'input=hidden,silent');
        
        $this->FLD('paymentType', 'enum(,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг)', 'caption=Плащане->Начин,before=accountId,mandatory');
        $this->FLD('autoPaymentType', 'enum(,cash=В брой,bank=По банков път,intercept=С прихващане,card=С карта,factoring=Факторинг,mixed=Смесено)', 'placeholder=Автоматично,caption=Плащане->Начин,input=none');
        $this->FLD('accountId', 'key(mvc=bank_OwnAccounts,select=title, allowEmpty)', 'caption=Плащане->Банкова с-ка, changable');
        $this->FLD('numlimit', 'enum(1,2)', 'caption=Диапазон, after=template,input=hidden,notNull,default=1');
        $this->FLD('number', 'bigint(21)', 'caption=Номер, after=place,input=none');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
        $this->FLD('type', 'enum(invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие,dc_note=Известие)', 'caption=Вид, input=hidden');
        
        
     }
    
}