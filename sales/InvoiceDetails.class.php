<?php 

/**
 * Invoice (Details)
 */
class sales_InvoiceDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на фактурата";


    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, sales_Wrapper';    
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Фактури";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'invoiceId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'invoiceId, actionType, invPeraId, orderId, note,  productId, unit, quantity, price, amount, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
     
    
    /**
     * Права
     */
    var $canWrite = 'sales, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'sales, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId',  'key(mvc=sales_Invoices)', 'caption=Поръчка, input=hidden, silent');
        $this->FLD('actionType', 'enum(sale,downpayment,deduct,discount)', 'caption=Тип');
        $this->FLD('invPeraId',  'int', 'caption=Пера');
        $this->FLD('orderId',    'int', 'caption=Поръчка');
        $this->FLD('note',       'text', 'caption=Пояснение');
        $this->FLD('productId',  'key(mvc=cat_Products, select=title)', 'caption=Продукт');
        $this->FLD('unit',       'key(mvc=cat_UoM, select=name)', 'caption=Мярка');
        $this->FLD('quantity',   'double(decimals=4)', 'caption=Количество');
        $this->FLD('price',      'double(decimals=2)', 'caption=Ед. цена');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума, column');
        
        $this->setDbUnique('invoiceId, productId');
    }
    
    
    /**
     * Изчислява полето 'amount' 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    function on_CalcAmount($mvc, $rec)
    {
        $rec->amount = round($rec->priceForOne * $rec->quantity, 2); 
    }
    
    
    /**
     * Подготвя шаблона за детайлите
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforeRenderDetail($mvc, $res, $data)
    {
        $res = new ET("
            <table class=\"invTable\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">
                <tbody>
                <tr>
                    <td class=\"topCell\" align=\"center\">№</td>
                    <td class=\"topCell\" align=\"center\">Наименование<br><i>Description</i></td>
                    <td class=\"topCell\" align=\"center\">Мярка<br><i>Measure</i></td>
                    <td class=\"topCell\" align=\"center\">Количество<br><i>Quantity</i></td>
                    <td class=\"topCell\" align=\"center\">Цена<br><i>Price</i></td>
                    <td class=\"topCell\" align=\"center\">Стойност<br><i>Amount</i></td>
                </tr>");

      // Брояч на редовете
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
	                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $row->numb . "</td>
	                        <td class=\"cell\" align=\"left\">"                     . $row->productId . "</td>
	                        <td class=\"cell\" nowrap=\"nowrap\" align=\"center\">" . $row->unit . "</td>
	                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $row->quantity . "</td>
	                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $row->price . "</td>
	                        <td class=\"cell\" nowrap=\"nowrap\" align=\"right\">"  . $row->amount . "</td>
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