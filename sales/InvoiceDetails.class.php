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
    var $listFields = 'productId, quantity, unit, price, amount, note, tools=Пулт';
    
    
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
        $this->FLD('priceType', 'enum(policy=По ценова политика, history=По предишна цена, input=Въведена цена)', 'caption=Ценообразуване, input=hidden, silent');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
        $this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input=none');
        $this->FLD('note', 'varchar(64)', 'caption=@Пояснение', array('attr'=>array('rows'=>2)));
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума, column');
        $this->FNC('unit', 'varchar', 'caption=Мярка');
        $this->setDbUnique('invoiceId, productId');
    }
    
    
    /**
     * Изчислява полето 'amount'
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    static function on_CalcAmount($mvc, $rec)
    {
        $rec->amount = round($rec->price * $rec->quantity, 2);
    }
    
    
    /**
     * Изчислява полето 'unit' (мярка на перото)
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    static function on_CalcUnit($mvc, $rec)
    {
        if ($rec->productId) {
        	$productRec = cat_Products::fetch($rec->productId);
            $rec->unit = cat_Products::getVerbal($productRec, 'measureId');
        }
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
            
            $data->toolbar->addBtn('По предишна цена', $addUrl + array('priceType' => 'history'));
            $data->toolbar->addBtn('Въведена цена', $addUrl + array('priceType' => 'input'));
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $form->setOptions('productId', cat_Products::getByGroup($mvc::$productGroups));
    	
        if($type = Request::get('priceType', 'varchar')){
        	$form->setField('policyId', 'input=none');
        	$form->setReadOnly('priceType');
        	if($type == 'input'){
        		$form->setField('price', 'input,mandatory');
        	}
        } else {
        	$form->setDefault('priceType', 'policy');
        }
    }


    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
           
           // Ако няма грешки, прави опит да изчисли цената според избраната стратегия
           $mvc::calculatePrice($form);
        }
    }
    
    
    /**
     * Изчислява цената на стандартен продукт според стратегията: въведена, последна или 
     * каталожна цена.
     * 
     * Изчислената цена се записва във полето на формата ($form). При проблем задава 
     * предупреждения или грешки във формата.
     * 
     * @param core_Form $form
     * 
     */
    public static function calculatePrice(core_Form $form)
    {
        $rec = $form->rec;
        
        switch ($rec->priceType) {
            case 'input':
                return;
            case 'policy':
                
            	$masterRec = sales_Invoices::fetch($rec->invoiceId);
                $contragentItem = acc_Items::fetch($masterRec->contragentAccItemId);
            	
                $Policy = cls::get($rec->policyId);
                $rec->price= $Policy->getPriceInfo($contragentItem->classId, $contragentItem->objectId, $rec->productId)->price;
               
            	break;
            case 'history':
            	
                /**
                * Последната цена, на която този продукт е фактуриран на този клиент.
                */
                $recentPrice = static::getRecentPriceFor($rec->productId, $rec->invoiceId);
                
                if ($recentPrice !== FALSE) {
                    if ($rec->price && $rec->price != $recentPrice) {
                        $form->setWarning('price',
                            "Цената бе изчислена автоматично: {$recentPrice}. Въведената цена е игнорирана!"
                        );
                    }
                    $rec->price = $recentPrice;
                } elseif (!$rec->price) {
                    $form->setError('price', 'Невъзможно е да се определи предишна цена');
                }
                
                break;
        }
    }

	
    /**
     * Намира последната цена за даден продукт
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
    
    
    /**
     * Подготвя шаблона за детайлите
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterRenderDetail1($mvc, &$res, $data)
    {return;
    
        $res = new ET(tr("|*" . "
            <table class=\"invTable\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">
                <tbody>
                <tr>
                    <td class=\"topCell\" align=\"center\">|№|*</td>
                    <td class=\"topCell\" align=\"center\">|Наименование|*<br><i>Description</i></td>
                    <td class=\"topCell\" align=\"center\">|Мярка|*<br><i>Measure</i></td>
                    <td class=\"topCell\" align=\"center\">|Количество|*<br><i>Quantity</i></td>
                    <td class=\"topCell\" align=\"center\">|Цена|*<br><i>Price</i></td>
                    <td class=\"topCell\" align=\"center\">|Стойност|*<br><i>Amount</i></td>
                </tr>"));
        
        // Брояч на редовете
        $row = new stdClass();
        $row->numb = 0;
        
        if (count($data->rows)) {
            foreach($data->rows as $id => $row) {
                $row->numb += 1;
                $rec = $data->recs[$id];
                $rec->amount = $rec->quantity * $rec->price;
                
                $row->amount = number_format($rec->amount, 2, ',', ' ');
                
                // Сума за всички редове (детайли)
                $sum += $rec->amount;
                
                $res->append("
                        <tr>
                            <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">" . $row->numb . "</td>
                            <td class=\"cell\" align=\"left\">" . $row->productId . "</td>
                            <td class=\"cell\" nowrap=\"nowrap\" align=\"center\">" . $row->unit . "</td>
                            <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">" . $row->quantity . "</td>
                            <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">" . $row->price . "</td>
                            <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">" . $row->amount . "</td>
                        </tr>");
            }
        }
        
        // ДДС
        $dds = $sum * 0.20;
        $dds = number_format($dds, 2, ', ', '');
        
        // totalSumPlusDds
        $totalSumPlusDds = $sum * 1.20;
        $totalSumPlusDds = number_format($totalSumPlusDds, 2, ', ', '');
        
        $SpellNumber = cls::get('core_SpellNumber');
        $sayWords = $SpellNumber->asCurrency($sum);
        
        $res->append("
                <tr>
                    <td class=\"cell\" colspan=\"3\" rowspan=\"4\" align=\"center\">
                        <center><table><tbody><tr><td align=\"LEFT\">С думи:</td></tr><tr><td align=\"LEFT\"><small><b>" . $sayWords . "</b></small></td></tr></tbody></table></center>
                    </td>
                    <td class=\"cell\" colspan=\"2\" align=\"RIGHT\">
                        Стойност / <i>Subtotal</i>&nbsp;
                    </td>
                    <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">&nbsp;(BGN) <b>" . $sum . "</b></td>
                </tr>
                
                <tr>
                    <td class=\"cell\" colspan=\"2\" align=\"RIGHT\">
                        Данъчна основа&nbsp;
                    </td>
                    <td class=\"cell\" style=\"color: rgb(51, 51, 51);\" nowrap=\"nowrap\" align=\"right\">(BGN) <b>" . $sum . "</b></td>
                </tr>
                
                <tr>
                    <td class=\"cell\" colspan=\"2\" align=\"RIGHT\">
                        ДДС / <i>VAT</i> &nbsp;  <b>20%</b>&nbsp;
                    </td>
                    <td class=\"cell\" style=\"color: rgb(51, 51, 51);\" nowrap=\"nowrap\" align=\"right\">(BGN) <b>" . $dds . "</b></td>
                </tr>
                
                <tr>
                    <td class=\"cell\" colspan=\"2\" align=\"RIGHT\"> Общо / <i>Total</i>&nbsp;</td>
                    <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">(BGN) <b>" . $totalSumPlusDds . "</b></td>
                </tr>
                </tbody>
            </table>");
        
        return FALSE;
    }
}