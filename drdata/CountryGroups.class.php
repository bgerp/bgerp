<?php


/**
 * Клас 'drdata_CountryGroups' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_CountryGroups extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper, plg_RowTools2, plg_Created';
    
    
    /**
     * Заглавие
     */
    var $title = 'Групи държави';
    
    
    /**
     * Кой  може да редактира
     */
    var $canEdit = 'admin';
    
    
    /**
     *
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128, ci)', 'caption=Име');
        $this->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonNameBg)', 'caption=Държави');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща масив с групите, в които се съдържа съответната държава
     * 
     * @param integer|NULL $countryId
     * 
     * @return array
     */
    public static function getGroupsArr($countryId = NULL)
    {
        $query = self::getQuery();
        if (isset($countryId)) {
            $query->likeKeylist('countries', $countryId);
        }
        
        $resArr = $query->fetchAll();
        
        return $resArr;
    }
    
    
    /**
     * Връща общите групи в които участват двете държави
     * 
     * @param integer $countryId1
     * @param integer $countryId2
     * 
     * @return array
     */
    public static function getGroupUnion($countryId1, $countryId2)
    {
        $query = self::getQuery();
        
        $query->likeKeylist('countries', $countryId1);
        $query->likeKeylist('countries', $countryId2);
        
        $resArr = $query->fetchAll();
        
        return $resArr;
    }
}
