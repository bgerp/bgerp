<?php
/**
 * Помощен клас-имплементация на интерфейса acc_RegisterIntf за класа sales_Sales 
 * 
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class sales_RegisterImpl
{
    /**
     * 
     * @var sales_Sales
     */
    public $class;
    
    
    /**
     * Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @return stdClass запис за модела acc_Items:
     *
     * o num
     * o title
     * o uomId (ако има)
     * o features - списък от признаци за групиране
     */
   public function getItemRec($saleId)
    {
        $rec = $this->class->fetch($saleId);
        
        return (object)array(
            'num' => $rec->id,
            'title' => "Продажба №{$rec->id} / " . $this->class->getVerbal($rec, 'date'),
            'uomId' => NULL,
            'features' => NULL,
        );
    }
    
    
    /**
     * Хипервръзка към този обект
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @return mixed string или ET (@see ht::createLink())
     */
    function getLinkToObj($objectId)
    {
        
    }
    
    
    /**
     * Нотифицира регистъра, че обекта е станал (или престанал да бъде) перо
     *
     * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
     * @param boolean $inUse true - обекта е перо; false - обекта не е перо
     */
    function itemInUse($objectId, $inUse)
    {
        // @TODO
    }
    
    
    /**
     * Имат ли обектите на регистъра размерност?
     *
     * @return boolean
     */
    static function isDimensional()
    {
        return false;
    }
    
}