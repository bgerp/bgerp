<?php 


/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Periods extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Периоди';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'borsa, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'borsa, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Created, plg_State2, plg_Sorting, plg_Modified, plg_RowTools2';
    
    
    /**
     * 
     */
    public $listFields = 'lotId, price, periodFromTo, blastId, qAvailable, qBooked, qConfirmed, modifiedOn, modifiedBy, state';
    
    
    /**
     * Интерфейси
     */
    var $interfaces = 'bgerp_PersonalizationSourceIntf';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=productName)', 'caption=Артикул, mandatory, removeAndRefreshForm=from|to, refreshForm, silent');
        $this->FLD('from', 'date', 'caption=От, mandatory, input');
        $this->FLD('to', 'date', 'caption=До, mandatory, input');
        $this->FLD('qAvailable', 'double(smartRound,decimals=2,Min=0)', 'caption=Количество->Общо, oldFieldName=qAviable, mandatory');
        $this->FLD('qBooked', 'double(smartRound,decimals=2)', 'caption=Количество->Запазено, input=none');
        $this->FLD('qConfirmed', 'double(smartRound,decimals=2)', 'caption=Количество->Потвърдено, input=none');
        $this->FLD('blastId', 'key(mvc=blast_Emails)', 'caption=Имейл,column=none,input=none');
        
        $this->FNC('price', 'double(smartRound,decimals=2)', 'caption=Цена|* ' . acc_Periods::getBaseCurrencyCode());
        $this->FNC('periodFromTo', 'varchar', 'caption=За период');
        
        $this->setDbUnique('lotId, from, to');
    }
    
    
    /**
     * Връща съответния запис за периода
     * 
     * @param integer $lotId
     * @param DateTime $from
     * @param DateTime $to
     * 
     * @return false|stdClass
     */
    public static function getPeriodRec($lotId, $from, $to, $state = null)
    {
        if (isset($state)) {
            
            return self::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]' AND #state = '[#4#]'", $lotId, $from, $to, $state));
        } else {
            
            return self::fetch(array("#lotId = '[#1#]' AND #from = '[#2#]' AND #to = '[#3#]'", $lotId, $from, $to));
        }
    }
    
    
    /**
     * 
     * @param borsa_Periods $mvc
     * @param stdClass $rec
     */
    function on_CalcPeriodFromTo($mvc, $rec)
    {
        $rec->periodFromTo = borsa_Lots::getPeriodVerb(array('bPeriod' => $rec->from, 'ePeriod' => $rec->to));
    }
    
    
    /**
     *
     * @param borsa_Periods $mvc
     * @param stdClass $rec
     */
    function on_CalcPrice($mvc, $rec)
    {
        $pArr = cls::get('borsa_Lots')->getChangePeriods($rec->lotId);
        foreach ($pArr as $pRec) {
            if (($pRec['bPeriod'] == $rec->from) && ($pRec['ePeriod'] == $rec->to)) {
                if ($pRec['price']) {
                    $rec->price = $pRec['price'];
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('from', 'DESC');
        $data->query->orderBy('to', 'DESC');
        $data->query->orderBy('modifiedOn', 'DESC');
        
        $data->listFilter->setFieldTypeParams('lotId', array('allowEmpty' => 'allowEmpty'));
        
        $data->listFilter->showFields = 'lotId';
        
        $data->listFilter->input('lotId');
        
        if ($data->listFilter->rec->lotId) {
            $data->query->where(array("#lotId = '[#1#]'", $data->listFilter->rec->lotId));
        }
        
        if ($data->listFilter->rec->lotId) {
            $data->query->where(array("#lotId = '[#1#]'", $data->listFilter->rec->lotId));
        }
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if ($rec->id) {
            $form->setReadOnly('lotId');
            $form->setReadOnly('from');
            $form->setReadOnly('to');
        } else {
            $optArr = $form->fields['lotId']->type->prepareOptions();
            if (!empty($optArr)) {
                $form->setDefault('lotId', key($optArr));
            }
            
            $suggArr = array();
            if ($rec->lotId) {
                $pArr = cls::get('borsa_Lots')->getChangePeriods($rec->lotId);
                foreach ($pArr as $pRec) {
                    if (!$mvc->getPeriodRec($rec->lotId, $pRec['bPeriod'], $pRec['ePeriod'])) {
                        $form->setDefault('from', $pRec['bPeriod']);
                        $form->setDefault('to', $pRec['ePeriod']);
                        
                        break;
                    }
                }
                
                $pId = borsa_Lots::fetchField($rec->lotId, 'productId');
                if ($pId) {
                    $mId = cat_Products::fetchField($pId, 'measureId');
                    if ($mId) {
                        $sName = cat_UoM::getShortName($mId);
                        $data->form->setField('qAvailable', array('unit' => $sName));
                    }
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->lotId) {
            $pId = borsa_Lots::fetchField($rec->lotId, 'productId');
            if ($pId && cat_Products::haveRightFor('single', $pId)) {
                $row->lotId = cat_Products::getLinkToSingle($pId, 'name');
            }
        }
        
        if ($rec->blastId) {
            if (blast_Emails::haveRightFor('single', $rec->blastId)) {
                $row->blastId = blast_Emails::getLinkToSingle($rec->blastId);
            }
        }
    }
    
    
    /**
     * Добавяне на бутоните в единичния изглед
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if($data->rec->blastId && blast_Emails::haveRightFor('single', blast_Emails::fetch($data->rec->blastId))) {
            $data->toolbar->addBtn('Имейл', array('blast_Emails', 'single', $data->rec->blastId), 'ef_icon=img/16/emails.png,row=2');
        }
    }
    
    
    /**
     * Крон функция за изпращане на циркулярни имейли за начало на период
     */
    public function cron_sendBlast()
    {
        $query = self::getQuery();
        $query->where("#state = 'active'");
        $query->where("#blastId IS NULL");
        
        $cnt = $query->count();
        
        if ($cnt) {
            
            $blId = null;
            $pArr = array();
            while ($rec = $query->fetch()) {
                if (!isset($pArr[$rec->lotId])) {
                    $pArr[$rec->lotId] = cls::get('borsa_Lots')->getPeriods($rec->lotId);
                }
                
                // Този период все още не е активен и го прескачаме
                if (!$pArr[$rec->lotId][$rec->id]) {
                    continue;
                }
                
                if (!$blId) {
                    $blId = blast_Emails::createEmail($this, $rec->id, '[#text#]', '[#subject#]', array('recipient' => '[#company#]', 'attn' => '[#name#]'));
                }
                
                $rec->blastId = $blId;
                
                $this->save($rec, 'blastId');
            }
            
            if ($blId) {
                blast_Emails::activateEmail($blId, 20);
            }
        }
    }
    
    
    /***************************************************************************************
     *
     * bgerp_PersonalizationSourceIntf
     *
     ***************************************************************************************/
    
    
    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     *
     * @param integer $id
     *
     * @return array
     */
    function getPersonalizationDescr($id)
    {
        
        return array(
                'email'   => cls::get('type_Email'),
                'company' => cls::get('type_Varchar'),
                'name'    => cls::get('type_Varchar'),
                'text'    => cls::get('type_Text'),
                'subject' => cls::get('type_Text')
        );
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     *
     * @param integer $id
     * @param integer $limit
     *
     * @return array
     */
    function getPresonalizationArr($id, $limit = 0)
    {
        expect($rec = $this->fetch($id));
        
        expect($rec->blastId);
        
        $pQuery = borsa_Periods::getQuery();
        $pQuery->where(array("#blastId = '[#1#]'", $rec->blastId));
        
        $pQuery->show('lotId');
        
        while ($pRec = $pQuery->fetch()) {
            $lotIdArr[$pRec->lotId] = $pRec->lotId;
        }
        
        expect($lotIdArr);
        
        $query = borsa_Companies::getQuery();
        $query->where("#allowedLots IS NULL");
        $query->orLikeKeylist("allowedLots", $lotIdArr);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $res = array();
        while ($cRec = $query->fetch()) {
            if ((!$cRec->companyId) || (!$cRec->email)) {
                
                continue;
            }
            $compRec = crm_Companies::fetch($cRec->companyId);
            $folderId = $compRec->folderId;
            if (!$folderId) {
                $folderId = crm_Companies::forceCoverAndFolder($compRec);
            }
            
            if (!$folderId) {
                continue;
            }
            $lg = doc_Folders::getLanguage($folderId);
            
            
            if (!$lg) {
                $lg = core_Lg::getCurrent();
            } else {
                if (!core_Lg::isGoodLg($lg)) {
                    $lg = 'en';
                }
            }
            
            if ($lg) {
                core_Lg::push($lg);
            }
            
            $appTitle = tr(core_Setup::get('EF_APP_TITLE', true));
            
            $tpl = getTplFromFile('borsa/tpl/Blast.shtml');
            $tpl->replace($appTitle, 'COMPANY_NAME');
            $tpl->replace($cRec->url, 'LINK');
            
            $res[$cRec->id] = array(
                                        'company' => $cRec->name,
                                        'email' => $cRec->email,
                                        'subject' => tr('Нов период за запитване в') . ' ' . $appTitle,
                                        'text' => $tpl->getContent()
                                    );
            
            if ($lg) {
                core_Lg::pop($lg);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     *
     * @param integer $id
     * @param boolean $verbal
     *
     * @return string
     */
    function getPersonalizationTitle($id, $verbal = FALSE)
    {
        static $resArr = array();
        
        $lg = core_Lg::getCurrent();
        
        $key = md5($lg . '|' . $id . '|' . $vebal);
        
        if (!$resArr[$key]) {
            $rec = $this->fetchRec($id);
            $resArr[$key] = tr('Запитване за|* "') . borsa_Lots::fetchField($rec->lotId, 'productName') . '" - ' . $rec->periodFromTo;
        }
        
        return $resArr[$key];
    }
    
    
    /**
     * Връща TRUE или FALSE дали потребителя може да използва дадения източник на персонализация
     *
     * @param integer $id
     * @param integer $userId
     *
     * @return boolean
     */
    function canUsePersonalization($id, $userId = NULL)
    {
        return $this->haveRightFor('list', $id, $userId);
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас, които са достъпни за посочения потребител
     *
     * @param integer $userId
     *
     * @return array
     */
    function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        //Добавя в лист само списъци с имейли
        $query = $this->getQuery();
        $query->where("#state = 'active'");
        $now = dt::now(false);
        $query->where(array("#from >= '[#1#]'", $now));
        
        // Обхождаме откритите резултати
        while ($rec = $query->fetch()) {
            
            // Ако няма права за персонализиране, да не се връща
            if (!$this->canUsePersonalization($rec->id, $userId)) {
                continue;
            }
            
            // Добавяме в масива
            $resArr[$rec->id] = $this->getPersonalizationTitle($rec, false);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     *
     * @param integer $id
     *
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $resArr = $this->getPersonalizationOptions();
        
        return $resArr;
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     *
     * @param integer $id
     *
     * @return core_ET
     */
    function getPersonalizationSrcLink($id)
    {
        $title = $this->getPersonalizationTitle($id);
        
        return ht::createLink($title, array($this, 'List', $id));
    }
    
    
    /**
     * Връща езика за източника на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return string
     */
    public function getPersonalizationLg($id)
    {
        
        return ;
    }
}
