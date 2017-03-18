<?php



/**
 * Банкови сметки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Accounts extends core_Master {
    
    
    /**
     * Заглавие
     */
    public $title = 'Всички сметки';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bank_Wrapper, plg_Rejected, plg_Search';
    
    
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
    public $singleTitle = "Банкова сметка";
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/bank.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'iban';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'bank, ceo';
    
    
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
    function description()
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
    	
    	$res = " " . $res . " " . plg_Search::normalizeText($contragentName);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
		$rec = $form->rec;
    	$contragentRec = cls::get($rec->contragentCls)->fetch($rec->contragentId);
    	
    	if(!$rec->id) {
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
                    $form->setWarning('bank', "|*<b>|Банка|*:</b> |въвели сте |*\"<b>|{$form->rec->bank}|*</b>\", |а IBAN-ът е на банка |*\"<b>|{$bank}|*</b>\". |Сигурни ли сте, че искате да продължите?");
                }
            }
            
            $bic = bglocal_Banks::getBankBic($form->rec->iban);
            
            if(!$form->rec->bic){
                $form->rec->bic = $bic;
            } else {
                if($bank && $form->rec->bic != $bic){
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
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->contragent = cls::get($rec->contragentCls)->getHyperLink($rec->contragentId, TRUE);
        
        if($rec->iban) {
        	$verbalIban = $mvc->getVerbal($rec, 'iban');
        	if(strpos($rec->iban, '#') === FALSE){
        			
        		$countryCode = iban_Type::getCountryPart($rec->iban);
        		if ($countryCode) {
        			$hint = 'Държава|*: ' . drdata_Countries::getCountryName($countryCode, core_Lg::getCurrent());
        				
        			if(isset($fields['-single'])){
        				$row->iban = ht::createHint($row->iban, $hint);
        			} else {
        				$singleUrl = $mvc->getSingleUrlArray($rec->id);
        				$row->iban = ht::createLink($verbalIban, $singleUrl, NULL, "ef_icon={$mvc->getIcon($rec->id)},title={$hint}");
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
        expect($data->contragentCls = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        $data->isOurCompany = FALSE;
        $ourCompany = crm_Companies::fetchOurCompany();
        if($data->contragentCls == crm_Companies::getClassId() && $data->masterId == $ourCompany->id){
        	$data->isOurCompany = TRUE;
        }
        
        while($rec = $query->fetch()) {
        	
        	// Ако е наша банкова сметка и е отттеглена, пропускаме я
        	if($data->isOurCompany === TRUE){
        		$rec->ourAccount = TRUE;
        		$state = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'state');
        		if($state == 'rejected') continue;
        	}
        	
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
            
            // Ако сметката е на нашата фирма, подменяме линка да сочи към изгледа на нашата сметка
            if($data->isOurCompany === TRUE){
            	$iban = $this->getVerbal($rec, 'iban');
            	$aId = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'id');
            	if(bank_OwnAccounts::haveRightFor('single', $aId)){
            		$row->iban = ht::createLink($iban, array('bank_OwnAccounts', 'single', $aId), FALSE, 'title=Към нашата банкова сметка,ef_icon=img/16/own-bank.png');
            	}
            }
        }
        
        $data->TabCaption = 'Банка';
    }
    
    
    /**
     * Рендира данните на банковите сметки за даден контрагент
     */
    public function renderContragentBankAccounts($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Банкови сметки'), 'title');
        
        if(count($data->rows)) {
            foreach($data->rows as $id => $row) {
            	core_RowToolbar::createIfNotExists($row->_rowTools);
                $rec = $data->recs[$id];
                
                $cCodeRec = currency_Currencies::fetch($rec->currencyId);
                $cCode = currency_Currencies::getVerbal($cCodeRec, 'code');
                
                $row->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px;
                font-size:0.7em;vertical-align:middle;'>{$cCode}</span>&nbsp;";
                
                $row->title .= $row->iban;
                
                if($rec->bank) {
                    $row->title .= ", {$row->bank}";
                }
                
                $row->title = core_ET::escape($row->title);
               
                $tpl->append("<div style='padding:3px;white-space:normal;font-size:0.9em;'>", 'content');
                $tools = new core_ET("{$row->title} <span style='position:relative;top:4px'>[#tools#]</span>");
                $tools->replace($row->_rowTools->renderHtml(), 'tools');
               
                $tpl->append($tools, 'content');
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма банкови сметки"), 'content');
        }
        
        if(!Mode::is('printing')) {
            if($data->masterMvc->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')) {
                $img = "<img src=" . sbf('img/16/add.png') . " width='16'  height='16'>";
            	
                // Ако контрагента е 'моята фирма' редирект към създаване на наша сметка, иначе към създаване на обикновена
            	if($data->isOurCompany === TRUE){
            		$url = array('bank_OwnAccounts', 'add', 'ret_url' => TRUE, 'fromOurCompany' => TRUE);
            		$title = 'Добавяне на нова наша банкова сметка';
            	} else {
            		$url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
            		$title = 'Добавяне на нова банкова сметка';
            	}
            	
            	$tpl->append(ht::createLink($img, $url, FALSE, 'title=' . $title), 'title');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Реализация по подразбиране на метода getEditUrl()
     *
     * @param core_Mvc $mvc
     * @param array $editUrl
     * @param stdClass $rec
     */
    protected static function on_BeforeGetEditUrl($mvc, &$editUrl, $rec)
    {
    	if($rec->ourAccount === TRUE){
    		$retUrl = $editUrl['ret_url'];
    		$ownAccountId = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'id');
    		$editUrl = array('bank_OwnAccounts', 'edit', $ownAccountId, 'fromOurCompany' => TRUE);
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
    public static function getRecTitle($rec, $escaped = TRUE)
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
    public static function getContragentIbans($contragentId, $contragentClass, $intKeys = FALSE)
    {
        $Contragent = cls::get($contragentClass);
        $suggestions = array('' => '');
        
        $query = static::getQuery();
        $query->where("#contragentId = {$contragentId}");
        $query->where("#contragentCls = {$Contragent->getClassId()}");
        
        $myCompany = crm_Companies::fetchOwnCompany();
        $isOurCompany = ($myCompany->companyId == $contragentId && $Contragent->getClassId() == crm_Companies::getClassId()) ? TRUE : FALSE;
        
        while($rec = $query->fetch()) {
        	
        	// Ако е наша банкова сметка и е отттеглена, пропускаме я
        	if($isOurCompany === TRUE){
        		$state = bank_OwnAccounts::fetchField("#bankAccountId = {$rec->id}", 'state');
        		if($state == 'rejected') continue;
        	}
        	
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
