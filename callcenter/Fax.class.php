<?php 

/**
 * Мениджър за записване на изпратените факсове
 *
 * @category  bgerp
 * @package   callcenter
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class callcenter_Fax extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Изпратени факсове';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
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
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'callcenter_Wrapper, plg_RowTools2, plg_Printing, plg_Sorting, plg_RefreshRows, plg_Created, callcenter_ListOperationsPlg';
    
    
    public $refreshRowsTime = 15000;
    
    
    public $listFields = 'faxNumData, faxNum, cid, createdOn=Изпратен->На, createdBy=Изпратен->От';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('faxNum', 'drdata_PhoneType', 'caption=Получател->Номер');
        $this->FLD('faxNumData', 'key(mvc=callcenter_Numbers)', 'caption=Получател->Контакт');
        $this->FLD('cid', 'key(mvc=doc_Containers)', 'caption=Документ');
    }
    
    
    /**
     * Записва изпращането на факса
     *
     * @param int $faxNum - Факс номера, до който се праща
     * @param int $cid    - id на документа
     */
    public static function saveSend($faxNum, $cid)
    {
        // Вземаме целия номер
        $faxNum = drdata_PhoneType::getNumberStr($faxNum, 0);
        
        // Вземаме записа за номера
        $extRecArr = callcenter_Numbers::getRecForNum($faxNum);
        
        // Създаваме записа
        $rec = new stdClass();
        $rec->faxNum = $faxNum;
        $rec->faxNumData = $extRecArr[0]->id;
        $rec->cid = $cid;
        
        static::save($rec);
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
        // Добавяме стил за телефони
        $row->faxNum = "<div class='fax crm-icon'>" . $row->faxNum . '</div>';
        
        // Ако има данни за търсещия
        if ($rec->faxNumData) {
            
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->faxNumData);
            
            // Вербалния запис
            $externalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($externalNumRow->contragent) {
                
                // Флаг, за да отбележим, че има данни
                $haveExternalData = true;
                
                // Добавяме данните
                $row->faxNumData = $externalNumRow->contragent;
            }
        }
        
        // Ако флага не е дигнат
        if (!$haveExternalData) {
            
            // Ако има номер
            if ($rec->faxNum) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                
                // Уникално id
                $uniqId = $rec->id . 'faxTo';
                
                // Добавяме линка
                callcenter_Talks::getTemplateForAddNum($row->_rowTools, $rec->faxNum, $uniqId, false, 'fax');
            }
        }
        
        // Ако има потребител
        if ($rec->createdBy) {
            
            // Създаваме линк към профила му
            $row->createdBy = crm_Profiles::createLink($rec->createdBy);
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Дива за разстояние
            $div = "<div style='margin-top:5px;'>";
            
            // Добавяме данните към номерата
            $row->faxNum .= $div. $row->faxNumData . '</div>';
            
            // Обединяваме създадено на и от
            $row->created = $row->createdBy . $div . dt::mysql2verbal($rec->createdOn, 'smartTime') . '</div>';
        }
        
        // Ако има id на документ
        if ($rec->cid) {
            
            // Линк към сингъла на докуемнта
            $row->cid = doc_Containers::getLinkForSingle($rec->cid);
        }
    }
    
    
    /**
     *
     * Enter description here ...
     *
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    public static function on_AfterPrepareListFields($mvc, $data)
    {
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Променяме полетата, които ще се показват
            $data->listFields = arr::make('faxNum=Получател, cid=Документ, created=Изпратен');
        }
    }
    
    
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Поле за търсене по номера
        $data->listFilter->FNC('number', 'drdata_PhoneType', 'caption=Номер,input,silent, recently');
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent,autoFilter');
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'number,usersSearch';
        
        $data->listFilter->input('number,usersSearch', 'silent');
        
        // Ако не е избран потребител по подразбиране
        if (!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if ($filter = $data->listFilter->rec) {
            
            // Ако се търси по номера
            if ($number = $filter->number) {
                
                // Премахваме нулите и + от началото на номера
                $number = ltrim($number, '0+');
                
                // Търсим във факсовете
                $data->query->where(array("#faxNum LIKE '%[#1#]'", $number));
            }
            
            // Ако филтъра е по потребители
            if ($filter->usersSearch) {
                
                // Масив с потребителите
                $userSearchArr = type_Keylist::toArray($filter->usersSearch);
                
                // Показваме само на съответните потребители потребителя
                $data->query->orWhereArr('createdBy', $userSearchArr);
                
                // Ако се търси по всички и има права admin или ceo
                if ((strpos($filter->usersSearch, '|-1|') !== false) && (haveRole('ceo, admin'))) {
                    
                    // Показваме и празните резултати
                    $data->query->orWhere('#createdBy IS NULL');
                }
            }
        }
    }
    
    
    /**
     * Обновява записите за съответния номер
     *
     * @param string $numStr - Номера
     */
    public static function updateRecsForNum($numStr)
    {
        // Вземаме последния запис за съответния номер
        $nRecArr = callcenter_Numbers::getRecForNum($numStr);
        
        // Вземаме всички записи за съответния номер
        $query = static::getQuery();
        $query->where(array("#faxNum = '[#1#]'", $numStr));
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            $rec->faxNumData = $nRecArr[0]->id;
            
            // Записваме
            static::save($rec);
        }
    }
}
