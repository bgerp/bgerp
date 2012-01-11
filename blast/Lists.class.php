<?php

/**
 * Клас 'blast_Lists' - Списъци за масово разпращане
 *
 * @category   bgERP
 * @package    blast
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class blast_Lists extends core_Master
{   
    var $loadList = 'plg_Created,plg_Rejected,blast_Wrapper,plg_State,plg_RowTools,plg_Rejected';

    var $title    = "Списъци за масово разпращане";

    //var $listFields = 'id,title,type=Тип,inCharge=Отговорник,threads=Нишки,last=Последно';

    var $canRead   = 'blast,admin';
    var $canWrite  = 'blast,admin';
    var $canReject = 'blast,admin';

    var $singleTitle = 'Списък за масово разпращане';
    var $rowToolsSingleField = 'title';
    
 
    var $details = 'blast_ListDetails';
    
    
    /**
	* Нов темплейт за показване
	*/
	var $singleLayoutFile = 'blast/tpl/SingleLayoutLists.html';
    

    function description()
    {
        // Информация за папката
        $this->FLD('title' ,  'varchar', 'caption=Заглавие,width=100%,mandatory');
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
        $rec->allFields = $rec->keyField . '=' . $mvc->fields['keyField']->type->options[$rec->keyField] . "\n" . $rec->fields;
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    function on_AfterUpdateDetail($mvc, $id, $Detail)
    {
        $rec  = $mvc->fetch($id);
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
     *
     */
    function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if(($action == 'edit' || $action == 'delete') && $rec->state != 'draft' && isset($rec->state)) {
            $roles = 'no_one';
        }
    }
    
    
    /**
     * Изчиства празния ред.
	 * Премахва едноредовите коментари.
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
    	$newFields = '';
    	$delimiter = '[#newLine#]';
    	
    	//Премахва редове, които започват с #
    	$fields = str_ireplace(array("\n", "\r\n", "\n\r"), $delimiter, $rec->fields);
    	$fieldsArr = explode($delimiter, $fields);
    	foreach ($fieldsArr as $value) {
    		$value = str::trim($value);
    		if ((strpos($value, '#') !== 0) && (strlen($value))) {
    			$newFields .= $value . "\r\n";
    		}
    	}
    	$rec->fields = str::trim($newFields);
    }
    
    
    /**
     * Добавя помощен шаблон за попълване на полетата
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
    	$template =  "# - едноредов коментар\r\n" . "#Само се премахва '#'\r\n" . 
    			"\r\n" . "#name=Име\r\n#family=Фамилия\r\n#date=Дата\r\n#hour=Час\r\n#и др." . 
    			"\r\n" . "\r\n#Необходими за \"Писма\" \r\n" . "\r\n#city=Град" . 
    			"\r\n#postCode=Пощенски код" . "\r\n#district=Област" .
    			"\r\n#recepient=Получател" . "\r\n#address=Адрес";
    	
//		if ($data->form->rec->fields == NULL) {
//			$data->form->rec->fields = $template;
//		}
		$data->form->rec->fields .= "\r\n" . $template;
    }

}