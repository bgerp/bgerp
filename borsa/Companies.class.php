<?php 

/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Companies extends core_Manager
{
    
    /**
     * Заглавие на модела
     */
    public $title = 'Фирми';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'borsa, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'borsa, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'borsa, ceo';
    
    
//     /**
//      * Кой може да променя състоянието на документите
//      *
//      * @see plg_State2
//      */
//     public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper';
//     public $loadList = 'borsa_Wrapper, plg_RowTools2, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//     public $searchFields = 'pattern';

    
    
    public function description()
    {
        $this->FLD('companyId', 'key(mvc=crm_Companies, allowEmpty, restrictViewAccess=no)', 'caption=Фирма,removeAndRefreshForm=email,mandatory,silent');
        $this->FLD('email', 'email', 'caption=Имейл,mandatory');
        
        $this->FNC('url', 'url');
        $this->FNC('name', 'varchar');
        
        $this->setDbUnique('companyId,email');
    }
    
    function on_CalcUrl($mvc, $rec)
    {
        $rec->url = $mvc->getAccessUrl('borsa_Lots', $rec);
    }
    
    function on_CalcName($mvc, $rec)
    {
        if ($rec->companyId) {
            $rec->name = crm_Companies::getVerbal($rec->companyId, 'name');
        }
    }
    
    
    /**
     * 
     * 
     * @param string $className
     * @param stdClass $rec
     * @param integer $lifeTime
     * 
     * @return string
     */
    protected static function getAccessUrl($className, $rec, $lifeTime = 604800)
    {
        
        return core_Forwards::getURL($className, 'openBid', array('id' => $rec->id, 'companyId' => $rec->companyId, 'email' => $rec->email), $lifeTime);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($data->form->rec->companyId) {
            $emailsArr = type_Emails::toArray(crm_Companies::fetchField($data->form->rec->companyId, 'email'));
            
            if ($emailsArr[0]) {
                $data->form->setDefault('email', $emailsArr[0]);
            }
        }
    }
}
