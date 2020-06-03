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
class borsa_Bids extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Оферти';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo, sales';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'borsa, ceo';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canRestore = 'borsa, ceo';
    
    
    /**
     * Кой има право да одобрява запитванията
     */
    public $canConfirm = 'borsa, ceo, sales';
    
    
    /**
     * Мастър ролите за работа със заявките
     */
    public $masterRoles = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_state, plg_Created, plg_RowTools2, plg_Rejected, plg_Modified, plg_Sorting';
    
    
    /**
     * Описание на полетата
     */
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=productName,allowEmpty)', 'caption=Продукт, mandatory, refreshForm');
        $this->FLD('periodId', 'key(mvc=borsa_Periods,select=periodFromTo)', 'caption=Период, mandatory');
        $this->FLD('price', 'double(smartRound,decimals=2)', 'caption=Цена');
        $this->FLD('quantity', 'double(smartRound,decimals=5)', 'caption=Количество');
        $this->FLD('companyId', 'key(mvc=borsa_Companies,select=name)', 'caption=Фирма');
        $this->FLD('note', 'text', 'caption=Забележка');
        $this->FLD('ip', 'ip', 'caption=IP,input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен)', 'caption=Състояние,input=none');
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'caption=Продажба,input=none');
        
        $this->setDbIndex('lotId, periodId, state');
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
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('createdOn', 'DESC');
        
        $data->listFilter->showFields = 'lotId';
        
        // Ако ще вижда само определени търгове
        if (!haveRole($mvc->masterRoles)) {
            $cu = core_Users::getCurrent();
            
            // Ограничаваме да се показват само достъпните резултати
            $data->query->EXT('canConfirm', 'borsa_Lots', 'externalName=canConfirm,externalKey=lotId');
            $data->query->likeKeylist('canConfirm', $cu);
            
            // Показваме само достъпните опции
            $optArr = $data->listFilter->fields['lotId']->type->prepareOptions();
            $lQuery = borsa_Lots::getQuery();
            $lQuery->likeKeylist('canConfirm', $cu);
            $lQuery->show('id');
            $aRec = $lQuery->fetchAll();
            foreach ($optArr as $oId => $oName) {
                if (!$aRec[$oId]) {
                    unset($optArr[$oId]);
                }
            }
            $optArr[''] = '';
            
            $data->listFilter->setOptions('lotId', $optArr);
        }
        
        $data->listFilter->input('lotId');
        
        if ($data->listFilter->rec->lotId) {
            $data->query->where(array("#lotId = '[#1#]'", $data->listFilter->rec->lotId));
        }
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Първичния ключ на направения запис
     * @param stdClass     $rec     Всички полета, които току-що са били записани
     * @param string|array $fields  Имена на полетата, които sa записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $qArr = $mvc->getQuantity($rec->lotId, $rec->periodId);
        $qArr['qBooked'] = $qArr['qBooked'] ? $qArr['qBooked'] : 0;
        $qArr['qConfirmed'] = $qArr['qConfirmed'] ? $qArr['qConfirmed'] : 0;
        
        $pRec = borsa_Periods::fetch($rec->periodId);
        
        $pRec->qBooked = $qArr['qBooked'];
        $pRec->qConfirmed = $qArr['qConfirmed'];
        
        borsa_Periods::save($pRec, 'qBooked, qConfirmed');
    }
    
    
    /**
     * Помощна функция за преизчисляване на количествата
     * 
     * @param integer $lotId
     * 
     * @return array
     */
    public static function getQuantity($lotId, $periodId)
    {
        $query = self::getQuery();
        $query->where(array("#lotId = '[#1#]'", $lotId));
        $query->where(array("#periodId = '[#1#]'", $periodId));
        $query->where("#state != 'rejected'");
        
        $resArr = array();
        while ($rec = $query->fetch()) {
            if ($rec->state == 'draft') {
                $resArr['qBooked'] += $rec->quantity;
            } elseif ($rec->state == 'active') {
                $resArr['qConfirmed'] += $rec->quantity;
            }
        }
        
        return $resArr;
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
        $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, true);
        
        $row->brid = log_Browsers::getLink($rec->brid);
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        foreach ($data->rows as $id => &$row) {
            if ($mvc->haveRightFor('confirm', $data->recs[$id])) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Потвърждаване', array($mvc, 'confirm', $id, 'ret_url' => true), array('ef_icon' => 'img/16/stock_new_meeting.png', 'title' => 'Потвърждаване на оферта'));
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'confirm' && $rec && ($requiredRoles != 'no_one')) {
            if ($rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
            
            if (!haveRole($mvc->masterRoles, $userId)) {
                $lRec = borsa_Lots::fetch($rec->lotId);
                if (!type_Keylist::isIn($userId, $lRec->canConfirm)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Екшън за потвърждаване на офертите
     * 
     * @return Redirect
     */
    public function act_Confirm()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('confirm', $rec);
        
        $form = $this->getForm();
        
        // Другите полета да не може да се променят
        $fArr = $form->selectFields("#input != 'none' AND #input != 'hidden'");
        foreach ($fArr as $fName => $fVal) {
            $form->setReadOnly($fName, $rec->{$fName});
            
            if ($fVal->mandatory) {
                unset($form->fields[$fName]->mandatory);
            }
        }
        
        $form->FLD('saleIdInt', 'int(Min=0)', 'caption=Продажба, mandatory, hint=Номер на продажбата, before=lotId');
        
        $form->input(null, true);
        $form->input();
        
        if ($form->isSubmitted()) {
            $sRec = sales_Sales::fetch($form->rec->saleIdInt);
            
            if (!$sRec) {
                $form->setError('saleIdInt', 'Не е открита такава продажба');
            } elseif ($sRec->state != 'active') {
                $form->setError('saleIdInt', 'Тази продажба не е контирана');
            }
        }
        
        $form->title = 'Потвърждаване на заявка';
        if ($rec->companyId) {
            $cId = borsa_Companies::fetchField($rec->companyId, 'companyId');
            if ($cId) {
                $form->title .= ' към|*' . crm_Companies::getLinkToSingle($cId, 'name');
            }
        }
        
        if ($rec->lotId) {
            $pId = borsa_Lots::fetchField($rec->lotId, 'productId');
            $mId = cat_Products::fetchField($pId, 'measureId');
            
            if ($mId) {
                $sName = cat_UoM::getShortName($mId);
                $form->setField('quantity', array('unit' => $sName));
            }
        }
        
        $bQurr = acc_Periods::getBaseCurrencyCode();
        $form->setField('price', array('unit' => $bQurr));
        
        $retUrl = getRetUrl();
        if (!$retUrl) {
            $retUrl = array($this, 'list');
        }
        
        if ($form->isSubmitted()) {
            
            $rec->exState = $rec->state;
            $rec->state = 'active';
            $rec->saleId = $form->rec->saleIdInt;
            
            $this->save($rec, 'exState, state, saleId');
            
            return new Redirect($retUrl);
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Потвърди', 'save', 'ef_icon = img/16/stock_new_meeting.png, title = Потвърждаване на заявка');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logInfo('Разглеждане на формата за добавяне на пера към номенклатура');
        
        return $this->renderWrapping($form->renderHtml());
    }
}
