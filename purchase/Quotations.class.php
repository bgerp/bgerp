<?php


/**
 * Документ "Оферти от доставчици"
 *
 * Мениджър на документи за Оферти от доставчици
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
    public $title = 'Оферти от доставчици';


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
    public $singleIcon = 'img/16/letter.png';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,purchase';


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
    public $singleTitle = 'Оферта от доставчик';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.1|Логистика';


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
     * Кои полета да са нередактируеми, ако има вече детайли
     */
    protected $readOnlyFieldsIfHaveDetail = 'chargeVat,currencyRate,currencyId';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'validFor' => 'lastDocUser|lastDoc',
        'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId' => 'lastDocUser|lastDoc|CoverMethod',
        'chargeVat' => 'clientCondition|lastDocUser|lastDoc|defMethod',
        'deliveryTermId' => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId' => 'lastDocUser|lastDoc|',
        'company' => 'clientData',
        'pCode' => 'clientData',
        'place' => 'clientData',
        'address' => 'clientData',
        'contragentCountryId' => 'clientData',
        'template' => 'lastDocUser|lastDoc|defMethod',
    );


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setQuotationFields($this);
        $this->FLD('bankAccountId', 'iban_Type(64)', 'caption=Плащане->Към банк. сметка,after=paymentMethodId');
        $this->FLD('others', 'richtext(rows=4,bucket=purQuoteFiles)', 'caption=Допълнително->Условия');

        $this->setDbIndex('date');
        $this->setDbIndex('contragentClassId,contragentId');
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $tplArr = array();
        $tplArr[] = array('name' => 'Оферта от доставчик', 'content' => 'purchase/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/QuotationHeaderNormalNarrow.shtml');
        $tplArr[] = array('name' => 'Quotation from supplier', 'content' => 'purchase/tpl/QuotationHeaderNormalEng.shtml', 'lang' => 'en', 'narrowContent' => 'purchase/tpl/QuotationHeaderNormalEngNarrow.shtml');
        $res = doc_TplManager::addOnce($this, $tplArr);

        return $res;
    }


    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова  артикула, активираме и нея
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetch($rec->id, 'contragentClassId,contragentId,currencyId,bankAccountId');

        // Ако офертата е в папка на контрагент вкарва се в група Доставчици->Оферти
        $supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
        $groupRec = (object)array('name' => 'Оферти', 'sysId' => 'supplierQuote', 'parentId' => $supplierGroupId);
        $groupId = crm_Groups::forceGroup($groupRec);

        cls::get($rec->contragentClassId)->forceGroup($rec->contragentId, $groupId, false);

        if(empty($rec->date)){
            $rec->date = dt::now();
            $mvc->save_($rec, 'date');
        }

        if(!empty($rec->bankAccountId)){
            if(bank_Accounts::add($rec->bankAccountId, currency_Currencies::getIdByCode($rec->currencyId), $rec->contragentClassId, $rec->contragentId)){
                core_Statuses::newStatus('Добавена е нова сметка на контрагента|*!');
            }
        }
    }


    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $tpl = new core_ET(tr("Моля запознайте се с оферта от доставчик|*: #[#handle#]."));
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $form->setSuggestions('bankAccountId', bank_Accounts::getContragentIbans($rec->contragentId, $rec->contragentClassId));
    }


    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        $rec = $mvc->fetch($res->id);
        $dQuery = purchase_QuotationDetails::getQuery();
        $dQuery->where("#quotationId = {$rec->id}");
        $dQuery->show('productId');
        $productIds = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');

        if($redirectError = deals_Helper::getContoRedirectError($productIds, 'canBuy', 'generic', 'вече не са купуваеми или са генерични')){
            core_Statuses::newStatus($redirectError, 'error');

            return false;
        }
    }
}