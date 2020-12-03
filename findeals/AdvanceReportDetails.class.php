<?php


/**
 * Клас 'findeals_AdvanceReportDetail'
 *
 * Детайли на мениджър на авансови отчети (@see findeals_AdvanceReports)
 *
 * @category  bgerp
 * @package   findeals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class findeals_AdvanceReportDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на авансовия отчет';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'reportId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_AlignDecimals2, doc_plg_HidePrices, plg_SaveAndNew,plg_RowNumbering,acc_plg_ExpenseAllocation,cat_plg_ShowCodes';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Финанси:Сделки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, pettyCashReport';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, pettyCashReport';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, pettyCashReport';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pettyCashReport';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId=Мярка, packQuantity, packPrice, discount, amount';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('reportId', 'key(mvc=findeals_AdvanceReports)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->setFieldTypeParams('packQuantity', 'Min=0');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
        parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        $form = &$data->form;
        $masterRec = $data->masterRec;
        
        $form->setFieldTypeParams('productId', array('customerClass' => $masterRec->contragentClassId, 'customerId' => $masterRec->contragentId, 'hasProperties' => 'canBuy', 'hasnotProperties' => 'canStore'));
        $form->setField('packPrice', 'mandatory');
        $form->setField('discount', 'input=none');
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->productId = cat_Products::getVerbal($rec->productId, 'name');
        
        if ($rec->notes) {
            $row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
        }
    }
}
