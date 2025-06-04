<?php


/**
 * Мениджър за "Детайли на входящите оферти от доставчици"
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class purchase_QuotationDetails extends deals_QuotationDetails
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на входящите оферти от доставчици';

    /**
     * Кой може да променя?
     */
    public $canAdd = 'ceo,purchase';


    /**
     * Кой може да импортира?
     */
    public $canImport = 'ceo,purchase';


    /**
     * Кой може да променя?
     */
    public $canDelete = 'ceo,purchase';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, doc_plg_HidePrices, deals_plg_ImportDealDetailProduct, plg_SaveAndNew,cat_plg_CreateProductFromDocument,plg_PrevAndNext,cat_plg_ShowCodes';


    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Оферти';


    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canBuy';


    /**
     * Дефолтен шаблон за показване на детайлите
     */
    public $normalDetailFile = 'purchase/tpl/LayoutQuoteDetails.shtml';


    /**
     * Кратък шаблон за показване на детайлите
     */
    public $shortDetailFile = 'purchase/tpl/LayoutQuoteDetailsShort.shtml';


    /**
     * Най-кратък шаблон за показване на детайлите
     */
    public $shortestDetailFile = 'purchase/tpl/LayoutQuoteDetailsShortest.shtml';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = '';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('quotationId', 'key(mvc=purchase_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
        parent::addDetailFields($this);
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        parent::inputQuoteDetailsForm($mvc, $form);
    }
}