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
class h18_SalesServices extends core_Manager
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
        
        $this->dbTableName = 'sales_services';
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
        $this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено,summary=amount,input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Обект до,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        $this->FLD('received', 'varchar', 'caption=Получил');
        $this->FLD('delivered', 'varchar', 'caption=Доставил');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
        $this->FLD('state',
            'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)',
            'caption=Статус, input=none'
            );
        $this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $this->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)','input=none,notNull,value=411');
        
    }
    
}