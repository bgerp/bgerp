<?php


/**
 * Клас 'blast_Lists' - Списъци за масово разпращане
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Lists extends core_Master
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper,plg_RowTools,doc_DocumentPlg';
    
    
    /**
     * Заглавие
     */
    var $title = "Списъци за масово разпращане";
    
    

    //var $listFields = 'id,title,type=Тип,inCharge=Отговорник,threads=Нишки,last=Последно';

    /**
     * Кой има право да чете?
     */
    var $canRead = 'blast,admin';
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'blast,admin';
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,admin';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Списък за масово разпращане';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'blast_ListDetails';
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/application_view_list.png';


    /**
     * Абревиатура
     */
    var $abbr = 'BLS';


    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'blast/tpl/SingleLayoutLists.shtml';
    
    
    /**
     * 
     */
    function description()
    {
        // Информация за папката
        $this->FLD('title' , 'varchar', 'caption=Заглавие,width=100%,mandatory');
        $this->FLD('keyField', 'enum(email=Имейл,mobile=Мобилен,fax=Факс,names=Лице,company=Фирма)', 'caption=Ключ,width=100%,mandatory,hint=Kлючовото поле за списъка');
        $this->FLD('fields', 'text', 'caption=Полета,width=100%,mandatory,hint=Напишете името на всяко поле на отделен ред,column=none');
        $this->FNC('allFields', 'text', 'column=none,input=none');
        
        $this->FLD('contactsCnt', 'int', 'caption=Записи,input=none');
    }
        
    
    /**
     * Прибавя ключовото поле към другите за да получи всичко
     */
    function on_CalcAllFields($mvc, $rec)
    {
        $rec->allFields = $rec->keyField . '=' . $mvc->fields['keyField']->type->options[$rec->keyField] . "\n" . $this->clearFields($rec->fields);
        
    }
    
    
    /**
     * Изчиства празния ред.
     * Премахва едноредовите коментари.
     */
    function clearFields($rec)
    {
        $newFields = '';
        $delimiter = '[#newLine#]';
        
        //Заместваме празните редове
        $fields = str_ireplace(array("\n", "\r\n", "\n\r"), $delimiter, $rec);
        $fieldsArr = explode($delimiter, $fields);

        //Премахва редове, които започват с #
        foreach ($fieldsArr as $value) {
            $value = str::trim($value);
            
            if ((strpos($value, '#') !== 0) && (strlen($value))) {
                $newFields .= $value . "\n";
            }
        }
        
        return str::trim($newFields);
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    function on_AfterUpdateDetail($mvc, $id, $Detail)
    {
        $rec = $mvc->fetch($id);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#listId = $id");
        $rec->contactsCnt = $dQuery->count();
        
        // Определяме състоянието на база на количеството записи (контакти)
        if($rec->state == 'draft' && $rec->contactsCnt > 0) {
            $rec->state = 'closed';
        } elseif ($rec->state == 'closed' && $rec->contactsCnt == 0) {
            $rec->state = 'draft';
        }
        
        $mvc->save($rec);
    }
    
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if(($action == 'edit' || $action == 'delete') && $rec->state != 'draft' && isset($rec->state)) {
            $roles = 'no_one';
        }
    }
    
    
    /**
     * Добавя помощен шаблон за попълване на полетата
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        if (!$data->form->rec->fields) {
            $template = new ET (getFileContent("blast/tpl/ListsEditFormTemplates.txt"));
            $data->form->rec->fields = $template->getContent();
        }
    }


    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        //Заглавие
        $row->title =$this->getVerbal($rec, 'title');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }

}