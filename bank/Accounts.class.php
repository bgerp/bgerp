<?php



/**
 * Банкови сметки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Accounts extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Всички сметки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, plg_Rejected, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'iban,bic,bank';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'tools=Пулт, iban, contragent=Контрагент, currencyId';
    
    
    /**
     * Поле за показване на пулта за редакция
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Банкова сметка";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/bank.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'iban';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'bank,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Файл за единичен изглед
     */
    var $singleLayoutFile = 'bank/tpl/SingleAccountLayout.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class', 'caption=Контрагент->Клас,mandatory,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Контрагент->Обект,mandatory,input=hidden,silent');
        
        // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('iban', 'iban_Type(64)', 'caption=IBAN / №,mandatory,removeAndRefreshForm=bic|bank|currencyId,silent');
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
    	
    	$res = " " . $res . " " . plg_Search::normalizeText($contragentName);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        $contragentRec   = $Contragents->fetch($rec->contragentId);
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);
        
        if($rec->id) {
            $data->form->title = 'Редактиране на банкова сметка на |*<b>' . $contragentTitle . "</b>";
        } else {
            // По подразбиране, валутата е тази, която е в обръщение в страната на контрагента
            if ($contragentRec->country) {
                $countryRec = drdata_Countries::fetch($contragentRec->country);
                $cCode = $countryRec->currencyCode;
                $data->form->setDefault('currencyId', currency_Currencies::fetchField("#code = '{$cCode}'", 'id'));
            } else {
                // По дефолт е основната валута в системата
                $conf = core_Packs::getConfig('acc');
                $defaultCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
                $data->form->setDefault('currencyId', $defaultCurrencyId);
            }
            
            $data->form->title = 'Нова банкова сметка на |*<b>' . $contragentTitle . "</b>";
        }
        
        if($iban = Request::get('iban')) {
            $data->form->setDefault('iban', $iban);
        }
        
        // Ако има въведен iban
        if(isset($rec->iban)){
        	
        	// и той е валиден
        	if(!$data->form->gotErrors()){
        		
        		// по дефолт извличаме името на банката и bic-а ако можем
        		$data->form->setDefault('bank', bglocal_Banks::getBankName($rec->iban));
        		$data->form->setDefault('bic', bglocal_Banks::getBankBic($rec->iban));
        	}
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if (($action == 'edit' || $action == 'delete') && isset($rec->contragentCls)) {
            $productState = cls::get($rec->contragentCls)->fetchField($rec->contragentId, 'state');
            
            if ($productState == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
        
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // ако формата е събмитната, и банката и бика не са попълнени,  
        // то ги извличаме от IBAN-a , ако са попълнени изкарваме преудреждение 
        // ако те се разминават с тези в системата
        if($form->isSubmitted()){
            if($form->rec->iban{0} != '#') {
                $bank = bglocal_Banks::getBankName($form->rec->iban);
            }
            
            if(!$form->rec->bank){
                $form->rec->bank = $bank;
            } else {
                if($bank && $form->rec->bank != $bank){
                    $form->setWarning('bank', "|*<b>|Банка|*:</b> |въвели сте |*\"<b>|{$form->rec->bank}|*</b>\", |а IBAN-ът е на банка |*\"<b>|{$bank}|*</b>\". |Сигурни ли сте че искате да продължите?");
                }
            }
            
            $bic = bglocal_Banks::getBankBic($form->rec->iban);
            
            if(!$form->rec->bic){
                $form->rec->bic = $bic;
            } else {
                if($bank && $form->rec->bic != $bic){
                    $form->setWarning('bic', "|*<b>BIC:</b> |въвели сте |*\"<b>{$form->rec->bic}</b>\", |а IBAN-ът е на BIC |*\"<b>{$bic}</b>\". |Сигурни ли сте че искате да продължите?");
                }
            }
        }
    }
 

    /**
     * Връща иконата за сметката
     */
    function getIcon($id)
    {
        $rec = $this->fetch($id);
        
        $ourCompanyRec = crm_Companies::fetchOurCompany();
        
        if($rec->contragentId == $ourCompanyRec->id && $rec->contragentCls == $ourCompanyRec->classId) {
            $ownBA = cls::get('bank_OwnAccounts');
            $icon =  $ownBA->singleIcon;
        } else {
            $icon =  $this->singleIcon;
        }
        
        return $icon;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $cMvc = cls::get($rec->contragentCls);
        $field = $cMvc->rowToolsSingleField;
        $cRec = $cMvc->fetch($rec->contragentId);
        $cRow = $cMvc->recToVerbal($cRec, "-list,{$field}");
        $row->contragent = $cRow->{$field};
    }
    
    
    /**
     * Подготвя данните необходими за рендиране на банковите сметки за даден контрагент
     */
    function prepareContragentBankAccounts($data)
    {
        expect($data->contragentCls = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
        
        $data->TabCaption = 'Банка';
    }
    
    
    /**
     * Рендира данните на банковите сметки за даден контрагент
     */
    function renderContragentBankAccounts($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Банкови сметки'), 'title');
        
        if(count($data->rows)) {
            
            foreach($data->rows as $id => $row) {
                
                $rec = $data->recs[$id];
                
                $cCodeRec = currency_Currencies::fetch($rec->currencyId);
                $cCode = currency_Currencies::getVerbal($cCodeRec, 'code');
                
                $row->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px;
                font-size:0.7em;vertical-align:middle;'>{$cCode}</span>&nbsp;";
                
                $row->title .= $row->iban;
                
                if($rec->bank) {
                    $row->title .= ", {$row->bank}";
                }
                
                $tpl->append("<div style='padding:3px;white-space:normal;font-size:0.9em;'>", 'content');
                
                $tpl->append("{$row->title} {$row->tools}", 'content');
                
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма банкови сметки"), 'content');
        }
        
        if(!Mode::is('printing')) {
            if($data->masterMvc->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')) {
                $ourCompany = crm_Companies::fetchOurCompany();
                $img = "<img src=" . sbf('img/16/add.png') . " width='16'  height='16'>";
            	
                // Ако контрагента е 'моята фирма' редирект към създаване на наша сметка, иначе към създаване на обикновена
            	if($data->contragentCls == crm_Companies::getClassId() && $data->masterId == $ourCompany->id){
            		$url = array('bank_OwnAccounts', 'add', 'ret_url' => TRUE);
            		$title = 'Добавяне на нова наша банкова сметка';
            	} else {
            		$url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
            		$title = 'Добавяне на нова банкова сметка';
            	}
            	
            	$tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr($title)), 'title');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Банкови сметки немогат да се добавят от мениджъра bank_Accounts
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = iban_Type::removeDs($rec->iban);
        
        if($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Връща банковите сметки на даден контрагент
     *
     * @param int $contragentId - контрагент
     * @param mixed $contragentClass - класа на контрагента
     * @param int $intKeys - дали ключовете да са инт
     * @return array $suggestions - Масив от сметките на клиента
     */
    static function getContragentIbans($contragentId, $contragentClass, $intKeys = FALSE)
    {
        $Contragent = cls::get($contragentClass);
        $suggestions = array('' => '');
        
        $query = static::getQuery();
        $query->where("#contragentId = {$contragentId}");
        $query->where("#contragentCls = {$Contragent->getClassId()}");
        
        while($rec = $query->fetch()) {
            $iban = $rec->iban;
            $key = ($intKeys) ? $rec->id : $rec->iban;
            $suggestions[$key] = $iban;
        }
        
        return $suggestions;
    }
    
    
    /**
     * Добавя нова банкова сметка
     *
     * @param iban_Type $iban - iban
     * @param int $currency - валута
     * @param int $contragentClsId - класа на контрагента
     * @param int $contragentId - ид на контрагента
     */
    public static function add($iban, $currency, $contragentClsId, $contragentId)
    {
        expect(cls::get($contragentClsId)->fetch($contragentId));
        $IbanType = cls::get('iban_Type');
        expect($IbanType->fromVerbal($iban));
        
        if(!static::fetch(array("#iban = '[#1#]'", $iban))){
            bank_Accounts::save((object)array('iban'          => $iban,
                    'contragentCls' => $contragentClsId,
                    'contragentId'  => $contragentId,
                    'currencyId'    => $currency,
                    'bank'          => bglocal_Banks::getBankName($iban),
                    'bic'           => bglocal_Banks::getBankBic($iban)));
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->setField('contragentCls', 'input=none');
    	$data->listFilter->setField('contragentId', 'input=none');
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
}
