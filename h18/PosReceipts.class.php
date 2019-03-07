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
class h18_PosReceipts extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Касови бележки';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->FLD('valior', 'date(format=d.m.Y)', 'caption=Дата,input=none');
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка на продажба');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
        $this->FLD('contragentObjectId', 'int', 'input=none');
        $this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0, summary=amount');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0, summary=amount');
        $this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0, summary=amount');
        $this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
        $this->FLD(
            'state',
            'enum(draft=Чернова, active=Контиран, rejected=Оттеглен, closed=Затворен,waiting=Чакащ,pending)',
            'caption=Статус, input=none'
            );
        $this->FLD('transferedIn', 'key(mvc=sales_Sales)', 'input=none');
        $this->FLD('revertId', 'key(mvc=pos_Receipts)', 'input=none');
        
        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST,
            ));
        
     }
    
}