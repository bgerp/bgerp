<?php 


/**
 * Invoice (Details)
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_InvoiceDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на фактурата";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering';
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Фактури";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'invoiceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId, quantity, unit=Мярка, price, amount, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'sales, admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'sales, admin';
    
    
    /**
     * sysId-та на  групи, от които можем да задаваме продукти
     */
    public static $productGroups = array('goods', 
    									 'productsStandard', 
    									 'productsNonStand', 
    									 'services');
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт');
        $this->FLD('quantity', 'double(decimals=4)', 'caption=Количество,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
        $this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input');
        $this->FLD('note', 'varchar(64)', 'caption=@Пояснение');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума,input=none');
        $this->setDbUnique('invoiceId, productId');
    }
    
    
    /**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $pricePolicies = core_Classes::getOptionsByInterface('price_PolicyIntf');
            
            $contragentItem = acc_Items::fetch($data->masterData->rec->contragentAccItemId);
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                
                $data->toolbar->addBtn($Policy->getPolicyTitle($contragentItem->classId, $contragentItem->objectId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId},class=btn-shop");
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $form->setOptions('productId', cat_Products::getByGroup($mvc::$productGroups));
    }


    /**
     * Извиква се след изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
           
          if(!$form->rec->price){
          	
          	// Ако не е зададена цена, извличаме я от избраната политика
          	$masterRec = sales_Invoices::fetch($form->rec->invoiceId);
            $contragentItem = acc_Items::fetch($masterRec->contragentAccItemId);
            $Policy = cls::get($form->rec->policyId);
            $form->rec->price = $Policy->getPriceInfo($contragentItem->classId, $contragentItem->objectId, $form->rec->productId)->price;
          
	        if(!$form->rec->price){
	          $form->setError('price', 'Неможе да се определи цена');
	        }
          }
          
          // Изчисляваме цената
          $form->rec->amount = round($form->rec->price * $form->rec->quantity, 2);
        }
    }

    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$varchar = cls::get('type_Varchar');
    	$row->quantity = $varchar->toVerbal(floatval($rec->quantity));
    	
    	if($rec->note){
	    	$row->note = $varchar->toVerbal($rec->note);
	    	$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
    	}
    	
    	$productRec = cat_Products::fetch($rec->productId);
        $row->unit = cat_Products::getVerbal($productRec, 'measureId');
    }
	
    
    /**
     * Намира последната цена за даден продукт
     * @TODO Не се използва засега
     * @param int $productId - продукт, за който проверяваме
     * @param int $invoiceId - фактура
     * @return double $recentPrice - последната цена
     */
    public static function getRecentPriceFor($productId, $invoiceId)
    {
        expect($contragentAccItemId = sales_Invoices::fetchField($invoiceId, 'contragentAccItemId'));
    
        $query = static::getQuery();
    	$query->EXT('contragentAccItemId', 'sales_Invoices', 'externalName=contragentAccItemId,externalKey=invoiceId');
        $query->EXT('invoiceDate', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');
        $query->EXT('invoiceState', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');
    
        $query->where("#contragentAccItemId = {$contragentAccItemId}");
        $query->where("#productId = {$productId}");
        $query->where("#invoiceId <> {$invoiceId}");
        $query->where("#invoiceState <> 'rejected'");
        $query->where("#price IS NOT NULL");
        $query->orderBy('invoiceDate', 'DESC');
        $query->limit(1);
    
        $recentPrice = FALSE;
    
        if ($rec = $query->fetch()) {
            $recentPrice = $rec->price;
        }
    
        return $recentPrice;
    }
}