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
    public $listFields = 'id,token,initiatorId=Източник,createdOn=Създаване';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('token', 'varchar', 'caption=Токен');
        $this->FLD('initiatorClassId', 'class(interface=eshop_InitiatorPaymentIntf)', 'caption=Инициатор->Клас');
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
    public static function getPaymentReason($initiatorClass, $initiatorId)
    {
        $token = self::force($initiatorClass, $initiatorId);
        $reason = "Плащане по поръчка #{$token}";
        
        return $reason;
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
            $token = self::getNewToken();
            $rec = (object)array('token' => $token, 'initiatorClassId' => $initiatorClassId, 'initiatorId' => $initiatorId);
            self::save($rec);
        }
        
        return $rec->token;
    }
    
    
    /**
     * Връща нов неизползван досега токен
     *
     * @return string $token - генерираният токен
     */
    public static function getNewToken()
    {
        // Докато не се получи уникален токен, се генерира нов
        $token = self::generate();
        while(self::fetch("#token = '{$token}'")){
            $token = self::generate();
        }
        
        return $token;
    }
    
    
    /**
     * Генериане на произволен токен
     * 
     * @return $token - генерианият токен
     */
    public static function generate()
    {
        $token = str::getRand('AAA') . str::getRand('########');
        
        return $token;
    }
    
    
    /**
     * Търси съществуващ токен в стринга
     * 
     * @param string $string - стринг
     * 
     * @return false|array  - информация за токена или false ако не е намерен валиден токен
     *          ['token']            - токен
     *          ['initiatorClassId'] - клас инициатор
     *          ['initiatorId']      - ид на инициатора
     */
    public static function findToken($string)
    {
        $string = trim($string);
        
        $matches = array();
        if(preg_match('/(#([A-Z]{3})([0-9]{8}))/', $string, $matches)){
            if(isset($matches[1])){
                $token = trim($matches[1], '#');
                if($tokenRec = self::fetch(array("#token = '[#1#]'", $token), 'token,initiatorClassId,initiatorId')){
                    
                    return array('token' => $tokenRec->token, 'initiatorClassId' => $tokenRec->initiatorClassId, 'initiatorId' => $tokenRec->initiatorId);
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * Изтриване по разпоисание на токените към несъществуващи обекти
     */
    public function cron_DeleteOldTokens()
    {
        $query = self::getQuery();
        while($rec = $query->fetch()){
            $delete = false;
            if(empty($rec->initiatorClassId) || empty($rec->initiatorId) || (isset($rec->initiatorClassId) && !cls::load($rec->initiatorClassId, true))){
                $delete = true;
            } elseif(isset($rec->initiatorClassId) && isset($rec->initiatorId)){
                $initId = cls::get($rec->initiatorClassId)->fetchField($rec->initiatorId);
                if(empty($initId)){
                    $delete = true;
                }
            }
            
            if($delete === true){
                self::delete($rec->id);
            }
        }
    }
}