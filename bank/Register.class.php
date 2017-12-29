<?php



/**
 * Регистър за импортиране на банкови плащания
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Register extends core_Manager
{
	

    public $title = "Регистър за банковите транзакции";

	/**
	 * Неща, подлежащи на начално зареждане
	 */
	public $loadList = 'bank_Wrapper, plg_State, plg_Created, plg_Modified, plg_Search,plg_GroupByField,plg_RowTools2,import2_Plugin';
	

    /**
     * Интерфейс на драйверите за импортиране
     */
    public $importInterface = 'bank_ImportTransactionsIntf';


    /**
     * По кое поле да се направи групиране
     */
	public $groupByField = 'valiorAndIban';


	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = "valiorAndIban, amount, contragentName=Контрагент, info=Осчетоводяване";
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'bank, ceo';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'bank, ceo';
	
	
	/**
     * Кой може да създава?
     */
    public $canAdd = 'bank, ceo';
        
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'bank, ceo';
	
	
	/**
	 * Кой може да го контира?
	 */
	public $canConto = 'bank, ceo';
		
	
	
	/**
	 * Добавяне на дефолтни полета
	 *
	 * @param core_Mvc $mvc
	 * @return void
	 */
	function description()
	{
		$this->FLD('serviceId', 'varchar(32)', 'caption=Услуга');
        $this->FLD('transactionId', 'varchar(32)', 'caption=Услуга');

		$this->FLD('type', 'enum(incoming=Входящ,outgoing=Изходящ)', 'caption=Вид');
		$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума');
		$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор');		
        $this->FLD('ownAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)', 'caption=Наша сметка');
		$this->FLD('reason', 'varchar', 'caption=Основание');

		$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Име');
		$this->FLD('contragentIban', 'varchar(255)', 'caption=Контрагент->Сметка');

		$this->FLD('matches', 'blob(compress,serialize)', 'caption=Съответстия,input=none,oldFieldName=accounting');
 		
        $this->FLD('state', 'enum(waiting=Чакащ, active=Активиран, rejected=Оттеглен)', 'caption=Състояние');

        $this->FNC('valiorAndIban', 'varchar', 'captin=Дата и IBAN');

        $this->setDbUnique('transactionId');
	}


    /**
     * Поддръжка на функционално поле
     */
    function on_CalcValiorAndIban($mvc, $rec)
    {
        $rec->valiorAndIban = $rec->valior . '|' . $rec->ownAccountId;
    }


    /**
     * Вербализира групата
     */
    public function renderGroupName($data, $groupId, $groupVerbal)
    {
        list($valior, $ownBankAccId) = explode('|', $groupId);

        $valior = dt::mysql2verbal($valior, 'd/m/Y');

        $ownBankAcc = bank_OwnAccounts::getTitleById($ownBankAccId);

        $res = "<h3>{$valior}, {$ownBankAcc}</h3>";

        return $res;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(!empty($rec->contragentIban)) {
            $row->contragentName .= ($row->contragentName ? "<br>" : '') . $mvc->getVerbal($rec, 'contragentIban');
        }
        if(!empty($rec->reason)) {
            $row->contragentName .= ($row->contragentName ? "<br>" : '') . '<small>' . $mvc->getVerbal($rec, 'reason') . '<small>';
        }

        $row->amount .= '<br><small>' . mb_strtolower($mvc->getVerbal($rec, 'type')) . '</small>';

        if($rec->type == 'outgoing') {
            $color = '#800';
        } else {
            $color = '#008';
        }


        if($folderId = $rec->matches['folderId']) {
            $params = array();
            $params['folderId'] = $folderId;
            $folder = doc_Folders::getVerbalLink($params);
           

            $folder = $folder->getContent();
            $row->info = $folder;
        }

        if(is_array($rec->matches['rows'])) {
            foreach($rec->matches['rows'] as $r) {
                $t .= "\n<tr>";
                $parts = array('head', 'prof', 'inv', 'bdoc');

                foreach($parts as $part) {
                    // Проформите
                    $t .= "<td>";
                    $first = TRUE;
                    if(is_array($r->{$part})) {
                        foreach($r->{$part} as $p) {
                            $link = ht::createLink($p->documentMvc->abbr . $p->number, 
                                array(  $p->documentMvc, 'Single', $p->documentId, 'ret_url' => TRUE),
                                NULL,
                                array('title' => $p->documentMvc->singleTitle .' / ' . $p->amount)) ;
                            $t .= $first ? "" : "<br>";
                            $t .= $link->getContent();
                            $first = FALSE;
                        }
                    }
                    if($part == 'bdoc') {
                        $mvc = ($rec->type == 'incoming') ? 'bank_IncomeDocuments' : 'bank_PaymentOrders';
                        $url = array($mvc, 'add', 'originId' => $r->containerId, 'ret_url' => TRUE);
                        $link = ht::createLink('+',  $url) ;
                        $t .= $first ? "" : "<br>";
                        $t .= $link->getContent();
                        $first = FALSE;
                    }
                    $t .=  "</td>";
                }
                $t .= "</tr>";
            }

            $row->info .= "<table style='font-size:0.8em' class='listTable'>" . $t . "</table>";
        }

        $row->ROW_ATTR['style'] .= "color:{$color};";
    }


    /**
     * Импортира подготвени редове в модела
     */
    public static function importRecs($recs, $serviceId = NULL)
    {
        $usedIds = array();
        $ins = $skip = 0;
 
        foreach($recs as $rec) {
            if($serviceId) {
                $rec->serviceId = $serviceId;
            }
            $rec->state = 'waiting';
            $ind = 0;
            do {
                $rec->transactionId = md5("{$rec->valior}|{$rec->type}|{$rec->amount}|{$rec->contragentIban}|{$rec->ownAccountId}|{$ind}");
                $ind++;
            } while($usedIds[$rec->transactionId]);
            
            $usedIds[$rec->transactionId] = TRUE;
            
            if(self::fetch("#transactionId = '{$rec->transactionId}'")) {
                $skip++;
            } else {
                self::save($rec, NULL, 'IGNORE');
                $ins++;
            }
        }
        
        $status = "Импортирани {$ins} трансакции, пропуснати {$skip}";
        
        return $status;
    }


    /**
     * Намира съответствията на документи и папки и ги записва в полето `matches`
     */
    public static function findMatches($ids = NULL)
    {
        $folders   = self::getFolders();
        $documents = self::getDocuments();

        $query = self::getQuery();
        
        $timeline = dt::addSecs(-5 * 24 * 60 * 60);
        
        if(is_array($ids) && count($ids)) {
            $ids = implode(',', $ids);
            $query->where("#id IN ({$ids})");

        } else {
            $query->where("#state = 'waiting' AND #modifiedOn > '$timeLine'");
        }

        while($rec = $query->fetch()) {
            $cnt++;

            // Вадим номерата от основанието
            $matches = array();
            preg_match_all("/([1-9][0-9]+)/", $rec->reason, $matches);
            if(count($matches[0])) {
                $numbers = array_flip($matches[0]);
            } else {
                $numbers = array();
            }

            $rec->matches = array();
            
            $ourAcc =  bank_OwnAccounts::fetch($rec->ownAccountId);

            // Намираме папката на контрагента по ИБАН-а
            if($i = $rec->contragentIban) {
                $i = strtoupper(preg_replace("/[^a-z0-9]/i", '', $i)); 
                $bAcc = bank_Accounts::fetch(array("#iban = '[#1#]'", $i));
                if(!$bAcc) {
                    $bAcc = bank_Accounts::fetch(array("#iban = '#[#1#]'", $i));
                }
                if($bAcc) {
                    $rec->matches['bAcc'] = $bAcc;
                    // Ако е наша сметката
                    if($ibanAcc = bank_OwnAccounts::fetch("#bankAccountId = {$bAcc->id}")) {
                        if($rec->type == 'outgoing') {
                            $rec->matches['folderId'] = $ibanAcc->folderId;
                            $toSave = TRUE;
                        } else {
                            $rec->matches['folderId'] = $ourAcc->folderId;
                            $toSave = TRUE;
                        }
                    } else {
                        $rec->matches['folderId'] = doc_Folders::getIdByCover($bAcc->contragentCls, $bAcc->contragentId);
                    }
                }
            }

            // Намираме папката по името на контрагента
            if(empty($rec->matches['folderId']) && ($contragent = $rec->contragentName)) {  
                $contragent = trim(strtolower(preg_replace("/[^a-z0-9]+/i", ' ', self::transliterate($contragent))));
 
                if($folderId = $folders[$contragent]) {
                    $rec->matches['folderId'] = $folderId;
                }
            }

            foreach($documents as $d) {

                if(($rec->id == 37) && ($d->folderId == 6855)) {
                    $debug[] = $d;
                }

                // Ако имаме папка, прескачаме документите, които не са в нея
                if(isset($rec->matches['folderId']) && $rec->matches['folderId'] != $d->folderId) continue;
                
                // Ако валутата на документа не съвпада с тази на трансакцията - прескачаме
                if($d->currencyId != $rec->currencyId && $d->currencyId2 != $rec->currencyId) continue;
                
                $p = 0;
                
                // Номер на документа
                if(strlen($d->number) && stripos($rec->reason, $d->number) !== FALSE) {
                    $p += max(0, 1 - 2.5/strlen($d->number));
                    if($numbers[$d->number]) {
                        $p += max(0, 1 - 1.5/strlen($d->number));
                    }
                }

                // Сумата на документа
                $delta = abs($d->amount - $rec->amount) / max($d->amount, $rec->amount);
                if($delta < 0.001) {
                    $p += 0.31;
                } elseif($delta < 0.03) {
                    $p += 0.11;
                }
                
                // Дата на докуемнта
                if($d->date) {
                    $delta = abs(dt::secsBetween($d->date, $rec->valior));
                    
                    if($delta <= 24*60*60) {
                        $p += 0.32;
                    } elseif($delta < 3*24*60*60) {
                        $p += 0.12;
                    }
                    //if(($d->date == $rec->valior) && $d->date == '2017-12-22' && $d->amount == $rec->amount) bp($p, $delta, abs($d->amount - $rec->amount) / max($d->amount, $rec->amount), $d, $rec);
                }

                // Папка на документа
                if(isset($rec->matches['folderId'])) {
                    $p += 0.1;
                }

                if($p > 0.5) {
                    $d->p = $p;
                    $rec->matches['docs'][] = $d;
                }
            }
            
      
            // Ако имаме документи, но нямаме папка, опитваме се да я определим от най-вероятните документи
            if(empty($rec->matches['folderId']) && is_array($rec->matches['docs'])) {
                $foldersTmp = array();
                foreach($rec->matches['docs'] as $d) {
                    $foldersTmp[$d->folderId] += $d->p;
                }
                
                list($rec->matches['folderId']) =  array_keys($foldersTmp, max($foldersTmp));

                if($rec->matches['folderId']) {
                    foreach($rec->matches['docs'] as $id => $d) {
                        if($d->folderId != $rec->matches['folderId']) {
                            unset($rec->matches['docs']);
                        }
                    }
                }
            }

            if(is_array($rec->matches['docs'])) {
                foreach($rec->matches['docs'] as $d) {
                    
                    if(!isset($rec->matches['rows'][$d->threadId])) {
                        $rec->matches['rows'][$d->threadId] = new stdClass();
                    }

                    $row = &$rec->matches['rows'][$d->threadId];
                    if(!isset($row->head[1])) {
                        $row->head[1] = $documents['T' . $d->threadId];
                        $dRec = $row->head[1]->documentMvc->fetch($row->head[1]->documentId);
                        $row->containerId = $dRec->containerId;
                    }
                    
                    switch($d->documentMvc->className) {
                        case 'sales_Proformas':
                            $row->prof[] = $d;
                            break;
                        case 'sales_Invoices':
                            $row->inv[] = $d;
                            break;
                        case 'bank_IncomeDocuments':
                            $row->bdoc[] = $d;
                            break;
                        default:
                    }
                }
            }


            if(is_array($rec->matches['rows']) || $rec->matches['folderId'] || TRUE) {
                self::save($rec);
            } 
            
        }
        
        return $cnt;
       
    }


    /**
     * Транслитерация по правила UniCredit
     */
    public static function transliterate($string)
    {
        $code['э'] = 'e';
        $code['а'] = 'a';
        $code['б'] = 'b';
        $code['в'] = 'v';
        $code['г'] = 'g';
        $code['д'] = 'd';
        $code['е'] = 'e';
        $code['ж'] = 'zh';
        $code['з'] = 'z';
        $code['и'] = 'i';
        $code['й'] = 'j';
        $code['к'] = 'k';
        $code['л'] = 'l';
        $code['м'] = 'm';
        $code['н'] = 'n';
        $code['о'] = 'o';
        $code['п'] = 'p';
        $code['р'] = 'r';
        $code['с'] = 's';
        $code['т'] = 't';
        $code['у'] = 'u';
        $code['ф'] = 'f';
        $code['х'] = 'h';
        $code['ц'] = 'c';
        $code['ч'] = 'ch';
        $code['ш'] = 'sh';
        $code['щ'] = 'sht';
        $code['ъ'] = 'a';
        $code['ы'] = 'yi';
        $code['ь'] = 'j';
        $code['ю'] = 'yu';
        $code['я'] = 'ya';

        $keys = array_keys($code);
        
        $string = mb_strtolower($string);

        $res = str::utf2ascii(preg_replace("/[^a-z0-9]+/i", ' ', str_replace($keys, $code, $string)));

        $res = str_replace(array(' ood ood', 'ad ad', ' eood eood', 'ead ead'), array(' ood', ' ad', ' eood', ' ead'), $res);

        return $res;
    }


    /**
     * Връща масив с папки, където може да има плащания
     */
    public static function getFolders($inThePast = NULL)
    {
        $hnd = 'BANK_FOLDERS_REGISTER';
        
        if(!$inThePast) $inThePast = 60*60*24*1980;
        
        $cachedFolders = core_Cache::get('BANK', 'ACTIVE_FOLDERS');
        
        if(is_array($cachedFolders)) {
            $inThePast = 60*60*24*1;
        } else {
            $cachedFolders = array();
        }

        $query = crm_Companies::getQuery();
        $query->EXT('last', 'doc_Folders', 'externalKey=folderId');
        $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');

        $query->where('#coverClass = ' . core_Classes::getId('crm_Companies'));
        $query->where("#state = 'active' OR #state = 'opened'");
        $query->where("#folderId > 0");
        
        $lastDateActivity = DT::addSecs(-$inThePast);
        $query->where("#last > '$lastDateActivity'");
        
        $res = array();

        while($rec = $query->fetch()) {  
            $title = self::transliterate($rec->name);

            if(!$res[$title] && !$cachedFolders[$title]) {
                $res[$title] = $rec->folderId;
            }

            // $title = 
        }
 
        $res1 = array_merge($res, $cachedFolders);
        
        if(count($res)) {
            core_Cache::set('BANK', 'ACTIVE_FOLDERS', $res1, 24*60);
        }
 
        return $res1;
    }


    public static function getDocuments()
    {
        // Обикаляме по всичко отворени Продажби и такива, в които имаме затваряне
        $earlyClosed = dt::addSecs(-5*24*60*60);
        $query = sales_Sales::getQuery();
        $query->where("#state = 'active' OR (#state = 'closed' AND #closedOn >= '{$earlyClosed}')");
        $query->orderBy('createdOn', 'DESC');
        while($rec = $query->fetch()) {
            // Извличаме всички проформи, фактури и документи за плащане в посочените нишки 

            $threads[] = $rec->threadId;

            $o = new stdClass();
            $o->type        = 'income';
            $o->number      = $rec->id;
            $o->amount      =  round(($rec->amountBl ? $rec->amountBl : $rec->amountDeal - $rec->amountDiscount + $rec->amountVat) / $rec->currencyRate, 2);
            $o->currencyId  = $rec->currencyId;
            $o->folderId    = $rec->folderId;
            $o->threadId    = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId  = $rec->id;
  
            $docs['T' . $rec->threadId] = $o;
        }
 
        $threadIds = implode(',', $threads);
        // $threadIds = 130628;
        $query = sales_Invoices::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while($rec = $query->fetch("#threadId IN ({$threadIds}) AND #state = 'active'")) {
            $o = new stdClass(); 
            $o->type   = 'income';
            $o->number = $rec->number;
            $o->date   = $rec->dueDate;
            $o->amount      = round($rec->dealValue - $rec->discountAmount + $rec->vatAmount, 2);
            $o->currencyId  = $rec->currencyId;
            $o->folderId    = $rec->folderId;
            $o->threadId    = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId  = $rec->id;
            $docs[] = $o;
        }

        $query = sales_Proformas::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while($rec = $query->fetch("#threadId IN ({$threadIds}) AND #state = 'active'")) {
            $o = new stdClass();
            $o->type   = 'income';
            $o->number = $rec->number;
            $o->date   = $rec->dueDate;
            $o->amount      = round($rec->dealValue - $rec->discountAmount + $rec->vatAmount, 2);
            $o->currencyId  = $rec->currencyId;
            $o->folderId    = $rec->folderId;
            $o->threadId    = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId  = $rec->id;
            $docs[] = $o;
        }
        
        // Входящи банкови документи
        $query = bank_IncomeDocuments::getQuery();
        $query->orderBy('createdOn', 'DESC');
        while($rec = $query->fetch("#threadId IN ({$threadIds}) AND (#state = 'active' OR #state = 'pending')")) {

           
            $o = new stdClass();
            $o->type   = 'income';
            $o->number = $rec->number;
            $o->date   = $rec->valior ? $rec->valior : $rec->termDate;
            $o->amount      = round($rec->amountDeal, 2);
            $o->currencyId  = $rec->currencyId;
            $o->folderId    = $rec->folderId;
            $o->threadId    = $rec->threadId;
            $o->documentMvc = $query->mvc;
            $o->documentId  = $rec->id;
            $docs[] = $o;
        }

        return $docs;
    }


    function act_Match()
    {
        requireRole('admin,ceo,bank');
        
        $res = self::findMatches();

        return new Redirect(array('bank_register'), "Обработени {$res} записа");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if(haveRole('admin,debug')){
    		$data->toolbar->addBtn('Разнасяне', array($mvc, 'Match', 'ret_url' => TRUE), 'ef_icon=img/16/briefcase.png, title=Намиране на съответствия');
    	}
    }


}