<?php
/**
 * Master на профили в Zontromat
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Профили в Zontromat
 */
class ztm_Profiles extends core_Master
{
    public $title = 'Профили в Zontromat';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'ztm_ProfileDefaults';    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
    public $listFields = 'name, description';
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име');
        $this->FLD('description', 'richtext', 'caption=Описание');
        
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
    }
    
    
    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        
        $data->toolbar->addBtn('Изход', array('ztm_Profiles','ret_url' => true));
        
        if (ztm_ProfileDefaults::haveRightFor('add')) {
            $data->toolbar->addBtn('Добавяне на Регистър', array(ztm_ProfileDefaults, 'add', 'profileId' => $data->rec->id, 'ret_url' => true),
                                'order=15,title=Добавяне на Регистър,ef_icon = img/16/shopping.png');
        }
    }
    
    public static function getDefaultResponse($profileId)
    {
        $dArr = array();
        $dQuery = ztm_ProfileDefaults::getQuery();
        $dQuery->where("#profileId = {$profileId}");
        $dQuery->show('registerId,value');
        while($dRec = $dQuery->fetch()){
            $dArr[$dRec->registerId] = $dRec->value;
        }
        
        $res = array();
        $query = ztm_Registers::getQuery();
        
        while($rec = $query->fetch()){
            $default = $rec->default;
            if(array_key_exists($rec->id, $dArr)){
                $default = $dArr[$rec->id];
            }
            
            $res[$rec->name] = $default;
        }
        
        return (object)$res;
    }
    
}