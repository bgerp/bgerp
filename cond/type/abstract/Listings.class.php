<?php


/**
 * Тип за параметър 'Листвани артикули'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Листване
 */
abstract class cond_type_abstract_Listings extends cond_type_abstract_Proto
{
    /**
     * Мета свойства
     *
     * @string canBuy|canSell
     */
    protected $meta;
    
    
    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $options = array();
        
        $lQuery = cat_Listings::getQuery();
        $lQuery->where("#state = 'active'");
        $lQuery->where("#type = '{$this->meta}'");
        
        if(isset($domainClass) && isset($domainId) && cls::haveInterface('crm_ContragentAccRegIntf', $domainClass)){
            $folderId = cls::get($domainClass)->forceCoverAndFolder($domainId);
            $lQuery->where("#isPublic = 'yes' OR #folderId = {$folderId}");
        } else {
            $lQuery->where("#isPublic = 'yes'");
        }
        
        while ($rec = $lQuery->fetch()) {
            $options[$rec->id] = $rec->title;
        }
        
        $Type = core_Type::getByName('key(mvc=cat_Listings,select=title,makeLink)');
        $options = countR($options) ? array('' => '') + $options : $options;
        $Type->options = $options;
        
        return $Type;
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = null)
    {
        return false;
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        return cat_Listings::getHyperlink($value, TRUE);
    }
}
