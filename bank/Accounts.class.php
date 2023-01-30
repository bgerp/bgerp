<?php


/**
 * Банкови сметки
 *
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bank_Accounts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Всички сметки';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, plg_Rejected, plg_Search, plg_Sorting, doc_plg_Close, deals_plg_AdditionalConditions';


    /**
     * Полета за допълнителни условие към документи
     * @see deals_plg_AdditionalConditions
     */
    public $additionalConditionsToDocuments = 'sales_Sales,purchase_Purchases';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'iban,bic,bank';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'iban, currencyId, contragent=Контрагент';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Банкова сметка';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/bank.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'iban';


    /**
     * Кой може да затваря?
     */
    public $canClose = 'bank,ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'bank, ceo';
    
    
    /**
     * Файл за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleAccountLayout.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('contragentCls', 'class', 'caption=Контрагент->Клас,mandatory,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Контрагент->Обект,mandatory,input=hidden,silent');
        
        // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('iban', 'iban_Type(64)', 'caption=IBAN / №,mandatory,removeAndRefreshForm=bic|bank,silent');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
        $this->FLD('bic', 'varchar(12)', 'caption=BIC');
        $this->FLD('bank', 'varchar(64)', 'caption=Банка');
        $this->FLD('comment', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');

        // Задаваме индексите и уникалните полета за модела
        $this->setDbIndex('contragentCls,contragentId');
        $this->setDbUnique('iban');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $contragentName = cls::get($rec->contragentCls)->getTitleById($rec->contragentId);
        
        $res = ' ' . $res . ' ' . plg_Search::normalizeText($contragentName);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        if ($iban = Request::get('iban')) {
            $data->form->setDefault('iban', $iban);
        }
        
        // Ако има въведен iban
        if (isset($rec->iban)) {
            
            // и той е валиден
            if (!$data->form->gotErrors()) {
                
                // по дефолт извличаме името на банката и bic-а ако можем
                $data->form->setDefault('bank', bglocal_Banks::getBankName($rec->iban));
                $data->form->setDefault('bic', bglocal_Banks::getBankBic($rec->iban));
            }
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle($rec->contragentCls, $rec->contragentId, $mvc->singleTitle, $rec->id, 'на');
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'close'|| $action == 'edit' || $action == 'delete')) {
            if(isset($rec->contragentCls)){
                $cState = cls::get($rec->contragentCls)->fetchField($rec->contragentId, 'state');
                if (in_array($cState, array('closed', 'rejected'))) {
                    $requiredRoles = 'no_one';
                }
            }
        }

        if($action == 'close' && isset($rec)){
            if($ownAccountRec = bank_OwnAccounts::fetch("#bankAccountId = {$rec->id}")){
                if (in_array($ownAccountRec->state, array('closed', 'rejected'))) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        $contragentRec = cls::get($rec->contragentCls)->fetch($rec->contragentId);
        
        if (!$rec->id) {
            // По подразбиране, валутата е тази, която е в обръщение в страната на контрагента
            if ($contragentRec->country) {
                $countryRec = drdata_Countries::fetch($contragentRec->country);
                $cCode = $countryRec->currencyCode;
                $form->setDefault('currencyId', currency_Currencies::fetchField("#code = '{$cCode}'", 'id'));
            } else {
                // По дефолт е основната валута в системата
                $conf = core_Packs::getConfig('acc');
                $defaultCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
                $form->setDefault('currencyId', $defaultCurrencyId);
            }
        }
        
        // Ако формата е събмитната, и банката и бика не са попълнени,
        // то ги извличаме от IBAN-a , ако са попълнени изкарваме преудреждение
        // ако те се разминават с тези в системата
        if ($form->isSubmitted()) {
            if ($form->rec->iban[0] != '#') {
                $bank = bglocal_Banks::getBankName($form->rec->iban);
            }
            
            if (!$form->rec->bank) {
                $form->rec->bank = $bank;
            } else {

                if (trim($bank) && (trim(mb_strtolower($form->rec->bank)) != trim(mb_strtolower($bank)))) {
                    $form->setWarning('bank', "|*<b>|Банка|*:</b> |въвели сте |*\"<b>|{$form->rec->bank}|*</b>\", |а IBAN-ът е на банка |*\"<b>|{$bank}|*</b>\". |Сигурни ли сте, че искате да продължите?");
                }
            }
            
            $bic = bglocal_Banks::getBankBic($form->rec->iban);
            
            if (!$form->rec->bic) {
                $form->rec->bic = $bic;
            } else {
                if ($bank && $form->rec->bic != $bic) {
                    $form->setWarning('bic', "|*<b>BIC:</b> |въвели сте |*\"<b>{$form->rec->bic}</b>\", |а IBAN-ът е на BIC |*\"<b>{$bic}</b>\". |Сигурни ли сте, че искате да продължите?");
                }
            }
        }
    }
    
    
    /**
     * Връща иконата за сметката
     */
    public function getIcon($id)
    {
        $rec = $this->fetch($id);
        
        $ourCompanyRec = crm_Companies::fetchOurCompany();
        
        if ($rec->contragentId == $ourCompanyRec->id && $rec->contragentCls == $ourCompanyRec->classId) {
            $ownBA = cls::get('bank_OwnAccounts');
            $icon = $ownBA->singleIcon;
        } else {
            $icon = $this->singleIcon;
        }
        
        return $icon;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->contragent = cls::get($rec->contragentCls)->getHyperLink($rec->contragentId, true);
        $row->STATE = ($rec->state == 'rejected') ? 'rejected' : (($rec->state == 'closed') ? 'closed' : 'active');
        $row->ROW_ATTR['class'] = "state-{$row->STATE}";
        
        if ($rec->iban) {
            $verbalIban = $mvc->getVerbal($rec, 'iban');
            if (strpos($rec->iban, '#') === false) {
                $countryCode = iban_Type::getCountryPart($rec->iban);
                if ($countryCode) {
                    $hint = 'Държава|*: ' . drdata_Countries::getCountryName($countryCode, core_Lg::getCurrent());
                    if (isset($fields['-single'])) {
                        $row->iban = ht::createHint($row->iban, $hint);
                    } else {
                        $singleUrl = $mvc->getSingleUrlArray($rec->id);
                        $row->iban = ht::createLink($verbalIban, $singleUrl, null, "ef_icon={$mvc->getIcon($rec->id)},title={$hint}");
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготвя данните необходими за рендиране на банковите сметки за даден контрагент
     */
    public function prepareContragentBankAccounts($data)
    {
        $data->TabCaption = 'Банка';
        
        if (!$data->isCurrent) {
            
            return;
        }
        
        expect($data->contragentCls = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        $data->isOurCompany = false;
        $ourCompany = crm_Companies::fetchOurCompany();
        if ($data->contragentCls == crm_Companies::getClassId() && $data->masterId == $ourCompany->id) {
            $data->isOurCompany = true;
        }
        
        while ($rec = $query->fetch()) {

            // Ако е наша банкова сметка и е отттеглена, пропускаме я
            if ($data->isOurCompany === true) {
                $rec->ourAccount = true;
                $state = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'state');
                if ($state == 'rejected') continue;
            }
            
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
            
            // Ако сметката е на нашата фирма, подменяме линка да сочи към изгледа на нашата сметка
            if ($data->isOurCompany === true) {
                $iban = $this->getVerbal($rec, 'iban');
                $aId = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'id');
                if (bank_OwnAccounts::haveRightFor('single', $aId)) {
                    $row->iban = ht::createLink($iban, array('bank_OwnAccounts', 'single', $aId), false, 'title=Към нашата банкова сметка,ef_icon=img/16/own-bank.png');
                }
            }
        }
    }
    
    
    /**
     * Рендира данните на банковите сметки за даден контрагент
     */
    public function renderContragentBankAccounts($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Банкови сметки'), 'title');
        
        if (countR($data->rows)) {
            foreach ($data->rows as $id => $row) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $rec = $data->recs[$id];
                
                $cCodeRec = currency_Currencies::fetch($rec->currencyId);
                $cCode = currency_Currencies::getVerbal($cCodeRec, 'code');
                
                $row->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px;font-size:0.7em;vertical-align:middle;'>{$cCode}</span>&nbsp;";
                if($rec->state == 'closed'){
                    $row->iban = ht::createElement('span', array('class' => 'warning-balloon state-closed', 'title' => tr('Сметката е закрита')), $row->iban);
                }

                $row->title .= $row->iban;
                if ($rec->bank) {
                    $row->title .= ", {$row->bank}";
                }
                
                $row->title = core_ET::escape($row->title);


                $tpl->append("<div style='padding:3px;white-space:normal;font-size:0.9em;'>", 'content');
                $tools = new core_ET("{$row->title} <span style='position:relative;top:4px'>[#tools#]</span>");
                $tools->replace($row->_rowTools->renderHtml(), 'tools');
                
                $tpl->append($tools, 'content');
                $tpl->append('</div>', 'content');
            }
        } else {
            $tpl->append(tr('Все още няма банкови сметки'), 'content');
        }
        
        if (!Mode::is('printing')) {
            if ($data->masterMvc->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')) {
                $img = '<img src=' . sbf('img/16/add.png') . " width='16'  height='16'>";
                
                // Ако контрагента е 'моята фирма' редирект към създаване на наша сметка, иначе към създаване на обикновена
                if ($data->isOurCompany === true) {
                    $url = array('bank_OwnAccounts', 'add', 'ret_url' => true, 'fromOurCompany' => true);
                    $title = 'Добавяне на нова наша банкова сметка';
                } else {
                    $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => true);
                    $title = 'Добавяне на нова банкова сметка';
                }
                
                $tpl->append(ht::createLink($img, $url, false, 'title=' . $title), 'title');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     *
     * @param core_Mvc $mvc
     * @param array    $editUrl
     * @param stdClass $rec
     */
    protected static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
        if ($rec->ourAccount === true) {
            $retUrl = $editUrl['ret_url'];
            $ownAccountId = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'id');
            $editUrl = array('bank_OwnAccounts', 'edit', $ownAccountId, 'fromOurCompany' => true);
            $editUrl['ret_url'] = $retUrl;
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Банкови сметки немогат да се добавят от мениджъра bank_Accounts
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $title = iban_Type::removeDs($rec->iban);
        
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Връща банковите сметки на даден контрагент
     *
     * @param int   $contragentId    - контрагент
     * @param mixed $contragentClass - класа на контрагента
     * @param int   $intKeys         - дали ключовете да са инт
     *
     * @return array $suggestions - Масив от сметките на клиента
     */
    public static function getContragentIbans($contragentId, $contragentClass, $intKeys = false)
    {
        $Contragent = cls::get($contragentClass);
        $suggestions = array('' => '');
        
        $query = static::getQuery();
        $query->where("#contragentId = {$contragentId}");
        $query->where("#contragentCls = {$Contragent->getClassId()}");
        $query->where("#state != 'closed'");

        $myCompany = crm_Companies::fetchOwnCompany();
        $isOurCompany = ($myCompany->companyId == $contragentId && $Contragent->getClassId() == crm_Companies::getClassId());
        $cu = core_Users::getCurrent();

        while ($rec = $query->fetch()) {
            
            // Ако е наша банкова сметка и е отттеглена, пропускаме я
            if ($isOurCompany === true) {
                $ownRec = bank_OwnAccounts::fetch("#bankAccountId = {$rec->id}");
                if(is_object($ownRec)){
                    if(in_array($ownRec->state, array('closed', 'rejected'))) continue;
                    if (!bgerp_plg_FLB::canUse('bank_OwnAccounts', $ownRec, $cu, 'select')) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            
            $iban = $rec->iban;
            $key = ($intKeys) ? $rec->id : $rec->iban;
            $suggestions[$key] = $iban;
        }

        return $suggestions;
    }
    
    
    /**
     * Добавя нова банкова сметка на контрагента
     *
     * @param iban_Type $iban            - iban
     * @param int       $currencyId      - ид на валута
     * @param int       $contragentClsId - класа на контрагента
     * @param int       $contragentId    - ид на контрагента
     *
     * @throws core_exception_Expect
     * @return int|null
     */
    public static function add($iban, $currencyId, $contragentClsId, $contragentId)
    {
        expect(cls::get($contragentClsId)->fetch($contragentId));
        $IbanType = cls::get('iban_Type');
        expect($IbanType->fromVerbal($iban));

        if (static::fetch(array("#iban = '[#1#]'", $iban)))  return null;

        $rec = (object) array('iban'          => $iban,
                              'contragentCls' => $contragentClsId,
                              'contragentId'  => $contragentId,
                              'currencyId'    => $currencyId,
                              'bank'          => bglocal_Banks::getBankName($iban),
                              'bic'           => bglocal_Banks::getBankBic($iban));

        return bank_Accounts::save($rec);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->setField('contragentCls', 'input=none');
        $data->listFilter->setField('contragentId', 'input=none');
        $data->listFilter->setFieldTypeParams('currencyId', array('allowEmpty' => 'allowEmpty'));
        $data->listFilter->showFields = 'search,currencyId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('currencyId');

        if ($data->listFilter->isSubmitted()) {
            if(!empty($data->listFilter->rec->currencyId)){
                $data->query->where("#currencyId = {$data->listFilter->rec->currencyId}");
            }
        }
    }


    /**
     * Декорира ибан-а в удобен за показване вид
     *
     * @param string $iban
     * @return core_ET
     */
    public static function decorateIban($iban)
    {
        $res = core_Type::getByName('iban_Type(64)')->toVerbal($iban);
        if(!Mode::isReadOnly()){
            if($bRec = bank_Accounts::fetch(array("#iban = '[#1#]'", $iban))){
                if(bank_Accounts::haveRightFor('single', $bRec)){
                    $url = bank_Accounts::getSingleUrlArray($bRec->id);
                    $res = ht::createLink($res, $url);
                }
                if($bRec->state == 'closed'){
                    $res = ht::createElement('span', array('class' => 'warning-balloon state-closed', 'title' => 'Сметката е закрита'), $res);
                }
            }
        }

        return $res;
    }
}
