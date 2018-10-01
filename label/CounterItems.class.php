<?php 

/**
 * Детайл на броячите.
 * Показва кой брояч в кой етикет е използван и до кой номер е стигнал
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_CounterItems extends core_Detail
{
    /**
     * Заглавие на модела
     */
    public $title = 'Запис в броячи';
    
    
    public $singleTitle = 'Записи';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'label, admin, ceo';
    
    
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
    public $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_Created, plg_Modified, plg_Sorting';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'counterId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, printId, number, modifiedOn, modifiedBy, createdOn, createdBy';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Брояч';
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('counterId', 'key(mvc=label_Counters, select=name)', 'caption=Брояч, mandatory');
        $this->FLD('printId', 'key(mvc=label_Prints, select=title)', 'caption=Етикет, mandatory');
        $this->FLD('number', 'int', 'caption=Номер');
        
        $this->setDbIndex('number, counterId');
        $this->setDbIndex('counterId, printId');
        $this->setDbIndex('counterId');
        
        $this->setDbUnique('counterId, printId, number');
    }
    
    
    /**
     * Връща най - голямата стойност за брояча
     *
     * @param int $counterId - id на брояча
     */
    public static function getMax($counterId)
    {
        // Вземаме най - голямата стойност на номера за съответния брояч
        $query = static::getQuery();
        $query->XPR('maxVal', 'int', 'MAX(#number)');
        $query->where(array("#counterId = '[#1#]'", $counterId));
        
        $query->show('maxVal');
        
        $query->limit(1);
        
        $rec = $query->fetch();
        
        // Връщаме максималната стойност
        return $rec->maxVal;
    }
    
    
    /**
     * Обновяваме брояча
     *
     * @param int $counterId - id на брояча
     * @param int $printId   - id на етикета
     * @param int $number    - Стойността на брояча
     * @param boolean $update    - дали да се обновят или добавят нови
     *
     * @return int - id на записа
     */
    public static function updateCounter($counterId, $printId, $number, $update = false)
    {
        if ($update) {
            // Вземаме записа
            $rec = static::fetch(array("#counterId = '[#1#]' AND #printId = '[#2#]'", $counterId, $printId));
        }
        
        // Ако няма запис
        if (!$update || !$rec) {
            $rec = new stdClass();
            $rec->counterId = $counterId;
            $rec->printId = $printId;
        }
        
        // Добавяме номера
        $rec->number = $number;
        
        // Записваме
        return static::save($rec);
    }
    
    
    /**
     *
     *
     * @param label_CounterItems $mvc
     * @param object             $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('modifiedOn', 'DESC');
        $data->query->orderBy('number', 'DESC');
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
        if ($rec->printId) {
            if (label_Prints::haveRightFor('single', $rec->printId)) {
                $row->printId = label_Prints::getLinkToSingle($rec->printId, 'title');
            }
        }
    }
}
