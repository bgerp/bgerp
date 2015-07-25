<?php


/**
 * Детайл с действия към логическите блокове
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class  sens2_ScriptActions extends core_Detail
{
    
    public $oldClassName = 'sens2_LogicDetails';

    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_RowTools, sens2_Wrapper, plg_State';
                      
    
    /**
     * Заглавие
     */
    var $title = 'Редове към Логическите блокове';
    
    var $singleTitle = 'Действие';

    /**
     * Права за писане
     */
    var $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    

    /**
     * Ключ към матера
     */
    var $masterKey = 'scriptId';
    

    var $currentTab = 'Логика';

    var $listFields = 'order,action';

    var $rowToolsField = 'order';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('scriptId', 'key(mvc=sens2_Scripts,title=name)', 'caption=Блок,column=none,silent,oldFieldName=logicId');
        $this->FLD('action', 'class(interface=sens2_ScriptActionIntf, select=title, allowEmpty)', 'caption=Действие,mandatory,silent,refreshForm');
        $this->FLD('order', 'int', 'caption=Ред №');
        $this->FLD('state', 'enum(active,closed,stopped)', 'caption=Състояние');

        $this->FLD('data', 'blob(serialize)', 'caption=Данни,input=none');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $rec =  &$form->rec;
 
        if($rec->id) {
            $form->setReadOnly('action');
            $data = (array) self::fetch($rec->id)->data;
            if(is_array($data)) {  
                foreach($data as $key => $value) {
                    $rec->{$key} = $value;
                }
            }
        }
 
        if($rec->action) {
            $action = cls::get($rec->action);
            $action->prepareActionForm($form);
        } else {
            $form->setField('order', 'input=none');
        }

    }


    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->rec->action && !$form->rec->order) {
            $query = $mvc->getQuery();
            $query->orderBy('#order', 'DESC');
            $query->limit(1);
            $maxOrder = (int) $query->fetch("#scriptId = {$form->rec->scriptId}")->order;
            $form->setDefault('order', round(($maxOrder+1)/10)*10 + 10);
        }
        if($form->isSubmitted() && $form->rec->action) {
            $action = cls::get($form->rec->action);
            $action->checkActionForm($form);
            if(!$form->gotErrors()) {
                $dataFields = array_keys($form->selectFields("(#input == 'input' || #input == '') && !#notData"));
                $form->rec->data = new stdClass();
                foreach($dataFields as $field) {
                    $form->rec->data->{$field} = $form->rec->{$field};
                }
            }
         }
    }


    /**
     *
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#order", 'ASC');
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        $action = cls::get($rec->action);
        
        $rec->data->scriptId = $rec->scriptId;

        $row->action = "<div style='font-family: Courier New,monospace !important; font-size:0.8em;'>" . $action->toVerbal($rec->data) . "</div>";
    }


    /**
     * Изпълнява указания скрипт
     */
    public static function runScript($scriptId)
    {
        $query = self::getQuery();
        while($rec = $query->fetch("#scriptId = {$scriptId}")) {
            $action = cls::get($rec->action);
            $rec->data->scriptId = $rec->scriptId;
            $exState = $rec->state;
            $rec->state = $action->run($rec->data);
            if($rec->state != $exState) {
                self::save($rec, 'state');
            }
        }
    }




}
