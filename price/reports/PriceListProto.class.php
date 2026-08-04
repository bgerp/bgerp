<?php


/**
 * Общ прототип за справки, показващи продаваеми артикули с цена по избрана ценова политика.
 * Съдържа само общата за наследниците логика - избор на артикули, цена по политика, филтри
 * по групи/листване, и служебните hook-ове на формата и единичния изглед. Конкретния изглед
 * (таблица с опаковки и промяна на цени, меню със снимки и т.н.) се дефинира от наследниците.
 *
 * @see price_reports_PriceList
 * @see price_reports_Menu
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class price_reports_PriceListProto extends frame2_driver_TableData
{
    /**
     * Закръгляне на цените по подразбиране
     */
    const DEFAULT_ROUND = 5;


    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'frame2_ReportIntf,label_SequenceIntf=price_interface_LabelImpl';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'sales, priceDealer, ceo';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'productId';


    /**
     * Плейсхолдър, в който се извежда текста с валутата/ДДС преди таблицата/шаблона
     *
     * @var string
     */
    protected $vatBeforeRowPlaceholder = 'TABLE_BEFORE';


    /**
     * Добавя общите за наследниците полета на драйвера към Fieldset. Наследниците викат
     * parent::addFields() и добавят/преподреждат собствените си специфични полета чрез
     * FLD()/setField(after=|before=).
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('date', 'datetime(smartTime)', 'caption=Към дата,after=title');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Цени->Политика, silent, mandatory,after=date,removeAndRefreshForm=listingId,single=none');
        $fieldset->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Цени->Валута,input,after=policyId');
        $fieldset->FLD('vat', 'enum(yes=с включено ДДС,no=без ДДС)', 'caption=Цени->ДДС,after=currencyId,single=none');
        $fieldset->FLD('vatExceptionId', 'key(mvc=cond_VatExceptions,select=title,allowEmpty)', 'caption=Цени->ДДС изключение,after=currencyId');
        $fieldset->FLD('round', 'int(Min=0,max=6)', 'caption=Цени->Точност,autohide,after=vat');
        $fieldset->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Филтър->Групи,columns=2,placeholderType=all,after=round,single=none');
        $fieldset->FLD('listingId', 'keylist(mvc=cat_Listings,select=id,allowEmpty)', 'caption=Филтър->Листвани артикули,columns=2,after=productGroups,input=hidden,maxRadio=1');
        $fieldset->FLD('expandGroups', 'enum(yes=Да,no=Не)', 'caption=Филтър->Подгрупи,columns=2,after=listingId,single=none');
        $fieldset->FLD('notInGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Филтър->Без групи,after=expandGroups,single=none');
        $fieldset->FLD('lang', 'enum(auto=Текущ,bg=Български,en=Английски)', 'caption=Допълнително->Език,after=expandGroups,single=internal');
    }


    /**
     * Подготвя общите данни, необходими за построяване на редовете - дата, активна вариация,
     * продаваеми артикули (филтрирани по групи/листване) и курс на валутата.
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return stdClass
     */
    protected function prepareCommonData($rec, &$data)
    {
        $date = !empty($rec->date) ? $rec->date : dt::now();
        $date = strlen($date) == 10 ? "{$date} 23:59:59" : $date;

        $data->variationId = price_ListVariations::getActiveVariationId($rec->policyId, $date);

        $sellableProducts = cat_Products::getProducts(null, null, null, 'canSell', null, null, false, $rec->productGroups, $rec->notInGroups, 'yes');

        // Ако се показват за себестойност излизат и активните нестандартни със сб-ст
        if ($rec->policyId == price_ListRules::PRICE_LIST_COST) {
            $ruleQuery = price_ListRules::getQuery();
            $ruleQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
            $ruleQuery->EXT('pState', 'cat_Products', 'externalName=state,externalKey=productId');
            $ruleQuery->where("#listId = {$rec->policyId} AND #productId IS NOT NULL AND #isPublic = 'no' AND #pState = 'active'");
            $ruleQuery->groupBy('productId');

            $sellableProducts += arr::extractValuesFromArray($ruleQuery->fetchAll(), 'productId');
        }

        $sellableProducts = array_keys($sellableProducts);
        unset($sellableProducts[0]);

        // Ако има избран списък с листвани артикули оставят се само тези от тях, които отговарят на филтъра
        if (isset($rec->listingId)) {
            $listingProductIds = arr::extractValuesFromArray(cat_Listings::getAll($rec->listingId), 'productId');
            $sellableProducts = array_values(array_intersect($sellableProducts, $listingProductIds));
        }

        // Вдигане на времето за изпълнение, според броя записи
        $timeLimit = countR($sellableProducts) * 0.7;
        core_App::setTimeLimit($timeLimit, false, 600);

        $currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, acc_Periods::getBaseCurrencyCode($rec->date));

        $pQuery = cat_Products::getQuery();
        if (countR($sellableProducts)) {
            $pQuery->in('id', $sellableProducts);
        } else {
            $pQuery->where("1=2");
        }
        $pQuery->show('groups,code,measureId,name,isPublic,nameEn');
        $pRecs = $pQuery->fetchAll();

        // Предварително зареждане в хита на ддс-то и групите на артикулите за по-лесно ползване
        cat_products_VatGroups::getVats(array_keys($pRecs), $date, $rec->vatExceptionId);
        price_ListRules::preloadGroups($pRecs);

        return (object) array(
            'date' => $date,
            'sellableProducts' => $sellableProducts,
            'pRecs' => $pRecs,
            'currencyRate' => $currencyRate,
        );
    }


    /**
     * Подготвя базовия обект за ред за артикул (без опаковки/промяна на цена)
     *
     * @param stdClass $rec
     * @param stdClass $productRec
     * @param string   $date
     *
     * @return stdClass $obj
     */
    protected function preparePriceRow($rec, $productRec, $date)
    {
        $obj = (object) array('productId' => $productRec->id,
            'code' => (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}",
            'measureId' => $productRec->measureId,
            'isPublic' => $productRec->isPublic,
            'vat' => cat_Products::getVat($productRec->id, $date, $rec->vatExceptionId),
            'packs' => array(),
            'groups' => $productRec->groups,
            'type' => null);

        return $obj;
    }


    /**
     * Подрежда готовите редове по избраните групи артикули (и им попълва groupName)
     *
     * @param stdClass $rec
     * @param array    $recs
     */
    protected function orderRecsByGroups($rec, &$recs)
    {
        if (!countR($recs)) return;

        // Ако няма избрани групи, търсят се всички
        $productGroups = $rec->productGroups;
        if (empty($productGroups)) {
            $productGroups = arr::extractValuesFromArray(cat_Groups::getQuery()->fetchAll(), 'id');
            $productGroups = keylist::fromArray($productGroups);
        }

        if ($rec->lang != 'auto') {
            core_Lg::push($rec->lang);
        }

        $expand = $rec->expandGroups === 'yes';
        store_InventoryNoteSummary::filterRecs($productGroups, $recs, 'code', 'name', 'groups', $expand);

        if ($rec->lang != 'auto') {
            core_Lg::pop();
        }
    }


    /**
     * Какъв ще е езика с който ще се рендират данните на шаблона
     *
     * @param stdClass $rec
     *
     * @return string|null езика с който да се рендират данните
     */
    public function getRenderLang($rec)
    {
        return ($rec->lang == 'auto') ? null : $rec->lang;
    }


    /**
     * рендиране на таблицата/шаблона
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        $tpl = parent::renderTable($rec, $data);

        $vatRow = core_Type::getByName('enum(yes=с включено ДДС,no=без ДДС)')->toVerbal($rec->vat);
        $beforeRow = tr("Всички цени са в|* {$rec->currencyId}, |{$vatRow}|*");
        $tpl->prepend($beforeRow, $this->vatBeforeRowPlaceholder);

        return $tpl;
    }


    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        if (isset($rec->listingId)) {
            $row->listingId = cat_Listings::getHyperlink($rec->listingId, true);
        }

        $row->policyId = price_Lists::getHyperlink($rec->policyId, true);
        $row->productGroups = (!empty($rec->productGroups)) ? implode(', ', cat_Groups::getLinks($rec->productGroups)) : tr('Всички');
        if (!empty($rec->notInGroups)) {
            $row->notInGroups = implode(', ', cat_Groups::getLinks($rec->notInGroups));
        }

        $dateHint = false;
        $date = $rec->date;
        if (empty($date)) {
            $date = dt::verbal2mysql($rec->lastRefreshed);
            $dateHint = true;
        }
        $row->date = core_Type::getByName('datetime(format=smartTime)')->toVerbal($date);
        if ($dateHint) {
            $row->date = ht::createHint($row->date, 'Датата ще се опресни при следващата актуализация', 'notice', false);
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setField('round', 'placeholder=' . self::DEFAULT_ROUND);
        $form->setSuggestions('round', array('' => '', 2 => 2, 4 => 4));
        $form->setDefault('lang', 'auto');

        // Ако е в папка на контрагент
        $defaultListId = price_ListRules::PRICE_LIST_CATALOG;
        $Cover = doc_Folders::getCover($form->rec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $defaultListId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
            $form->setDefault('vat', deals_Helper::getDefaultChargeVat($Embedder, $form->rec));
            $form->setDefault('currencyId', $Cover->getDefaultCurrencyId());

            $listOptions = price_Lists::getAccessibleOptions($Cover->className, $Cover->that);
        } else {
            $listOptions = price_Lists::getAccessibleOptions(null, null, false);
        }

        $form->setOptions('policyId', $listOptions);
        $form->setDefault('policyId', $defaultListId);

        // Ако е в папка с контрагентски данни
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $cData = doc_Folders::getContragentData($form->rec->folderId);
            $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
            $lang = (!empty($cData->countryId) && $cData->countryId != $bgId) ? 'en' : 'bg';
            $form->setDefault('lang', $lang);
        }

        // Ако има посочена ценова политика
        if (isset($rec->policyId)) {
            $listingId = null;

            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {

                // Ако справката е в папка на клиент взима се списъка му листвани артикули
                $listingId = cond_Parameters::getParameter($Cover->className, $Cover->that, 'salesList');
            } else {

                // Ако справката не е в папка на клиент, проверява се дали политиката е частна
                // ако е частна, взима се списъка от листваните артикули на клиента
                $listRec = price_Lists::fetch($rec->policyId);
                if ($listRec->public == 'no') {
                    if ($foundRec = price_ListToCustomers::fetch("#listId = {$rec->policyId}")) {
                        $listingId = cond_Parameters::getParameter($foundRec->cClass, $foundRec->cId, 'salesList');
                    }
                }
            }

            // Ако има намерен списък с листвани артикули, показва се като възможност за избор
            if (isset($listingId)) {
                $form->setField('listingId', 'input');
                $form->setOptions('listingId', array('' => '') + array("{$listingId}" => cat_Listings::getTitleById($listingId, false)));
            }
        }
    }


    /**
     * При събмитване на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (cat_Groups::checkForNestedGroups($form->rec->productGroups)) {
                $form->setError('productGroups', 'Избрани са вложени групи');
            }
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if (isset($data->rec->data->variationId)) {
            $data->row->variationId = price_Lists::getHyperlink($data->rec->data->variationId, true);
        }

        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            if ($data->rec->showLetterHeadWhenSending == 'no') return;
            unset($data->row->variationId);
        }

        $fieldTpl = $Driver->getSingleFieldTpl($data);

        foreach ($Driver->getSingleFieldsToReplace() as $field) {
            $fieldTpl->replace($data->row->{$field} ?? null, $field);
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * Шаблонът за филтърната информация, показвана в единичния изглед на справката
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    abstract protected function getSingleFieldTpl($data);


    /**
     * Кои полета от вербализирания ред да се заместят в шаблона от getSingleFieldTpl()
     *
     * @return array
     */
    protected function getSingleFieldsToReplace()
    {
        return array('date', 'productGroups', 'notInGroups', 'policyId', 'currencyId', 'variationId');
    }


    /**
     * Може ли справката да бъде изпращана по имейл
     *
     * @param mixed $rec
     *
     * @return bool
     */
    public function canBeSendAsEmail($rec)
    {
        return true;
    }


    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        return true;
    }


    /**
     * Изборът на потребители които да бъдат нотифицирани при обновяване дали са задължителни
     *
     * @param mixed $rec
     *
     * @return boolean
     */
    public function requireUserForNotification($rec)
    {
        return false;
    }


    /**
     * Заглавие от източника на етикета
     *
     * @param mixed $id
     *
     * @return void
     */
    public function getLabelSourceLink($id)
    {
        return frame2_Reports::getLabelSourceLink($id);
    }
}