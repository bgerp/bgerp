<?php


/**
 * Клас  'type_MenuDate' - Тип за дати
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_AutofillMenu extends type_Varchar
{
    /**
     * Опции за менюто
     */
    private $menuOpt;
    
    
    /**
     * Списък с полета за попълване
     */
    private $namesList;
    
    
    /**
     * Разделител в списъците
     */
    private $separator;
    
    
    public function setMenu($menuOpt, $namesList, $separator = '|')
    {
        $this->menuOpt = $menuOpt;
        $this->namesList = $namesList;
        $this->separator = $separator;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $res = new ET();
        
        if (is_array($this->menuOpt) && count($this->menuOpt)) {
            foreach ($this->menuOpt as $fromTo => $verbal) {
                $opt .= "<option value='${fromTo}'>{$verbal}</options>";
            }
            
            expect($this->namesList);
            
            $res->append("<select class='shortSelect' style='width: 20px;' onchange='
            var values = this.value.split(\"{$this->separator}\");
            var names  = \"{$this->namesList}\".split(\"{$this->separator}\"); 
            var valuesLength = values.length;
            for (var i = 0; i < valuesLength; i++) {
                this.form[names[i]].value = values[i];
            }'>
            <option></option>{$opt}</select>");
        }
        
        return $res;
    }
}
