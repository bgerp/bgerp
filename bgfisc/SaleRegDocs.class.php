<?php


/**
 * Документи за регистриране на продажба при неприсъствено картово плащане по чл. 52о от Наредба Н-18
 *
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgfisc_SaleRegDocs extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Документи за регистриране на продажба (чл. 52о)';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Документ за регистриране на продажба';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Created, plg_Sorting, plg_SelectPeriod, plg_Search, plg_Printing';


    /**
     * Полета за търсене
     */
    public $searchFields = 'number, orderNumber, transactionRef';


    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'issuedOn, number, ownCompanyId, orderNumber, saleId, transactionRef, total, currencyId';


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bgfisc/tpl/SingleLayoutSaleRegDoc.shtml';


    /**
     * Кой може да разглежда
     */
    public $canList = 'sales,eshop,ceo';


    /**
     * Кой може да разглежда сингъла
     */
    public $canSingle = 'sales,eshop,ceo';


    /**
     * Документите се създават само от системата и не се променят
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да създаде документа отново, ако не е създаден при плащането
     */
    public $canCreateforcart = 'ceo,eshop';


    /**
     * Публичният изглед и PDF-ът са за купувача - достъпът е по подписания адрес
     */
    public $canShow = 'every_one';
    public $canPdf = 'every_one';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('number', 'bigint', 'caption=Номер,input=none');
        $this->FLD('issuedOn', 'datetime(format=smartTime)', 'caption=Дата,input=none');
        $this->FLD('ownCompanyId', 'key(mvc=crm_Companies,select=name)', 'caption=Наша фирма,input=none');
        $this->FLD('domainId', 'key(mvc=cms_Domains,select=titleExt)', 'caption=Магазин,input=none');
        $this->FLD('shopNumber', 'varchar(10)', 'caption=Номер на магазина от НАП,input=none');
        $this->FLD('cartId', 'key(mvc=eshop_Carts)', 'caption=Кошница,input=none');
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
        $this->FLD('orderNumber', 'varchar(32)', 'caption=Поръчка,input=none');
        $this->FLD('paymentCode', 'int', 'caption=Код на плащането (PAYM),input=none');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=title)', 'caption=Начин на плащане,input=none');
        $this->FLD('transactionRef', 'varchar(64)', 'caption=Трансакция,input=none');
        $this->FLD('posId', 'varchar(32)', 'caption=Виртуален ПОС,input=none');
        $this->FLD('merchantId', 'varchar(32)', 'caption=Merchant ID,input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Сума,input=none');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,input=none');
        $this->FLD('lines', 'blob(1000000, serialize, compress)', 'caption=Редове,input=none,column=none,single=none');

        // Номерът е уникален за цялата дейност на търговеца, а трансакцията се регистрира веднъж
        $this->setDbUnique('ownCompanyId,number');
        $this->setDbUnique('transactionRef');
        $this->setDbUnique('cartId');
        $this->setDbIndex('saleId');
    }


    /**
     * Създава документа за платена с карта онлайн поръчка - при повторно извикване връща вече създадения
     *
     * @param stdClass $cartRec     - количката
     * @param stdClass $saleRec     - продажбата по нея
     * @param array    $paymentData - transactionRef, posId, merchantId, paidOn, amount, currencyCode (валутата на сумата, ако не е тази на продажбата)
     *
     * @return stdClass
     */
    public static function create($cartRec, $saleRec, $paymentData = array())
    {
        expect(!empty($cartRec->id) && !empty($saleRec->id), 'Липсва количка или продажба');
        $transactionRef = $paymentData['transactionRef'] ?? null;
        $settings = cms_Domains::getSettings($cartRec->domainId ?? null);
        $ownCompanyId = !empty($saleRec->ownCompanyId) ? $saleRec->ownCompanyId : crm_Setup::BGERP_OWN_COMPANY_ID;
        $lines = static::prepareLines($saleRec);
        $rec = (object) array(
            'issuedOn' => $paymentData['paidOn'] ?? dt::now(),
            'ownCompanyId' => $ownCompanyId,
            'domainId' => $cartRec->domainId ?? null,
            'shopNumber' => $paymentData['shopNumber'] ?? ($settings->n18ShopNumber ?? null),
            'paymentCode' => $paymentData['paymentCode'] ?? null,
            'cartId' => $cartRec->id,
            'saleId' => $saleRec->id,
            'orderNumber' => $cartRec->id,
            'paymentMethodId' => $cartRec->paymentId ?? null,
            'transactionRef' => $transactionRef,
            'posId' => $paymentData['posId'] ?? null,
            'merchantId' => $paymentData['merchantId'] ?? null,
            'total' => static::getPaidAmount($cartRec, $saleRec, $paymentData, $lines),
            'currencyId' => $saleRec->currencyId ?? null,
            'lines' => $lines,
        );

        // Номерата нарастват със стъпка 1 без пропуски, затова се взимат под заключване
        $lockKey = "bgfisc_SaleRegDocs_{$ownCompanyId}";
        expect(core_Locks::obtain($lockKey, 60, 20, 30), 'Номерът на документа е зает');
        try {
            // Повторно плащане за същата количка или трансакция не създава втори документ
            $exQuery = static::getQuery();
            $exQuery->where("#cartId = {$cartRec->id}");
            if (!empty($transactionRef)) {
                $exQuery->orWhere(array("#transactionRef = '[#1#]'", $transactionRef));
            }
            $exRec = $exQuery->fetch();
            if (is_object($exRec)) {

                return $exRec;
            }

            $query = static::getQuery();
            $query->where("#ownCompanyId = {$ownCompanyId}");
            $query->XPR('maxNumber', 'bigint', 'MAX(#number)');
            $query->show('maxNumber');
            $rec->number = ($query->fetch()->maxNumber ?? 0) + 1;
            expect($rec->number <= 9999999999, 'Изчерпани са 10-цифрените номера');
            static::save($rec);
        } finally {
            core_Locks::release($lockKey);
        }

        return $rec;
    }


    /**
     * Създаване на документа за количка, при която това не е станало при плащането
     */
    public function act_CreateForCart()
    {
        expect($id = Request::get('id', 'int'));
        expect($cartRec = eshop_Carts::fetch($id));
        $this->requireRightFor('createforcart', $cartRec);
        expect(!empty($cartRec->saleId) && ($saleRec = sales_Sales::fetch($cartRec->saleId)), 'Количката няма продажба');

        $docRec = bgfisc_plg_EshopCarts::createDoc(cls::get('eshop_Carts'), $cartRec, $saleRec);
        if (!is_object($docRec)) {

            followRetUrl(null, 'Документът не е създаден - причината е в системния лог на количката|*!', 'error');
        }
        $this->logWrite('Ръчно създаване по количка', $docRec->id);

        return new Redirect(array($this, 'single', $docRec->id), 'Документът е създаден|*!');
    }


    /**
     * Публичен изглед за купувача
     */
    public function act_Show()
    {
        $rec = $this->fetchByPublicKey();
        $this->requireRightFor('show', $rec);

        $actions = new ET("<div class='srd-actions'>[#PRINT#] [#PDF#]</div>");
        $actions->replace(ht::createFnBtn('Печат', 'window.print();', false, array('ef_icon' => 'img/16/printer.png')), 'PRINT');
        $actions->replace(ht::createBtn('PDF', static::getPublicUrl($rec, 'pdf'), false, false, 'ef_icon=img/16/doc_pdf.png'), 'PDF');
        $tpl = new ET('[#1#][#2#]', $actions, static::renderDoc($rec));
        $this->logRead('Публичен изглед', $rec->id);
        Mode::set('wrapper', 'cms_page_External');

        return $tpl;
    }


    /**
     * PDF за купувача
     */
    public function act_Pdf()
    {
        $rec = $this->fetchByPublicKey();
        $this->requireRightFor('pdf', $rec);
        $this->logRead('PDF', $rec->id);

        return new Redirect(fileman_Download::getDownloadUrl(static::getPdf($rec)));
    }


    /**
     * Документът по подписания ключ от публичния адрес
     *
     * @return stdClass
     */
    protected function fetchByPublicKey()
    {
        Request::setProtected('doc');
        $id = Request::get('doc', 'int');
        Request::removeProtected('doc');
        expect($id && ($rec = $this->fetch($id)), 'Няма такъв документ');

        return $rec;
    }


    /**
     * Публичен адрес на документа - отваря се без вход, но не може да се налучка
     *
     * @param stdClass $rec
     * @param string   $action - show или pdf
     *
     * @return string
     */
    public static function getPublicUrl($rec, $action = 'show')
    {
        Request::setProtected('doc');
        $url = toUrl(array(get_called_class(), $action, 'doc' => $rec->id), 'absolute');
        Request::removeProtected('doc');

        return $url;
    }


    /**
     * PDF на документа
     *
     * @param stdClass $rec
     *
     * @return string - манипулатор на файла
     */
    public static function getPdf($rec)
    {
        $name = 'Doc52o_' . static::formatNumber($rec->number) . '.pdf';

        return doc_PdfCreator::convert(static::renderDoc($rec), $name);
    }


    /**
     * Самият документ - еднакъв в системата, публичния изглед и PDF-а
     *
     * @param stdClass $rec
     *
     * @return core_ET
     */
    public static function renderDoc($rec)
    {
        // Документът е по българска наредба - еднакъв за всеки купувач и в PDF-а
        core_Lg::push('bg');
        try {
            $me = cls::get(get_called_class());
            $fields = arr::make('number,issuedOn,orderNumber,transactionRef,paymentMethodId,posId,shopNumber,total,currencyId', true);
            $fields['-single'] = true;
            $row = $me->recToVerbal($rec, $fields);
            $row->issuedOn = dt::mysql2verbal($rec->issuedOn, 'd.m.Y H:i:s');

            $tpl = getTplFromFile('bgfisc/tpl/SaleRegDoc.shtml');
            $tpl->placeObject($row);

            $Double = core_Type::getByName('double(decimals=2)');
            $Quantity = core_Type::getByName('double(smartRound)');
            foreach ((array) $rec->lines as $line) {
                $line = (object) $line;
                $tpl->append('<tr><td class="srd-left">' . core_Type::escape($line->name ?? '') . '</td><td>' . core_Type::escape($line->vatGroup ?? '') . '</td>'
                    . '<td class="srd-right">' . $Quantity->toVerbal($line->quantity ?? 0) . ' ' . cat_UoM::getShortName($line->packagingId ?? null) . '</td>'
                    . '<td class="srd-right">' . $Double->toVerbal($line->price ?? 0) . '</td><td class="srd-right">' . $Double->toVerbal($line->amount ?? 0) . '</td></tr>', 'LINES');
            }
            $tpl->push('bgfisc/tpl/SaleRegDoc.css', 'CSS');
        } finally {
            core_Lg::pop();
        }

        return $tpl;
    }


    /**
     * Права за действията
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Само за поръчка, приета като картово плащане по Н-18 (по запазените тогава данни), с продажба и без документ
        if ($action == 'createforcart' && isset($rec)) {
            $paymentData = (array) ($rec->n18PaymentData ?? array());
            if (empty($paymentData['shopNumber']) || empty($paymentData['paymentCode']) || empty($paymentData['transactionRef']) || empty($rec->saleId) || $mvc->fetchField("#cartId = {$rec->id}", 'id')) {
                $res = 'no_one';
            }
        }
    }


    /**
     * Платената сума във валутата на продажбата
     *
     * @param stdClass $cartRec
     * @param stdClass $saleRec
     * @param array    $paymentData
     * @param array    $lines
     *
     * @return float
     */
    public static function getPaidAmount($cartRec, $saleRec, $paymentData, $lines)
    {
        // Шлюзът знае точно колко е платено
        if (isset($paymentData['amount']) && is_numeric($paymentData['amount'])) {
            $amount = $paymentData['amount'];

            // Шлюзът плаща във валутата на сметката, а документът е във валутата на продажбата
            $currencyCode = $paymentData['currencyCode'] ?? null;
            if (!empty($currencyCode) && !empty($saleRec->currencyId) && $currencyCode != $saleRec->currencyId) {
                $amount = currency_CurrencyRates::convertAmount($amount, !empty($paymentData['paidOn']) ? substr($paymentData['paidOn'], 0, 10) : null, $currencyCode, $saleRec->currencyId);
            }

            return round(round($amount, 10), 2);
        }

        // Сумата в количката е в основна валута - превръща се с курса на продажбата, както редовете
        if (isset($cartRec->total)) {
            $total = !empty($saleRec->currencyRate) ? $cartRec->total / $saleRec->currencyRate : $cartRec->total;

            return round(round($total, 10), 2);
        }

        return round(array_sum(array_column($lines, 'amount')), 2);
    }


    /**
     * Редовете на продажбата с цени и стойности с ДДС, след отстъпките, и данъчната група
     *
     * @param stdClass $saleRec
     *
     * @return array
     */
    public static function prepareLines($saleRec)
    {
        $res = array();
        if (empty($saleRec->id)) {

            return $res;
        }
        $chargeVat = in_array($saleRec->chargeVat ?? null, array('yes', 'separate'));
        $valior = $saleRec->valior ?? null;
        $vatExceptionId = $saleRec->vatExceptionId ?? null;

        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$saleRec->id}");
        $dQuery->orderBy('id');
        while ($dRec = $dQuery->fetch()) {
            $vat = $chargeVat ? cat_Products::getVat($dRec->productId, $valior, $vatExceptionId) : 0;

            // Автоматичната отстъпка се слива с ръчната както в deals_Helper, докато сделката не е активна
            $discount = $dRec->discount ?? 0;
            if (!empty($dRec->autoDiscount) && in_array($saleRec->state ?? null, array('draft', 'pending'))) {
                $discount = 1 - (1 - $discount) * (1 - $dRec->autoDiscount);
            }

            // Цените са в основна валута, както в deals_Helper; ДДС не се закръгля отделно, за да е като платеното в количката
            $noVatPrice = !empty($saleRec->currencyRate) ? $dRec->price / $saleRec->currencyRate : $dRec->price;
            $amount = $noVatPrice * $dRec->quantity * (1 - $discount) * (1 + $vat);

            // Първото закръгляне маха грешката на плаващата запетая (2.2549999 вместо 2.255)
            $amount = round(round($amount, 10), 2);
            $quantity = $dRec->packQuantity ?? 0;

            // Без зададена група артикулът е в Б при начисляване на ДДС и в А без ДДС, както при фискалния бон
            $groupRec = cat_products_VatGroups::getCurrentGroup($dRec->productId, $valior, $vatExceptionId);
            $sysId = $groupRec->sysId ?? ($chargeVat ? 'B' : 'A');
            $vatGroup = acc_VatGroups::fetchField(acc_VatGroups::getIdBySysId($sysId), 'title');

            $res[] = (object) array(
                'name' => cat_Products::getVerbal($dRec->productId, 'name'),
                'packagingId' => $dRec->packagingId,
                'quantity' => $quantity,
                'price' => $quantity ? round(round($amount / $quantity, 10), 2) : 0,
                'vatGroup' => $vatGroup,
                'vat' => $vat,
                'amount' => $amount,
            );
        }

        return $res;
    }


    /**
     * Текстът на QR кода по приложение № 18а - второто поле е празно
     *
     * @param stdClass $rec
     *
     * @return string
     */
    public static function getQrText($rec)
    {
        list($date, $time) = explode(' ', $rec->issuedOn ?? '') + array('', '');

        return implode('*', array($rec->shopNumber ?? '', '', $rec->orderNumber ?? '', $rec->transactionRef ?? '', $date, $time, number_format($rec->total ?? 0, 2, '.', '')));
    }


    /**
     * Номерът във вида от наредбата - 10 цифри с водещи нули
     *
     * @param int $number
     *
     * @return string
     */
    public static function formatNumber($number)
    {
        return str_pad($number, 10, '0', STR_PAD_LEFT);
    }


    /**
     * След преобразуване на записа в четим вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->number = static::formatNumber($rec->number);
        if (isset($fields['-list'])) {
            $row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id));
            if (!empty($rec->ownCompanyId)) {
                $row->ownCompanyId = crm_Companies::getHyperlink($rec->ownCompanyId, true);
            }
            if (!empty($rec->cartId)) {
                $row->orderNumber = eshop_Carts::getHyperlink($rec->cartId, true);
            }
        }

        if ((isset($fields['-list']) || isset($fields['-single'])) && !empty($rec->saleId)) {
            $row->saleId = sales_Sales::getLink($rec->saleId, 0);
        }

        if (isset($fields['-single'])) {
            $company = crm_Companies::fetchOwnCompany($rec->ownCompanyId, $rec->issuedOn);
            $row->companyName = core_Type::escape($company->companyVerb ?? $company->company ?? '');
            $row->companyUicId = core_Type::escape($company->uicId ?? '');
            $row->companyAddress = core_Type::escape(trim(implode(', ', array_filter(array($company->pCode ?? '', $company->place ?? '', $company->address ?? ''))), ', '));
            $row->companyContact = core_Type::escape(implode(', ', array_filter(array($company->email ?? '', $company->tel ?? ''))));

            $qrUrl = barcode_Qr::getUrl(static::getQrText($rec), true, 4);
            $row->qr = ht::createElement('img', array('src' => $qrUrl, 'alt' => 'QR'));
        }
    }


    /**
     * Пълният номер (с водещите нули) също се търси - така е изписан в документа
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $number = !empty($rec->number) ? $rec->number : (isset($rec->id) ? $mvc->fetchField($rec->id, 'number') : null);
        if (!empty($number)) {
            $res .= ' ' . plg_Search::normalizeText(static::formatNumber($number));
        }
    }


    /**
     * Филтър на списъка - търсене, период и наша фирма
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $form->FLD('from', 'date', 'caption=От,silent');
        $form->FLD('to', 'date', 'caption=До,silent');
        $showFields = 'search,selectPeriod,from,to';

        // Само фирмите с документи - номерацията е отделна за всяка
        $cQuery = $mvc->getQuery();
        $cQuery->groupBy('ownCompanyId');
        $cQuery->show('ownCompanyId');
        $companies = array();
        while ($cRec = $cQuery->fetch()) {
            $companies[$cRec->ownCompanyId] = crm_Companies::getTitleById($cRec->ownCompanyId, false);
        }
        if (countR($companies) > 1) {
            $form->FLD('company', 'int', 'caption=Наша фирма,placeholder=Всички,silent');
            $form->setOptions('company', array('' => '') + $companies);
            $showFields .= ',company';
        }

        $form->showFields = $showFields;
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->input(null, 'silent');
        $form->input();

        $filter = $form->rec;
        if (!empty($filter->from)) {
            $data->query->where(array("#issuedOn >= '[#1#]'", "{$filter->from} 00:00:00"));
        }
        if (!empty($filter->to)) {
            $data->query->where(array("#issuedOn <= '[#1#]'", "{$filter->to} 23:59:59"));
        }
        if (!empty($filter->company) && isset($companies[$filter->company])) {
            $data->query->where(array('#ownCompanyId = [#1#]', $filter->company));
        }
        $data->query->orderBy('issuedOn', 'DESC');
    }


    /**
     * Рендиране на единичния изглед - самият документ
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->replace(static::renderDoc($data->rec), 'DOC');
    }


    /**
     * Бутон към публичния изглед, както го вижда купувачът
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn('За купувача', static::getPublicUrl($data->rec), 'ef_icon=img/16/world_link.png,title=Публичният изглед - адресът от имейла за поръчката', array('target' => '_blank'));
    }
}
