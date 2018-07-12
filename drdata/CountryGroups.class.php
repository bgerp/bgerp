<?php


/**
 * Клас 'drdata_CountryGroups' -
 *
 *
 * @category  vendors
 * @package   drdata
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class drdata_CountryGroups extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_RowTools2, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Групи държави';
    
    
    /**
     * Кой  може да редактира
     */
    public $canEdit = 'admin';
    
    
    public $canAdd = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128, ci)', 'caption=Име');
        $this->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonNameBg)', 'caption=Държави');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща масив с групите, в които се съдържа съответната държава
     *
     * @param int|NULL $countryId
     *
     * @return array
     */
    public static function getGroupsArr($countryId = null)
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
     * @param int $countryId1
     * @param int $countryId2
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
