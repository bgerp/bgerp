<?php



/**
 * Ключ към запис от модел, по който се групира друг модел
 *
 * Понякога даден модел съдържа keylist поле към друг модел (за групиране)
 * Този тип позволява да се избере ключ от  модела за групирането
 * Например: ключ към групите от визитника
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class type_Group extends type_Key
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        expect($base = $this->params['base']);     // Базов модел
        expect($keylist = $this->params['keylist']);     // Името на keylist полето
        $baseMvc = cls::get($base);
        
        expect($mvc = $baseMvc->fields[$keylist]->type->params['mvc'], $baseMvc->fields[$keylist]);
        
        $this->params['mvc'] = $mvc;
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    public function prepareOptions($value = NULL)
    {
        expect($base = $this->params['base']);     // Базов модел
        expect($keylist = $this->params['keylist']);     // Името на keylist полето
        
        Mode::push('text', 'plain');
        
        $mvc = cls::get($this->params['mvc']);
        
        $baseMvc = cls::get($base);
        $baseQuery = $baseMvc->getQuery();
        $baseQuery->show($keylist);
        
        while($baseRec = $baseQuery->fetch()) {
            $arr = keylist::toArray($baseRec->{$keylist});
            
            foreach($arr as $id => $dummy) {
                $groups[$id]++;
            }
        }
        
        if (is_array($groups)) {
            foreach($groups as $id => $cnt) {
                $this->options[$id] = $mvc->getTitleById($id, FALSE) . " ({$cnt})";
            }
        }
        
        Mode::pop('text');
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return parent::fromVerbal_($value);
    }
}