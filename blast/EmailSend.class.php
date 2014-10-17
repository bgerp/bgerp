<?php 


/**
 * Лог на изпратените писма
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class blast_EmailSend extends core_Detail
{
    
    /**
     * Заглавие
     */
    public $title = "Лог на изпращаните писма";
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast';
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'no_one';
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast';
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast';
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper, plg_Created';
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'emailId';
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'email, sentOn, state';
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 20;
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'blast_ListSend';
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('emailId', 'key(mvc=blast_Emails, select=subject)', 'caption=Списък');
        $this->FLD('dataId', 'int', 'caption=Списък данни');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни');
        $this->FLD('state', 'enum(pending=Чакащо, sended=Изпратено)', 'caption=Състояние, input=none');
        $this->FLD('sentOn', 'datetime(format=smartTime)', 'caption=Изпратено->На, input=none');
        $this->FLD('email', 'emails', 'caption=Изпратено->До, input=none');
        
        $this->setDbUnique('emailId, dataId');
    }
    
    
    /**
     * Обновява списъка
     *
     * @param integer $emailId - id на мастер (blast_Emails)
     * @param array $dataArr - Масив с данните - ключ id на източника и стойност самите данни
     * @param array $emailFieldsArr - Масив с полета, които се използва за имейл
     *
     * @return integer - Броя на добавените записи
     */
    public static function updateList($emailId, $dataArr, $emailFieldsArr = array())
    {
        $cnt = 0;
        
        // Обхождаме масива с данните
        foreach ((array)$dataArr as $dataId => $data) {
            $emailStr = '';
            
            $nRec = new stdClass();
            $nRec->emailId = $emailId;
            $nRec->dataId = $dataId;
            $nRec->data = $data;
            $nRec->state = 'pending';
            
            // Ако са подадени полета, които да се използват за имейли
            if ($emailFieldsArr) {
                
                // Генерира стринг от всички имейли
                foreach ((array)$emailFieldsArr as $name => $type) {
                    if (isset($data[$name])) {
                        $emailStr .= $emailStr ? ', ' . $data[$name] : $data[$name];
                    }
                }
                
                // Добаваме стринга с имейлите
                $nRec->email = $emailStr;
            }
            
            // За всеки нов запис увеличаваме брояча
            $id = self::save($nRec, NULL, 'IGNORE');
            
            if ($id) $cnt++;
        }
        
        return $cnt;
    }
    
    
    /**
     * Връща данните за подадения emailId
     *
     * @param integer $emailId - id на мастер (blast_Emails)
     * @param integer $count - Дали да има ограничени в броя на записите
     *
     * @return array
     */
    public static function getDataArrForEmailId($emailId, $count = NULL)
    {
        $resArr = array();
        
        // Вземаме всички записи, които не са използвани
        $query = self::getQuery();
        $query->where(array("#emailId = '[#1#]'", $emailId));
        $query->where("#state = 'pending'");
        
        // Ако има ограничение
        if ($count) {
            $query->limit = $count;
        }
        
        // Обхождаме всички резултати и ги добавяме в масива
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->data;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща данните за подаденот id
     *
     * @param integer $id
     *
     * @return array
     */
    public static function getDataArr($id)
    {
        $dataArr = self::fetchField($id, 'data');
        
        return (array)$dataArr;
    }
    
    
    /**
     * Маркира като изпратени
     *
     * @param array $idsArr
     */
    public static function markAsSent($dataArr)
    {
        $dataArr = arr::make($dataArr);
        
        // Маркира всички подадени записи, като изпратени
        foreach ((array)$dataArr as $id => $dummy) {
            $nRec = new stdClass();
            $nRec->id = $id;
            $nRec->state = 'sended';
            
            self::save($nRec, NULL, 'UPDATE');
        }
    }
    
    
    /**
     * Променя времето на изпращане и имейла
     *
     * @param array $idsArr
     */
    public static function setTimeAndEmail($idsArr)
    {
        $idsArr = arr::make($idsArr);
        
        // Променя времето и имейла на всички подадени записи
        foreach ((array)$idsArr as $id => $email) {
            $nRec = new stdClass();
            $nRec->id = $id;
            $nRec->sentOn = dt::now();
            $nRec->email = $email;
            
            self::save($nRec, NULL, 'UPDATE');
        }
    }
    
    
    /**
     * След подготвяне на формата за филтриране
     *
     * @param blast_EmailSend $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подреждаме записите, като неизпратените да се по-нагоре
        $data->query->orderBy("state", 'ASC');
        $data->query->orderBy("createdOn", 'DESC');
        $data->query->orderBy("sentOn", 'DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param blast_EmailSend $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // В зависимост от състоянието променяме класа на реда
        if($rec->state == 'sended') {
            $row->ROW_ATTR['class'] .= ' state-closed';
        } else {
            $row->ROW_ATTR['class'] .= ' state-pending';
        }
    }
}
