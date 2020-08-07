<?php 


/**
 * Модел за офиси на speedy
 *
 * @category  bgerp
 * @package   speedy
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_Offices extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Офиси на спиди';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id,extName";
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('num', 'int', 'caption=Код');
        $this->FNC('extName', 'varchar', 'caption=Наименование');
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('address', 'varchar', 'caption=Адрес');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Изчисление на пълното наименование на офиса
     */
    protected static function on_CalcExtName($mvc, $rec)
    {
        $rec->extName = "[{$rec->num}] {$rec->name}";
        if(!empty($rec->address)){
            $rec->extName .= "; {$rec->address}";
        }
    }
    
    
    /**
     * Кои са достъпните офиси за избор
     * 
     * @return array $options
     */
    public static function getAvailable()
    {
        $options = array();
        $query = self::getQuery();
        while($rec = $query->fetch()){
            $options[$rec->id] = $rec->extName;
        }
        
        return $options;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'speedy/data/Offices.csv';
        
        $fields = array(
            0 => 'num',
            1 => 'name',
            2 => 'address',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    function act_Test11111111()
    {
        // Клиентска конфигурация.
        $clientConfiguration = new StdClass();
        $clientConfiguration->userName = '999389';                 // Конфигурирайте името на потребителя преодставен за вас от Speedy
        $clientConfiguration->userPassword = '6685946573';             // Конфигурирайте паролата за потребителя преодставен за вас от Speedy
        $clientConfiguration->arrEnabledServices = array(0=>505);  // Конфигурирайте ограничен списък от услуги на Speedy, с които клиентът ще работи
        $clientConfiguration->contactName='Ivelin Dimov';                // Конфигурирайте име за контакт на подателя при откриване на товарителници и заявки за куриер
        $clientConfiguration->contactPhone='0888 888 888';         
        
        
        //try {
            
            header("Content-Type: text/html; charset=utf-8");
            
            // Иницализация на времевата зона.
            // Препоръчва се параметрите и аргументите от тип datetime да са форматирани във времевата зона на Спиди,
            // поради специфики при определяне на датата и времето в някои от подаваните стойности
            if (function_exists("date_default_timezone_set")) {
                date_default_timezone_set(Util::SPEEDY_TIME_ZONE);
                $timeZone = date_default_timezone_get();
            } else {
                putenv("TZ=".Util::SPEEDY_TIME_ZONE);
                $timeZone = getenv("TZ");
            }
            
            $eps = new EPSFacade(new EPSSOAPInterfaceImpl(), $clientConfiguration->userName,  $clientConfiguration->userPassword);
            
            echo "Установяване на сесия [login]<br>";
            $resultLogin = $eps->getResultLogin();
            
            
            bp($eps, $resultLogin);
            
            
       // } catch(Exception $e){
            
       // }
        
        
        bp($clientConfiguration);
    }
}