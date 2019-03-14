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
class h18_CashPko extends core_Manager
{
    
    public $loadList = 'h18_Wrapper';

    /**
     * Заглавие
     */
    public $title = 'Приходни касови ордери';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');
        
        $this->db = cls::get('core_Db',
            array('dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST,
            ));
        
        $this->dbTableName = 'cash_pko';
        
        $this->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
        $this->FLD('amountDeal', 'double(decimals=2,max=2000000000,min=0)', 'caption=Платени,mandatory,silent');
        $this->FLD('dealCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'input=hidden');
        $this->FLD('reason', 'richtext(rows=2, bucket=Notes)', 'caption=Основание');
        $this->FLD('termDate', 'date(format=d.m.Y)', 'caption=Очаквано на,silent');
        $this->FLD('peroCase', 'key(mvc=cash_Cases, select=name,allowEmpty)', 'caption=Каса,removeAndRefreshForm=currencyId|amount,silent');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory');
        $this->FLD('contragentId', 'int', 'input=hidden,notNull');
        $this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
        $this->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
        $this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута (и сума) на плащането->Валута,silent,removeAndRefreshForm=rate|amount');
        $this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,summary=amount,input=hidden');
        $this->FLD('rate', 'double(decimals=5)', 'caption=Валута (и сума) на плащането->Курс,input=none');
        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Допълнително->Вальор,autohide');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Статус, input=none');
        $this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
     }
    
}