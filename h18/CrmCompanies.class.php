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
class h18_CrmCompanies extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Потребители';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST
            ));
        
        $this->dbTableName = 'crm_companies';
        
        $this->FLD('name', 'varchar(255,ci)', 'caption=Фирма,class=contactData,mandatory,remember=info,silent,export=Csv');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,mandatory,export=Csv');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');
        
        // Комуникации
        $this->FLD('email', 'emails', 'caption=Имейли,class=contactData,export=Csv');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Телефони,class=contactData,silent,export=Csv');
        $this->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Факс,class=contactData,silent,export=Csv');
        $this->FLD('website', 'url', 'caption=Web сайт,class=contactData,export=Csv');
        
        // Данъчен номер на фирмата
        $this->FLD('vatId', 'drdata_VatType', 'caption=ДДС (VAT) №,remember=info,class=contactData,export=Csv');
        $this->FLD('uicId', 'varchar(26)', 'caption=Национален №,remember=info,class=contactData,export=Csv');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles, passage=Общи)', 'caption=Бележки,height=150px,class=contactData,export=Csv');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого,export=Csv');
        $this->FLD('folderName', 'varchar', 'caption=Име на папка');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'persons\\\'AND #state !\\= \\\'rejected\\\',classLink=group-link)', 'caption=Групи->Групи,remember,silent,export=Csv');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }
    
}