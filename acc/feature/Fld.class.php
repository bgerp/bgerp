<?php



/**
 * Енкапсулира признак за групиране на обектите на регистър.
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_feature_Fld
{
    private $mvc;
    private $name;
    
     /**
      * Заглавие
      */
    public $title;
    
    
    /**
     * Конструктор
     */
    function __construct($mvc, $field)
    {
        if (!isset($mvc->fields[$field])) {
            halt("Missing feature field " . cls::getClassName($mvc) . "::{$field}");
        }
        
        $this->mvc = $mvc;
        $this->name = $field;
        $this->title = $mvc->fields[$field]->caption;
    }
    
    
    /**
     * В коя група попада обектът, според този признак?
     *
     * @param int $objectId
     * @return mixed
     */
    function valueOf($objectId)
    {
        $value = $this->mvc->fetchField($objectId, $this->name);
        
        if (value && $this->mvc->fields[$this->name]->type instanceof type_Keylist) {
            $value = '|' . (int)substr($value, 1) . '|';
        }
        
        return $value;
    }
    
    
    /**
     * Вербалното име на група, по която обектите се разделят според този признак.
     *
     * @param mixed $value стойност, която еднозначно идентифицира групата
     * @return string
     */
    function titleOf($group)
    {
        return $this->mvc->getVerbal((object)(array($this->name=>$group)), $this->name);
    }
    
    
    /**
     * Настройва заявка (@see core_Query), така че тя да връща само обектите от зададена група.
     * Използва се при филтриране, т.е. при показване само на обектите от зададена група.
     *
     * @param mixed $value идентификатор на групата
     * @param core_Query $query
     */
    function prepareGroupQuery($group, &$query)
    {
        if ($this->mvc->fields[$this->name]->type instanceof type_Keylist) {
            $query->where("#{$this->name} LIKE '{$group}%'");
        } else {
            $query->where(array("#{$this->name} = '[#1#]'", $group));
        }
    }
}
