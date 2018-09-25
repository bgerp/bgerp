<?php


/**
 * Токени за плащане чрез ePay.bg
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class epay_Tokens extends core_Manager
{
    

    /**
     * Заглавие на мениджъра
     */
    public $title = 'Токени за плащане чрез ePay.bg';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Токен за плащане чрез ePay.bg';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'token,initiatorId=Източник,createdOn=Създадено на';
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('token', 'varchar', 'caption=Токен');
        $this->FLD('initiatorClassId', 'class', 'caption=Инициатор->Клас');
        $this->FLD('initiatorId', 'int', 'caption=Инициатор->Ид,tdClass=leftCol');
        
        $this->setDbUnique('token');
        $this->setDbIndex('initiatorClassId,initiatorId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->initiatorId = cls::get($rec->initiatorClassId)->getHyperlink($rec->initiatorId, true);
    }
    
    
    
    /**
     * Форсира нов токен за обекта-инициатор
     * 
     * @param mixed $initiatorClass - клас инициатор
     * @param int $initiatorId      - ид на обекта от класа инициатор
     * @return string               - генерираният токен
     */
    public static function force($initiatorClass, $initiatorId)
    {
        // Има ли токен за този обект
        $initiatorClassId = cls::get($initiatorClass)->getClassId();
        $rec = self::fetch("#initiatorClassId = {$initiatorClassId} AND #initiatorId = {$initiatorId}");
       
        // Ако няма генерира се нов уникален токен
        if(empty($rec)){
            $token = self::getNewToken($initiatorClassId, $initiatorId);
            
            $rec = (object)array('token' => $token, 'initiatorClassId' => $initiatorClassId, 'initiatorId' => $initiatorId);
            self::save($rec);
        }
        
        return $rec->token;
    }
    
    
    /**
     * Генерира нов уникален токен
     *
     * @param mixed $initiatorClassId - клас инициатор
     * @param int $initiatorId        - ид на обекта от класа инициатор
     * @return string $token          - генерираният токен
     */
    public static function getNewToken($initiatorClassId, $initiatorId)
    {
        // Генериране на токен
        $initiatorClassId = cls::get($initiatorClassId)->getClassId();
        $token = $initiatorClassId . str::getRand('A') . $initiatorId . str::getRand('DDD');
       
        // Докато не се получи уникален токен, се генерира нов
        while(self::fetch("#token = '{$token}'")){
            $token = $initiatorClassId . str::getRand('A') . $initiatorId . str::getRand('DDD');
        }
        
        return $token;
    }
}