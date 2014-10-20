<?php 

/**
 * Помощен клас 'AskForSave', проверява дали въведен запис
 * съществува в даден модел, ако не показва напомняне с линк към
 * формата за добавяне на този запис в модела
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimoc <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_AskForSave
{
    /**
     * В кой модел ще проверяваме, и ще записваме
     */
    public $inClass;
    
    /**
     * Кое поле ще проверяваме
     */
    public $field;
    
    /**
     * Коя стойност ще записваме в модела
     */
    public $value;
    
    /**
     *  параметри нужни за url заявката
     */
    public $params;
    
    /**
     * Дали записа трябва да бъде записан
     */
    public $hasToSave = TRUE;
    
    
    /**
     * Конструктор на класа
     * @param string $inClass - модел, в който ще записваме
     * @param string $field - поле, в което ще записваме
     * @param string $value - стойност за записване
     * @param array  $params - параметри на заявката
     */
    public function __construct($inClass, $field, $value, $params = array())
    {
        // Попълваме стойностите на класа
        $this->inClass = $inClass;
        $this->field = $field;
        $this->value = $value;
        $this->params = $params;
        
        // Проверяваме дали в указания модел в указаното поле има подадената стойност
        // ако я има, то не е нужно напомняне и връщаме FALSE
        $class = $this->inClass;
        
        if($rec = $class::fetch("#{$this->field} = '{$this->value}'")) {
            $this->hasToSave = FALSE;
        }
    }
    
    
    /**
     * Функция която връща шаблон съдържащ съобщението за напомняне и линк
     * към модела, в който ще добавяме
     * @return core_ET $layout - Шаблона със съобщението и линка
     */
    function placeReminder()
    {
        // Трябва да $this->hasToSave да е TRUE
        expect($this->hasToSave, 'Има вече такъв запис');
        
        // Адреса за записване в модела
        $url = array($this->inClass,
            'Add',
            $this->field => $this->value,
            'ret_url' => TRUE, );
        
        // Добавяме към адреса, параметрите за заявката
        $url = array_merge($url, $this->params);
        
        // Линк към адреса
        $link = ht::createLink(tr('тук'), $url);
        
        // Съобщението за напомняне
        $reminder = tr("{$this->field} не фигурира в системата! За да го добавите натиснете ");
        $reminder .= $link;
        
        // Шаблона в който ще го поставим
        $layout = new ET("<div id = 'doc-reminder'>[#reminder#]</div>");
        $layout->replace($reminder, 'reminder');
        
        // Връщаме шаблона
        return $layout;
    }
}