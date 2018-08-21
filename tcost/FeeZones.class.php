<?php


/**
 * Модел "Взаимодействие на Зони и Навла"
 *
 *
 * @category  bgerp
 * @package   tcost
 *
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tcost_FeeZones extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'cond_TransportCalc';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'trans_FeeZones';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'name, deliveryTermId=Доставка->Условие, deliveryTime=Доставка->Време,createdOn, createdBy';
    
    
    /**
     * Заглавие
     */
    public $title = 'Навла';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Printing, tcost_Wrapper';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,tcost';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,tcost';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,tcost';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,tcost';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,tcost';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,tcost';
    
    
    /**
     * Детайли за зареждане
     */
    public $details = 'tcost_Fees, tcost_Zones';
    
    
    /**
     * Единично поле за RowTools
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Константа, специфична за дадения режим на транспорт
     *
     * @var float
     */
    const V2C = 1;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Зона, mandatory');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName)', 'caption=Условие на доставка, mandatory');
        $this->FLD('deliveryTime', 'time(uom=days)', 'caption=Доставка,recently,smartCenter');
        
        $this->FLD('addTax', 'double', 'caption=Надценки->Твърда, autohide');
        $this->FLD('addPerKg', 'double', 'caption=Надценки->За кг, autohide');
        $this->FLD('volume2quantity', 'double(min=0)', 'caption=Надценки->Обем към кг, autohide');
        
        $this->setDbIndex('deliveryTermId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $form->setField('volume2quantity', "placeholder=" . self::V2C);
        $currecyId = acc_Periods::getBaseCurrencyCode();
        
        if($addTax = tcost_Setup::get('ADD_TAX')){
            $form->setField('addTax', "placeholder={$addTax},unit={$currecyId}");
        }
        
        if($addPerKg = tcost_Setup::get('ADD_PER_KG')){
            $form->setField('addPerKg', "placeholder={$addPerKg},unit={$currecyId}");
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
        if ($action == 'delete' && isset($rec)) {
            if (tcost_Fees::fetch("#feeId = {$rec->id}") || tcost_Zones::fetch("#zoneId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param float $weight - Тегло на товара
     * @param float $volume - Обем  на товара
     * @param float|null $coefficient - коефициент за отношение, null за глобалната константа
     *
     * @return float - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume, $coefficient = null)
    {
        $volumicWeight = null;
        if (!empty($weight) || !empty($volume)) {
            $multiplier = !empty($coefficient) ? $coefficient : self::V2C;
            
            $volumicWeight = max($weight, $volume * $multiplier);
        }
        
        return $volumicWeight;
    }
    
    
    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int   $deliveryTermId - условие на доставка
     * @param float $singleWeight   - тегло
     * @param float $singleVolume   - обем
     * @param int   $totalWeight    - Общо тегло на товара
     * @param int   $totalVolume    - Общ обем на товара
     * @param array $params         - Други параметри
     *
     * @return array
     *               ['fee']          - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     *               ['deliveryTime'] - срока на доставка в секунди ако го има
     *               ['explain']      - текстово обяснение на изчислението
     */
    public function getTransportFee($deliveryTermId, $singleWeight, $singleVolume, $totalWeight, $totalVolume, $params = array())
    {
        $toCountry = $params['deliveryCountry'];
        $toPostalCode = $params['deliveryPCode'];
        
        // Определяне на зоната на транспорт, за зададеното условие на доставка
        $zoneRec = tcost_Zones::getZoneIdAndDeliveryTerm($deliveryTermId, $toCountry, $toPostalCode);
        $singleWeight = $this->getVolumicWeight($singleWeight, $singleVolume, $zoneRec->volume2quantity);
        
        // Ако няма, цената няма да може да се изчисли
        if (empty($singleWeight)) {
            return array('fee' => cond_TransportCalc::EMPTY_WEIGHT_ERROR);
        }
        
        $totalWeight = $this->getVolumicWeight($totalWeight, $totalVolume);
        
        // Опит за калкулиране на цена по посочените данни
        $fee = tcost_Fees::calcFee($zoneRec, $totalWeight, $singleWeight);
        
        $zoneId = $fee[2];
        $deliveryTime = ($fee[3]) ? $fee[3] : null;
        
        // Ако цената може да бъде изчислена се връща
        if (!($fee < 0)) {
            $fee = (isset($fee[1])) ? $fee[1] : 0;
        }
        
        $explain = null;
        if ($fee > 0) {
            
            // Надценките се взимат с приоритет от зоната, ако няма от глобалните настройки
            $zoneRec = self::fetch($zoneId, 'addTax,addPerKg,volume2quantity');
            $tax = isset($zoneRec->addTax) ? $zoneRec->addTax : tcost_Setup::get('ADD_TAX');
            $addPerKg = isset($zoneRec->addPerKg) ? $zoneRec->addPerKg : tcost_Setup::get('ADD_PER_KG');
            
            if($totalWeight){
                $tax = $tax * $singleWeight / $totalWeight;
            }
            $inc = $addPerKg * $singleWeight;
            $fee = $tax + $inc + $fee;
            
            $zoneName = tcost_FeeZones::getTitleById($zoneId);
            $termCode = cond_DeliveryTerms::getVerbal($deliveryTermId, 'codeName');
            $explain = ", {$termCode}, ZONE = '{$zoneName}', VOLUMIC_WEIGHT = '{$singleWeight}', ADD_TAX = {$tax}, ADD_PER_KG = {$inc}";
        }
        
        $res = array('fee' => $fee, 'deliveryTime' => $deliveryTime, 'explain' => $explain);
        
        // Връщане на изчислената цена
        return $res;
    }
    
    
    /**
     * Добавяне на бутон за изчисление
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (haveRole('admin, ceo, tcost')) {
            $data->toolbar->addBtn('Изчисление', array($mvc, 'calcFee', 'ret_url' => true), 'ef_icon=img/16/arrow_out.png, title=Изчисляване на разходи по транспортна зона');
        }
    }
    
    
    /**
     * Изчисление на транспортни разходи
     */
    public function act_CalcFee()
    {
        //Дос на потребителите
        requireRole('admin, ceo, tcost');
        
        // Вземаме съответстващата форма на този модел
        $form = cls::get('core_Form');
        $form->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms, select = codeName,allowEmpty)', 'caption=Условие на доставка, mandatory');
        $form->FLD('countryId', 'key(mvc = drdata_Countries, select=commonName,allowEmpty)', 'caption=Държава, mandatory,smartCenter');
        $form->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,smartCenter, notNull');
        $form->FLD('singleWeight', 'double(Min=0)', 'caption=Единично тегло,mandatory');
        $form->FLD('totalWeight', 'double(Min=0)', 'caption=Тегло за изчисление,recently, unit = kg.,mandatory');
        
        // Въвеждаме формата от Request (тази важна стъпка я бяхме пропуснали)
        $form->input();
        $form->setDefault('singleWeight', 1);
        
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            try {
                $zoneRec = tcost_Zones::getZoneIdAndDeliveryTerm($rec->deliveryTermId, $rec->countryId, $rec->pCode);
                $result = tcost_Fees::calcFee($zoneRec, $rec->totalWeight, $rec->singleWeight);
                if ($result < 0) {
                    $form->setError('deliveryTermId,countryId,pCode', "Не може да се изчисли сума за транспорт (${result})");
                } else {
                    $zoneName = tcost_FeeZones::getVerbal($result[2], 'name');
                    $form->info = 'Цената за|* <b>' . $rec->singleWeight . '</b> |на|* <b>' . $rec->totalWeight . '</b> |кг. от този пакет ще струва|* <b>'. round($result[1], 4).
                    '</b>, |a всички|* <b>'.  $rec->totalWeight . '</b> |ще струват|* <b>' . round($result[0], 4) . '</b>. |Пратката попада в|* <b>' . $zoneName . '</b>';
                    $form->info = tr($form->info);
                }
            } catch (core_exception_Expect $e) {
                $form->setError('zoneId, countryId', 'Не може да се изчисли по зададените данни, вашата пратка не попада в никоя зона');
            }
        }
        
        $form->title = 'Пресмятане на навла';
        $form->toolbar->addSbBtn('Изчисли', 'save', 'ef_icon=img/16/arrow_refresh.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        return $this->renderWrapping($form->renderHTML());
    }
    
    
    /**
     * Добавя полета за доставка към форма
     *
     * @param core_FieldSet $form
     * @param string|NULL   $userId
     *
     * @return void
     */
    public function addFields(core_FieldSet &$form, $userId = null)
    {
        $form->setField('deliveryCountry', 'mandatory');
        $form->setField('deliveryPCode', 'mandatory');
        $form->setField('deliveryPlace', 'mandatory');
        $form->setField('deliveryAddress', 'mandatory');
    }
    
    
    /**
     * Проверява форма
     *
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkForm(core_FieldSet &$form)
    {
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @return array
     */
    public function getFields()
    {
        return array();
    }
    
    
    /**
     * Рендира информацията
     *
     * @param stdClass rec
     *
     * @return core_ET $tpl
     */
    public function renderDeliveryInfo($rec)
    {
        $tpl = new core_ET('');
        
        return $tpl;
    }
}
