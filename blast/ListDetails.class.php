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
class blast_ListDetails extends core_Detail
{   
    var $loadList = 'blast_Wrapper,plg_RowNumbering,plg_RowTools,plg_Select';

    var $title    = "Контакти за масово разпращане";

    var $canRead   = 'blast,admin';
    var $canWrite  = 'blast,admin';
    var $canReject = 'blast,admin';

    var $singleTitle = 'Контакт за масово разпращане';

    var $masterKey = 'listId';

    var $rowToolsField = 'RowNumb';

    function description()
    {
        // Информация за папката
        $this->FLD('listId' ,  'key(mvc=blast_Lists,select=title)', 'caption=Списък,mandatory,column=none');

        $this->FLD('data', 'blob', 'caption=Данни,input=none,column=none');
        $this->FLD('key', 'varchar(64)', 'caption=Kлюч,input=none,column=none');

        $this->setDbUnique('listId,key');
    }


    /**
     *
     */
    function on_AfterPrepareDetailQuery($mvc, $res, $data)
    {
        $data->query->orderBy("#key");
    }
    

    /**
     *
     */
    function on_BeforePrepareListFields($mvc, $res, $data)
    {
        $mvc->addFNC($data->masterData->rec->allFields);
        $mvc->setField('id', 'column=none');
    }
    
    /**
     *
     */
    function on_BeforePrepareEditForm($mvc, $res, $data)
    {
        if($id = Request::get('id', 'int')) {
            expect($rec = $mvc->fetch($id));
            expect($masterRec = $mvc->Master->fetch($rec->listId));
        } elseif($masterKey = Request::get($mvc->masterKey, 'int')) {
            expect($masterRec = $mvc->Master->fetch($masterKey));
        }
 
        expect($masterRec);

        $data->masterRec = $masterRec; // @todo: Да се сложи в core_Detail

        $mvc->addFNC($masterRec->allFields);
        
    }




    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {

        if($bData = $data->form->rec->data) {

            $fieldsArr = $mvc->getFncFieldsArr($data->masterRec->allFields);
            
            $bData  =  unserialize($bData);
 
            foreach($fieldsArr as $name => $caption) {
                $data->form->rec->{$name} = $bData[$name];
            }

        }

    }


    /**
     *
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->isSubmitted()) return;
        
        expect($masterRec = $mvc->Master->fetch($form->rec->listId));

        $fieldsArr = $this->getFncFieldsArr($masterRec->allFields);

        foreach($fieldsArr as $name => $caption) {
            $data[$name] = $form->rec->{$name};
        }
 
        $form->rec->data =  serialize($data);
        
        $keyField = $masterRec->keyField;

        $form->rec->key = str::convertToFixedKey(mb_strtolower(trim($form->rec->{$keyField})));
        
        if($form->rec->id) {
            $idCond = " AND #id != {$form->rec->id}";
        }

        if($mvc->fetch(array("#key = '[#1#]' AND #listId = [#2#]" . $idCond, $form->rec->key, $form->rec->listId))) {
            $form->setError($keyField, "В списъка вече има запис със същия ключ");
        }
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        static $fieldsArr;

        if(!$fieldsArr) {
            expect($masterRec = $mvc->Master->fetch($rec->listId));
            $fieldsArr = $this->getFncFieldsArr($masterRec->allFields);
        }
        
        $body  =  unserialize($rec->data);
 
        foreach($fieldsArr as $name => $caption) {
            $rec->{$name} = $body[$name];
            $row->{$name} = $mvc->getVerbal($rec, $name);
        }
    }


    /**
     *
     */
    function addFNC($fields) 
    {
        $fieldsArr = $this->getFncFieldsArr($fields);
        foreach($fieldsArr as $name => $caption) {
            $attr = ",remember=info"; 
            switch($name) {
                case  'email': 
                    $type = 'email'; 
                    break;
                case    'fax': 
                    $type = 'drdata_PhoneType'; 
                    break;
                case 'mobile': 
                    $type = 'drdata_PhoneType'; 
                    break;
                case 'country': 
                    $type = 'key(mvc=drdata_Countries,select=commonName)'; 
                    $attr = ",remember"; 
                    break;
                default: 
                    $type = 'varchar'; 
                    break;
            }

            $this->FNC($name, $type, "caption={$caption},mandatory,input" . $attr);
        }
    }


    /**
     *
     */
    function getFncFieldsArr($fields)
    {
        $fields = str_replace(array("\n", "\r\n", "\n\r"), array(',', ',', ','), $fields);
        $fieldsArr = arr::make($fields, TRUE);
        
        return $fieldsArr;
    }
    
    /**
     *
     */
    function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if($action == 'edit' || $action == 'add') {
              $roles = 'blast,admin';
        }
    }


    /**
     * Добавя бутон за импортиране на контакти
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $data->masterId), NULL, 'class=btn-import');
    }

}