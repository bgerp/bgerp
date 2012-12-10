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
    var $listFields = 'itemId, quantity, unit, price, amount, note, tools=Пулт';
    
    
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
     * Описание на модела
     */
    function description()
    {
        $this->FLD('invoiceId', 'key(mvc=sales_Invoices)', 'caption=Фактура, input=hidden, silent');
        $this->FLD('itemId', 'acc_type_Item', 'caption=Перо,mandatory');
        $this->FLD('itemType', 'enum(standard, custom)', 'caption=Тип, input=hidden, silent');
        $this->FLD('quantity', 'double(decimals=4)', 'caption=Количество,mandatory');
        
        $this->FLD('priceType', 'enum(policy=По ценова политика, history=По предишна цена, input=Въведена цена)', 'caption=Ценообразуване, input=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input=none');
        
        $this->FLD('note', 'varchar(64)', 'caption=@Пояснение', array('attr'=>array('rows'=>2)));
        
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума, column');
        $this->FNC('unit', 'varchar', 'caption=Мярка');
        
        $this->setDbUnique('invoiceId, itemId');
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
        if ($rec->itemId) {
            $itemRec = acc_Items::fetch($rec->itemId);
            $rec->unit = acc_Items::getVerbal($itemRec, 'uomId');
        }
    }
    
    
    /**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->removeBtn('*');
        
        $addUrl = array($mvc, 'add', $mvc->masterKey=>$data->masterId, 'ret_url'=>true);
        
        $data->toolbar->addBtn('Стандартен продукт', 
            $addUrl + array('itemType'=>'standard'), 
            array('class'=>'btn-add')
        );
    }
    
    
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        /* @var $form core_Form */
        $form = $data->form;
        
        switch ($form->rec->itemType)
        {
            case 'standard':
                $itemField = $form->getField('itemId');
                $itemField->type->params['lists'] = 'standardProducts|goods';
                
                $form->setField('priceType, price', 'input=input');
                
                break;
            case 'custom':
            default:
                bp('TODO ...');
        }
    }


    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        switch ($form->rec->itemType)
        {
            case 'standard':
                $mvc::validatePrice($form);
                
                if (!$form->gotErrors()) {
                    // Ако няма грешки, прави опит да изчисли цената според избраната стратегия
                    $mvc::calculatePrice($form);
                }
                
                break;
                
            case 'custom':
                
            default:
                $form->setError('itemType', 'Не е реализирано');
        }
    }
    
    
    /**
     * Валидация на полето 'price'
     * 
     * Ако има, задава грешки и предупреждения във формата.
     * 
     * @param core_Form $form
     */
    public static function validatePrice(core_Form $form)
    {
        $rec = $form->rec;
        
        if ($rec->priceType == 'input' && !$rec->price) {
            $form->setError('price',
                'Полето е задължително'
            );
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
                /**
                 * @TODO цената според актуалната ценова политика (този продукт и този клас клиенти)
                 */
                $form->setError('priceType', 'Не е реализирано');
                break;
            case 'history':
                /**
                * Последната цена, на която този продукт е фактуриран на този клиент.
                */
                $recentPrice = static::getRecentPriceFor($rec->itemId, $rec->invoiceId);
                
                if ($recentPrice !== FALSE) {
                    if ($rec->price && $rec->price != $recentPrice) {
                        $form->setWarning('price',
                            "Цената бе изчислена автоматично: {$recentPrice}. Въведената цена е игнорирана!"
                        );
                    }
                    $rec->price = $recentPrice;
                } elseif (!$rec->price) {
                    $form->setError('priceType, price', 'Невъзможно е да се определи предишна цена');
                }
                
                break;
        }
    }


    public static function getRecentPriceFor($itemId, $invoiceId)
    {
        expect($contragentAccItemId = sales_Invoices::fetchField($invoiceId, 'contragentAccItemId'));
    
        /* @var $query core_Query */
        $query = static::getQuery();
    
        $query->EXT('contragentAccItemId', 'sales_Invoices', 'externalName=contragentAccItemId,externalKey=invoiceId');
        $query->EXT('invoiceDate', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');
        $query->EXT('invoiceState', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');
    
        $query->where("#contragentAccItemId = {$contragentAccItemId}");
        $query->where("#itemId = {$itemId}");
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
    
    
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        
    }
    
    
    /**
     * Подготвя шаблона за детайлите
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterRenderDetail1($mvc, &$res, $data)
    { return;
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