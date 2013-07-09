<?php 


/**
 * Модул за записване на външните номера от указателя
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_ExternalNum extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Външни номера';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, callcenter_DataWrapper, plg_Printing, plg_Search, plg_Sorting';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'number, type';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, number, type, contragent=Визитка';
	
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('number', 'bglocal_PhoneType', 'caption=Номер');
        $this->FLD('type', 'enum(tel=Телефон, mobile=Мобилен, fax=Факс)', 'caption=Тип');
        $this->FLD('classId', 'key(mvc=core_Classes, select=name)', 'caption=Визитка->Клас');
        $this->FLD('contragentId', 'int', 'caption=Визитка->Номер');
        
        $this->FNC('contragent', 'varchar', 'caption=Контрагент');
        
        $this->setDbUnique('number, type, classId, contragentId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има клас
        if ($rec->classId) {
            
            // Инстанция на класа
            $class = cls::get($rec->classId);
            
            // Вземаме записите
            $cardRec = $class->fetch($rec->contragentId);
            
            // Ако имаме права за сингъла на записа
            if ($class->haveRightFor('single', $cardRec)) {
                
                // Линк към сингъла
                $card = ht::createLink($cardRec->name, array($class, 'single', $rec->contragentId)) ;
            } else {
                
                // Ако нямаме права към сингъла на записа
                
                // Вземам линк към профила на отговорника
                $card = crm_Profiles::createLink($cardRec->inCharge);
            }
        }
        
        // Добавяме линка в контрагента
        $row->contragent = $card;
    }
    
    
    /**
     * Добавяме посочения номер в модела
     * 
     * @param array $numbersArr - Масив с номерата, които ще добавяме - tel, fax, mobile
     * @param int $classId - id на класа
     * @param int $docId - id на документа
     */
    public static function updateNumbers($numbersArr, $classId, $docId)
    {
        // Обхождаме подадени номера
        foreach ((array)$numbersArr as $type => $number) {
            
            // Масив с номерата
            $numberArr = arr::make($number);
            
            // Обхождаме номерата
            foreach ((array)$numberArr as $num) {
                
                // Вземаме детайлна информация за номерата
                $numberDetArr = bglocal_PhoneType::toArray($num);
                
                // Обхождаме резултата
                foreach ($numberDetArr as $numberDet) {
                    
                    // Ако не е обект, прескачаме
                    if (!is_object($numberDet)) continue;
                    
                    // Вземаме номера
                    $numStr = $numberDet->countryCode . $numberDet->areaCode . $numberDet->number;
                    
                    // Ако е факс
                    if ($type == 'fax') {
                        $fType = 'fax';
                    } else {
                        // Ако е мобилине
                        if ($numberDet->mobile) {
                            $fType = 'mobile';
                        } else {
                            $fType = 'tel';
                        }
                    }
                    
                    // Създаваме записа
                    $nRec = new stdClass();
                    $nRec->number = $numStr;
                    $nRec->type = $fType;
                    $nRec->classId = $classId;
                    $nRec->contragentId = $docId;
                    
                    // Записваме, ако няма такъв запис
                    static::save($nRec, NULL, 'IGNORE');
                }
            }
        }
        
        return ;
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->input('search', 'silent');
    }
    
    
    /**
     * Връща последния запис за номера
     * 
     * @param string $number - Номера
     * @param string $type - Типа на номера - tel, mobile, fax
     * 
     * @return core_Query - Запис от модела, отговарящ на условията
     */
    static function getLastRecForNum($number, $type=FALSE)
    {
        // Вземаме номера, на инициатора
        $numberArr = bglocal_PhoneType::toArray($number);
        
        // Ако има номер
        if (!$numberArr[0]) return ;
        
        // Вземаме стринга на номера
        $numStr =  $numberArr[0]->countryCode . $numberArr[0]->areaCode . $numberArr[0]->number;
        
        // Вземаме последния номер, който сме регистрирали
        $query = callcenter_ExternalNum::getQuery();
        $query->where(array("#number = '[#1#]'", $numStr));
        $query->orderBy('id', 'DESC');
        $query->limit(1);
        
        // Ако е зададен типа
        if ($type) {
            
            // Добавяме типа в клаузата
            $query->where(array("#type = '[#1#]'", $type));
        }
        
        // Вземаме записа
        return $query->fetch();
    }
}
