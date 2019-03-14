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
class h18_PosReceiptDetails extends core_Manager
{
    public $loadList = 'h18_Wrapper';
    /**
     * Заглавие
     */
    public $title = 'Касови бележки';
    
    function description()
    {
        $conf = core_Packs::getConfig('h18');

        $this->db = cls::get('core_Db',
            array(  'dbName' => $conf->H18_BGERP_DATABASE,
                'dbUser' => $conf->H18_BGERP_USER,
                'dbPass' => $conf->H18_BGERP_PASS,
                'dbHost' => $conf->H18_BGERP_HOST,
            ));
        $this->dbTableName = 'pos_receipt_details';
        
        $this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
        $this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em;top:1px;position:relative');
        $this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
        $this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, class=ean-text');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
        $this->FLD('value', 'varchar(32)', 'caption=Мярка, input=hidden,smartCenter');
        $this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка,input=none');
     }
    
}