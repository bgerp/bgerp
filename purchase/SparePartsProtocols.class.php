<?php

/**
 * Документ  за Протоколи за предаване и ремонт на машина
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_SparePartsProtocols extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';


    /**
     * Заглавие
     */
    public $title = 'Протоколи за резервни части';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за резервни части';


    /**
     * Дали да се добави документа като линк към оридижина си
     */
    public $addLinkedDocumentToOriginId = true;


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'purchase_Wrapper, bgerp_plg_Blank, doc_ActivatePlg, plg_Printing, doc_SharablePlg, plg_RowTools2, doc_DocumentPlg, acc_plg_DocumentSummary, doc_EmailCreatePlg, cat_plg_AddSearchKeywords, plg_Search';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, purchase, acc';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, purchase, acc';


    /**
     * Кой може да създава?
     */
    public $canAdd = 'ceo, purchase, acc';


    /**
     * Кой може да се прави на заявка?
     */
    public $canPending = 'ceo, purchase, acc';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, purchase, acc';


    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, purchase, acc';

    /**
     * Абревиатура
     */
    public $abbr = 'Spp';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'date,modifiedOn,createdOn';


    /**
     * Икона на документа
     */
    public $singleIcon = 'img/16/document_accept.png';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutAssetTransferAndRepairReports.shtml';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_SparePartsProtocolDetails,purchase_SparePartsProtocolReturnedDetails';


    /**
     * Ключови думи от артикулите в кои детайли да се търсят в модела
     */
    public $addProductKeywordsFromDetails = 'purchase_SparePartsProtocolDetails,purchase_SparePartsProtocolReturnedDetails';


    /**
     * Кои полета ще виждаме в листовия изглед
     */
    public $listFields = 'title=Документ, date=Дата, originId=Вх. фактура, assetId=Машина, folderId, state, createdOn,createdBy,modifiedOn,modifiedBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'allowedBy,assetId,assetModel,assetSerial,assetInvNum,notes,handedOverForRepairBy,receivedForRepairBy,handedOverFromRepairBy,receivedFromRepairBy,repairBy';


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('date', 'datetime(requireTime)', 'caption=I. Предаване->Дата,mandatory');
        $this->FLD('allowedBy', 'varchar', 'caption=I. Предаване->Разрешил');
        $this->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty,)', 'caption=I. Предаване->Оборудване,mandatory,silent,removeAndRefreshForm=assetModel|assetSerial|assetInvNum|assetManifactureOn');

        $this->FLD('assetModel', 'varchar', 'caption=I. Предаване->Модел');
        $this->FLD('assetSerial', 'varchar', 'caption=I. Предаване->Сериен №');
        $this->FLD('assetInvNum', 'varchar', 'caption=I. Предаване->Инв. номер');
        $this->FLD('assetManifactureOn', 'int', 'caption=I. Предаване->Година на произв.');

        $this->FLD('notes', 'text(rows=4)', 'caption=I. Предаване->Описание');
        $this->FLD('handedOverForRepairBy', 'varchar', 'caption=I. Предаване->Предадено от');
        $this->FLD('receivedForRepairBy', 'varchar', 'caption=I. Предаване->Прието от');

        $this->FLD('handedOverOn', 'datetime(requireTime)', 'caption=II. Ремонт->Предадена на');
        $this->FLD('warranty', 'enum(3=3 месеца,6=6 месеца, 12=12 месеца, 24=24 месеца)', 'caption=II. Ремонт->Гаранция,maxRadio=4,columns=4');
        $this->FLD('repairBy', 'text(rows=4)', 'caption=II. Ремонт->Извършили');
        $this->FLD('manHours', 'int', 'caption=II. Ремонт->Работни,unit=човекочаса');
        $this->FLD('handedOverFromRepairBy', 'varchar', 'caption=II. Ремонт->Предадено от');
        $this->FLD('receivedFromRepairBy', 'varchar', 'caption=II. Ремонт->Прието от');
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $dCount = purchase_SparePartsProtocolDetails::count("#protocolId = '{$data->form->rec->id}'");
        if (!$dCount) {
            $data->form->toolbar->removeBtn('activate');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;

            if(!empty($rec->assetManifactureOn)){
                if(!((int)$rec->assetManifactureOn >= 1000 && (int)$rec->assetManifactureOn <= (date("Y") + 20))){
                    $form->setError('assetManifactureOn', 'Невалидна година на производство');
                }
            }

        }
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $originId = Request::get('originId', 'int');

        return isset($originId);
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;

        $machineTypeId = planning_AssetGroups::fetchField("#name= 'Машини'");
        $assetOptions = array();
        $aQuery = planning_AssetResources::getQuery();
        $aQuery->where("#state = 'active' AND #groupId = {$machineTypeId}");

        while($aRec = $aQuery->fetch()){
            $assetOptions[$aRec->id] = planning_AssetResources::getRecTitle($aRec, false);
        }

        $form->setOptions('assetId', array('' => '') + $assetOptions);

        $currentYear = date("Y");
        $startYear = $currentYear - 50;
        $assetManifactureOnOptions = array();
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $assetManifactureOnOptions["{$year}"] = "{$year}";
        }
        $form->setSuggestions('assetManifactureOn', $assetManifactureOnOptions);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'add' && isset($rec)){
            if(!empty($rec->originId)){
                $doc = doc_Containers::getDocument($rec->originId);
                if(!$doc->isInstanceOf('purchase_Invoices')){
                    $requiredRoles = 'no_one';
                } else {
                    $docRec = $doc->fetch();
                    if($docRec->state != 'active' || $docRec->type != 'invoice' || $docRec->dpOperation != 'none'){
                        $requiredRoles = 'no_one';
                    } else {
                        $count = self::getTransferableProducts($docRec, true);
                        if(!$count){
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            } else {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Има ли артикули от фактурата за които може да се пуска протокола
     *
     * @param stdClass $invoiceRec - запис на вх. фактура
     * @param bool $onlyCount      - дали да се върне само бройка
     * @return array
     */
    public static function getTransferableProducts($invoiceRec, $onlyCount = false)
    {
        $aboveAmount = purchase_Setup::get('ASSET_TRANSFER_REPLACEMENTS_FROM_INVOICE_ABOVE');
        $invoiceBaseCurrency = acc_Periods::getBaseCurrencyCode($invoiceRec->date);
        $toAboveAmount = currency_CurrencyRates::convertAmount($aboveAmount, $invoiceRec->date, null, $invoiceBaseCurrency);

        $iQuery = purchase_InvoiceDetails::getQuery();
        $iQuery->where("#invoiceId = '{$invoiceRec->id}'");
        $iQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $iQuery->where("#amount > {$toAboveAmount}");
        $iQuery->show('productId,quantity,amount');
        $replacementsIds = cat_Groups::getKeylistBySysIds('replacements');
        plg_ExpandInput::applyExtendedInputSearch('cat_Products', $iQuery, $replacementsIds, 'productId');

        if($onlyCount) return $iQuery->count();

        $res = array();
        while($iRec = $iQuery->fetch()){
            unset($iRec->id);
            $res[] = $iRec;
        }

        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     * @param array $fields полета
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(!Mode::isReadOnly()){
            $row->assetId = planning_AssetResources::getHyperlink($rec->assetId, true);
        }

        if(isset($fields['-single'])){
            list($row->date, $row->time) = explode(' ', $row->date);
            list($row->handedOverOn, $row->handedOverOnTime) = explode(' ', $row->handedOverOn);

            if(!empty($rec->repairBy)){
                $repairByArr = explode("\n", $rec->repairBy);
            } else {
                $repairByArr = array(0 => '', 1 => '');
            }

            $repairByUl = "<ol>";
            foreach ($repairByArr as $repairBy){
                $repairByUl .= "<li>$repairBy</li>";
            }
            $repairByUl .= "</ol>";
            $row->repairBy = $repairByUl;

            $ownCompanyData = crm_Companies::fetchOwnCompany(null, $rec->date);
            foreach (array('companyVerb', 'vatNo', 'uicId', 'email', 'tel', 'address', 'pCode', 'place') as $fld){
                $row->{$fld} = $ownCompanyData->{$fld};
            }
        }

        if(isset($fields['-list'])){
            $row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
            $row->title = $mvc->getLink($rec->id, 0);
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

        $tpl = new ET("Моля запознайте се с нашия протокол за резервни части: #[#handle#]");
        $tpl->append($handle, 'handle');

        return $tpl->getContent();
    }


    /**
     * Връща заглавието на имейла
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     * @return string
     *
     * @see email_DocumentIntf
     */
    public function getDefaultEmailSubject($id, $forward = false)
    {
        $rec = $this->fetchRec($id);

        return tr("Протокол за резервни части|* #{$rec->id}");
    }


    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();

        $row->title = $this->singleTitle . " №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;

        return $row;
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $origin = doc_Containers::getDocument($rec->originId);

        $Details = cls::get('purchase_SparePartsProtocolDetails');
        $invoiceRec = $origin->fetch();
        $baseCurrencyInvoiceId = acc_Periods::getBaseCurrencyCode($invoiceRec->date);
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->date);
        $products = self::getTransferableProducts($invoiceRec);
        foreach ($products as $productRec){
            $productRec->amount = currency_CurrencyRates::convertAmount($productRec->amount, $rec->date, $baseCurrencyInvoiceId, $baseCurrencyId);
            $productRec->protocolId = $rec->id;
            $Details->save($productRec);
        }
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);

        return $this->save($rec);
    }
}