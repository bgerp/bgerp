<?php 


/**
 * Абониране за бюлетина
 *
 * @category  bgerp
 * @package   marketing
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Bulletin extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Абонати за бюлетина";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'ceo, marketing';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'ceo, marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, marketing';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo, marketing';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, marketing';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'ceo, marketing';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'marketing_Wrapper,  plg_RowTools, plg_Created, plg_Sorting, plg_Search';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'bgerp_PersonalizationSourceIntf';
    
    
    /**
     * 
     */
    public $searchFields = 'email, name, company';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        $this->FLD('name', 'varchar(128)', 'caption=Имена, oldFieldName=names');
        $this->FLD('company', 'varchar(128)', 'caption=Фирма');
        $this->FLD('ip', 'ip', 'caption=IP, input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър, input=none');
        
        $this->setDbUnique('email');
    }
    
    
    /**
     * 
     * 
     * @param core_LoginLog $mvc
     * @param object $row
     * @param object $rec
     * @param array $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Оцветяваме BRID
    	$row->brid = core_Browser::getLink($rec->brid);
    	
        if ($rec->ip) {
        	// Декорираме IP-то
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * 
     * @param marketing_Bulletin $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
//        if (blast_Emails::haveRightFor('add') && $mvc->fetch("1=1")) {
//            $data->toolbar->addBtn('Циркулярен имейл', array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($mvc)),
//            'id=btnEmails','ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
//        }
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     * 
     * @param integer $id
     * 
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $resArr = $this->getPersonalizationOptions();
        
        return $resArr;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас, които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $userId
     * @param integer $folderId
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array($this->title);
        
        return $resArr;
    }

    
    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        $resArr = array();
        $resArr['email'] = cls::get('type_Email');
        $resArr['person'] = cls::get('type_Varchar');
        $resArr['company'] = cls::get('type_Varchar');
        
        return $resArr;
    }
    
    
    /**
     * Дали потребителя може да използва дадения източник на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $userId
     *
     * @return boolean
     */
    public function canUsePersonalization($id, $userId = NULL)
    {
        // Всеки който има права до листване на модела
        if ($this->haveRightFor('list', $id, $userId)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string|object $id
     * @param boolean $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = TRUE)
    {
        
        return $this->title;
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param integer $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        $query = $this->getQuery();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = array('email' => $rec->email, 'person' => $rec->name, 'company' => $rec->company);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        // Създаваме линк към сингъла листа
        $title = $this->getPersonalizationTitle($id, TRUE);
        $link = ht::createLink($title, array($this, 'list'));
        
        return $link;
    }
    
    
    /**
     * 
     * 
     * @param marketing_Bulletin $mvc
     * @param object $data
     * @param object $res
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->query->orderBy('createdOn', 'DESC');
    }
}
