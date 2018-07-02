<?php



/**
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_PersonalizationSourceIntf
{
    
    
    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     *
     * @param integer $id
     *
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        return $this->class->getPersonalizationDescr($id);
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     *
     * @param integer $id
     * @param integer $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        return $this->class->getPresonalizationArr($id, $limit);
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     *
     * @param integer $id
     * @param boolean $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = false)
    {
        return $this->class->getPersonalizationTitle($id, $verbal);
    }
    
    
    /**
     * Връща TRUE или FALSE дали потребителя може да използва дадения източник на персонализация
     *
     * @param integer $id
     * @param integer $userId
     *
     * @return boolean
     */
    public function canUsePersonalization($id, $userId = null)
    {
        return $this->class->canUsePersonalization($id, $userId);
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * които са достъпни за посочения потребител
     *
     * @param integer $userId
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = null)
    {
        return $this->class->getPersonalizationOptions($userId);
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     *
     * @param integer $srcId
     *
     * @return array
     */
    public function getPersonalizationOptionsForId($srcId)
    {
        return $this->class->getPersonalizationOptionsForId($srcId);
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     *
     * @param integer $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        return $this->class->getPersonalizationSrcLink($id);
    }
    
    
    /**
     * Връща езика за източника на персонализация
     *
     * @param integer $id
     *
     * @return string
     */
    public function getPersonalizationLg($id)
    {
        return $this->class->getPersonalizationLg($id);
    }
}
