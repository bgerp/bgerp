<?php 


/**
 * Мениджър за записване на изпратените факсове
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Fax extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Изпратени факсове';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
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
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Sorting, plg_RefreshRows, plg_Created, callcenter_ListOperationsPlg';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * 
     */
    var $listFields = 'faxNumData, faxNum, cid, createdOn=Изпратен->На, createdBy=Изпратен->От';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('faxNum', 'drdata_PhoneType', 'caption=Получател->Номер');
        $this->FLD('faxNumData', 'key(mvc=callcenter_Numbers)', 'caption=Получател->Контакт');
        $this->FLD('cid', 'key(mvc=doc_Containers)', 'caption=Документ');
    }
    
    
    /**
     * Записва изпращането на факса
     * 
     * @param integer $faxNum - Факс номера, до който се праща
     * @param integer $cid - id на документа
     */
    static function saveSend($faxNum, $cid)
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
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Добавяме стил за телефони        
        $row->faxNum = "<div class='fax crm-icon'>" . $row->faxNum . "</div>";
        
        // Ако има данни за търсещия
        if ($rec->faxNumData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->faxNumData);
            
            // Вербалния запис
            $externalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($externalNumRow->contragent) {
                
                // Флаг, за да отбележим, че има данни
                $haveExternalData = TRUE;
                
                // Добавяме данните
                $row->faxNumData = $externalNumRow->contragent;
            }
        } 
        
        // Ако флага не е дигнат
        if (!$haveExternalData) {
            
            // Ако има номер
            if ($rec->faxNum) {
                
                // Уникално id
                $uniqId = $rec->id . 'faxTo';
                
                // Добавяме линка
                $row->faxNumData = static::getTemplateForAddNum($rec->faxNum, $uniqId);
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
            $row->faxNum .=  $div. $row->faxNumData . "</div>";
            
            // Обединяваме създадено на и от
            $row->created = $row->createdBy . $div . dt::mysql2verbal($rec->createdOn, 'smartTime') . "</div>";
        }
        
        // Ако има id на документ
        if ($rec->cid) {
            
            // Линк към сингъла на докуемнта
            $row->cid = doc_Containers::getLinkForSingle($rec->cid);
        }
    }
    
    
    /**
     * Връща стринг с линкове за добавяне на номера във фирма, лица или номера
     * 
     * @param string $num - Номера, за който се отнася
     * @param string $uniqId - Уникално id
     * 
     * @return string - Тага за заместване
     */
    static function getTemplateForAddNum($num, $uniqId)
    {
        // Аттрибути за стилове 
        $companiesAttr['title'] = tr('Нова фирма');
        
        // Икона на фирмите
        $companiesImg = "<img src=" . sbf('img/16/office-building-add.png') . " width='16' height='16'>";
        
        // Добавяме линк към създаване на фирми
        $text = ht::createLink($companiesImg, array('crm_Companies', 'add', 'fax' => $num, 'ret_url' => TRUE), FALSE, $companiesAttr);
        
        // Аттрибути за стилове 
        $personsAttr['title'] = tr('Ново лице');
        
        // Икона на изображенията
        $personsImg = "<img src=" . sbf('img/16/vcard-add.png') . " width='16' height='16'>";
        
        // Добавяме линк към създаване на лица
        $text .= " | ". ht::createLink($personsImg, array('crm_Persons', 'add', 'fax' => $num, 'ret_url' => TRUE), FALSE, $personsAttr);
        
        // Дали да се показва или не
        $visibility = (mode::is('screenMode', 'narrow')) ? 'visible' : 'hidden';
        
        // Ако сме в мобилен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Не се добавя JS
            $res = "<div id='{$uniqId}'>{$text}</div>";
        } else {
            
            // Ако не сме в мобилен режим
            
            // Скриваме полето и добавяме JS за показване
            $res = "<div onmouseover=\"changeVisibility('{$uniqId}', 'visible');\" onmouseout=\"changeVisibility('{$uniqId}', 'hidden');\">
        		<div style='visibility:hidden;' id='{$uniqId}'>{$text}</div></div>";
        }
        
        return $res;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Променяме полетата, които ще се показват
            $data->listFields = arr::make('faxNum=Получател, cid=Документ, created=Изпратен');
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Поле за търсене по номера
        $data->listFilter->FNC('number', 'drdata_PhoneType', 'caption=Номер,input,silent, recently');
        
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent,refreshForm');
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'number,usersSearch';
        
        $data->listFilter->input('number,usersSearch', 'silent');
        
    	// Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако се търси по номера
            if ($number = $filter->number) {
                
                // Премахваме нулите и + от началото на номера
                $number = ltrim($number, '0+');
                
                // Търсим във факсовете
                $data->query->where(array("#faxNum LIKE '%[#1#]'", $number));
            }
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
                // Масив с потребителите
                $userSearchArr = type_Keylist::toArray($filter->usersSearch);
                
                // Показваме само на съответните потребители потребителя
    			$data->query->orWhereArr('createdBy', $userSearchArr);
    			
  			    // Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    
    			    // Показваме и празните резултати 
                    $data->query->orWhere("#createdBy IS NULL");
                }
    		}
        }
    }
    
    
	/**
     * Обновява записите за съответния номер
     * 
     * @param string $numStr - Номера
     */
    static function updateRecsForNum($numStr)
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