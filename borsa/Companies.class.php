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
    
    
    /**
     * 
     */
    public $searchFields = 'companyId, email';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Created, plg_Modified, plg_RowTools2, plg_Sorting, plg_Search';
    
    
    public function description()
    {
        $this->FLD('companyId', 'key2(mvc=crm_Companies, select=name, allowEmpty, restrictViewAccess=no)', 'caption=Фирма,removeAndRefreshForm=email,mandatory,silent,class=w100');
        $this->FLD('email', 'email', 'caption=Имейл,mandatory');
        $this->FLD('allowedLots', 'keylist(mvc=borsa_Lots,select=productName,allowEmpty)', 'caption=Търгове');
        
        $this->FNC('url', 'url');
        $this->FNC('name', 'varchar');
        
        $this->setDbUnique('companyId,email');
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по num
        $data->query->orderBy('modifiedOn');
    }
    
    
    /**
     * Попълване на полето за URL
     * 
     * @param borsa_Companies $mvc
     * @param stdClass $rec
     */
    function on_CalcUrl($mvc, $rec)
    {
        $rec->url = $mvc->getAccessUrl('borsa_Lots', $rec);
    }
    
    
    /**
     * Задаване на полето name
     * 
     * @param borsa_Companies $mvc
     * @param stdClass $rec
     */
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
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->rec->email && $form->isSubmitted()) {
            if ($form->rec->id) {
                $haveRec = $mvc->fetch(array("#email = '[#1#]' AND #id != '[#1#]'", $form->rec->email, $form->rec->id));
            } else {
                $haveRec = $mvc->fetch(array("#email = '[#1#]'", $form->rec->email));
            }
            
            if ($haveRec) {
                $form->setWarning('email', 'Вече съществува фирма с този имейл');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->companyId) {
            if (crm_Companies::haveRightFor('single', $rec->companyId)) {
                $row->companyId = crm_Companies::getLinkToSingle($rec->companyId, 'name');
            }
        }
    }
}
