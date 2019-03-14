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
class h18_SalesInvoiceDetails extends core_Manager
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
        $this->dbTableName = 'sales_invoice_details';
        
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory','tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field nowrap,silent,removeAndRefreshForm=packPrice|discount,mandatory,smartCenter,input=hidden');
        $this->FLD('quantity', 'double', 'caption=Количество','tdClass=small-field,smartCenter');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $this->FLD('price', 'double', 'caption=Цена, input=none');
        $this->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        $this->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,smartCenter');
        $this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Забележки,formOrder=110001');
        
        $this->FLD('batches', 'text(rows=1)', 'caption=Допълнително->Партиди, input=none, before=notes');
     }
    
}