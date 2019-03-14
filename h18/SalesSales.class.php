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
class h18_SalesSales extends core_Manager
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
        $this->dbTableName = 'sales_sales';
        
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban,allowEmpty)', 'caption=Плащане->Банкова с-ка,after=currencyRate,notChangeableByContractor');
        $this->FLD('priceListId', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Цени,notChangeableByContractor');
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date,notChangeableByContractor');
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф.,class=contactData,after=valior');
        
        // Стойности
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountBl', 'double(decimals=2)', 'caption=Стойности->Крайно салдо,input=none,summary=amount');
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е платена
        
        $this->FLD('amountVat', 'double(decimals=2)', 'input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие,notChangeableByContractor');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Доставка->До,silent,class=contactData'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryAdress', 'varchar', 'caption=Доставка->Място,notChangeableByContractor');
        $this->FLD('deliveryTime', 'datetime', 'caption=Доставка->Срок до,notChangeableByContractor'); // до кога трябва да бъде доставено
        $this->FLD('deliveryTermTime', 'time(uom=days,suggestions=1 ден|5 дни|10 дни|1 седмица|2 седмици|1 месец)', 'caption=Доставка->Срок дни,after=deliveryTime,notChangeableByContractor');
        
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)',  'caption=Доставка->От склад,notChangeableByContractor'); // наш склад, от където се експедира стоката
        
        // Плащане
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title,allowEmpty)','caption=Плащане->Метод,notChangeableByContractor');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,removeAndRefreshForm=currencyRate,notChangeableByContractor');
        $this->FLD('currencyRate', 'double(decimals=5)', 'caption=Плащане->Курс,input=hidden');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Плащане->Каса,notChangeableByContractor');
        
        // Наш персонал
        $this->FLD('initiatorId', 'user(roles=user,allowEmpty,rolesForAll=sales|ceo)', 'caption=Наш персонал->Инициатор,notChangeableByContractor');
       # $this->FLD('dealerId', "user(rolesForAll={$dealerRolesForAll},allowEmpty,roles={$dealerRolesList})", 'caption=Наш персонал->Търговец,notChangeableByContractor');
        
        // Допълнително
        $this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=Допълнително->ДДС,notChangeableByContractor');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Фактуриране,maxRadio=2,columns=2,notChangeableByContractor');
        $this->FLD('note', 'text(rows=4)', 'caption=Допълнително->Условия,notChangeableByContractor', array('attr' => array('rows' => 3)));
        
        $this->FLD('state',
            'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен, pending=Заявка,stopped=Спряно)',
            'caption=Статус, input=none'
            );
        
        $this->FLD('paymentState', 'enum(pending=Има||Yes,overdue=Просрочено,paid=Няма,repaid=Издължено)', 'caption=Чакащо плащане, input=none,notNull,value=paid');
        $this->FLD('productIdWithBiggestAmount', 'varchar', 'caption=Артикул с най-голяма стойност, input=none');
        
    }
    
}