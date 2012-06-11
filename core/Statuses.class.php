<?php


/**
 * Клас 'core_Statuses' - Статусни съобщения
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Statuses extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Статусни съобщения";
    
    
    /**
     * Добавя съобщение на избрания потребител
     * 
     * @param string  $message  - Съобщение, което ще добавим
     * @param enum    $type     - Типа на съобщението - success, notice, warning, error
     * @param integer $userId   - Потребителя, към когото ще се добавя. Ако не подаден потребител, тогава взема текущия потребител.
     * @param integer $lifeTime - След колко време да се изтрие
     * 
     * @return integer $id - При успешен запис връща id' то на записа
     */
    static function add($text, $type='notice')
    {
        //Очакваме съобщението да не е празен стринг
        expect(str::trim($text), 'Няма въведено съобщение.');
        
        //Всички статус съобщения
        $statusArr = mode::get('statusArr');
        
        //Създаваме обект
        $textObj = new stdClass();
        
        //Записваме текстовата част, типа и времето
        $textObj->statusText = $text;
        $textObj->statusType = $type;
        $textObj->time = time();
        
        //Ако има предишни статуси
        if ($statusArr) {
            
            //Добавяме в края на масива
            array_push($statusArr, $textObj);  
            
            //Записваме новия масив
            mode::setPermanent('statusArr', $statusArr);  
        } else {
            
            //Записваме обекта
            mode::setPermanent('statusArr', array($textObj)); 
        }
    }
    
    
    /**
     * Връща всички статуси на текущия потребител, на които не им е изтекъл lifeTime' а
     * 
     * @return array $resArr - Масив със съобщението и типа на статуса
     */
    static function getStatuses()
    {
        $resArr = array();

        //Времето на последното показване на нотификация
        $lastNotificationTime = Mode::get('lastNotificationTime');
        
        //Масив с всички статуси
        $statusArr = mode::get('statusArr');
        
        $i=0;
        
        if ($statusArr) {
            
            //Докатове има стойност в масива - вземаме първия му елемент
            while ($res=array_shift($statusArr)) {
                
                // Ако все още не е видян
                if ($res->time > $lastNotificationTime) {
                    
                    //Текстовата част
                    $resArr[$i]['statusText'] = $res->statusText;
                    
                    //Типа на статуса
                    $resArr[$i]['statusType'] = $res->statusType;
                    
                    //Увеличаваме ключа на масива с единица
                    $i++;
                }
            }
            
            //Записваме останалите стойности в масива
            mode::setPermanent('statusArr', $resArr);
        }
        
        return $resArr;
    }
    
    
    /**
     * Извлича статусите за текущия потребител и ги добавя в div таг
     * 
     * @return string $res - Всички активни статуси за текущия потребител, групирани в div таг
     */
    static function show_()
    {
        //Всички активни статуси за текущия потребител
        $notifArr = core_Statuses::getStatuses();
        
        //Обикаляме всички статуси
        foreach ($notifArr as $value) {
            
            //Записваме всеки статус в отделен div и класа се взема от типа на статуса
            $res .= "<div class='statuses-{$value['statusType']}'> {$value['statusText']} </div>";
        }

        return $res;
    }
    
    
    /**
     * Екшън, който се използва от ajax'a, за визуализиране на статус съобщенията
     */
    function act_AjaxGetStatuses()
    {
        //Всички статуси за текущия потребител
        $recs = $this->getStatuses();
        
        //Задаваме текущото време
        Mode::setPermanent('lastNotificationTime', time());
        
        //Енкодираме записите в json формат
        $json = json_encode($recs);  
        
        //Извеждаме записити на екрана
        echo $json;
        
        //Прекраряваме изпълнението на кода по нататаък
        die;
    }
}