<?php


/**
 * Документ "Входяща оферта от доставчик"
 *
 * Мениджър на документи за Входящи оферти от доставчици
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_Quotations extends deals_QuotationMaster
{
    /**
     * Заглавие
     */
    public $title = 'Вдодящи оферти от доставчици';


    /**
     * Абревиатура
     */
    public $abbr = 'Pq';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Sorting, purchase_Wrapper, doc_plg_Close, doc_EmailCreatePlg, acc_plg_DocumentSummary, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, plg_Printing, doc_ActivatePlg, plg_Clone, bgerp_plg_Blank, cond_plg_DefaultValues,doc_plg_SelectFolder,plg_LastUsedKeys,cat_plg_AddSearchKeywords, plg_Search';


    /**
     * Кой може да затваря?
     */
    public $canClose = 'ceo,purchase';


    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,purchaseMaster,manager';


    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/doc_table.png';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,debug';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,purchase';


    /**
     * Кой има право да добавя?
     */
    public $canWrite = 'ceo,purchase';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_QuotationDetails';


    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'purchase_QuotationDetails';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Входяща оферта от доставчик';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.999|Търговия';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'purchase_QuotationDetails';


    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, purchase';


    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo, purchase';


    /**
     * Кой  може да клонира системни записи
     */
    public $canClonesysdata = 'ceo, purchase';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,purchase';


    /**
     * Клас за сделка, който последва офертата
     */
    protected $dealClass = 'purchase_Purchases';


    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'purchase/tpl/QuotationHeaderNormal.shtml';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setQuotationFields($this);
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Входяща оферта от доставчик нормален изглед', 'content' => 'purchase/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg', 'narrowContent' => null);
        //$tplArr[] = array('name' => 'Quotation', 'content' => 'sales/tpl/QuotationHeaderNormalEng.shtml', 'lang' => 'en', 'narrowContent' => 'sales/tpl/QuotationHeaderNormalEngNarrow.shtml');
        $res = doc_TplManager::addOnce($this, $tplArr);

        return $res;
    }
}