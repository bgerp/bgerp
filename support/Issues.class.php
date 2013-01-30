<?php 


/**
 * Документ с който се сигнализара някакво несъответствие
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Issues extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Document';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Сигнали';
    
    
    /**
     * 
     */
    var $singleTitle = 'Сигнал';
    
    
    /**
     * 
     */
    var $abbr = 'Sig';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, support';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     *
     */
    var $canActivate = 'user';
    
    
    /**
     * Кой може да възлага задачата
     */
    var $canAssign = 'support, admin, ceo';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, plg_Search, 
    				doc_SharablePlg, doc_AssignPlg, plg_Sorting';

    
    /**
     * Дали може да бъде само в началото на нишка
     */
    // TODO може да се добави в папки на някои фирми, където да се добави по средата на нишката
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'support/tpl/SingleLayoutIssue.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/support.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'componentId, typeId, description';
    
    
    /**
     * 
     */
    var $listFields = 'id, title, systemIdShow, componentId, typeId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * 
     */
    var $cloneFields = 'componentId, typeId, title, description, priority';
	
	
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('componentId', "key(mvc=support_Components,select=name)", 'caption=Компонент, mandatory');
        $this->FLD('title', 'varchar', "caption=Заглавие, mandatory, width=100%");
        $this->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%');
        $this->FLD('description', 'richtext(rows=10,bucket=Support)', "caption=Описание, width=100%, mandatory");
        $this->FLD('priority', 'enum(normal=Нормален, warning=Висок, alert=Критичен)', 'caption=Приоритет');
        
        // Възлагане на задача (за doc_AssignPlg)
        $this->FLD('assign', 'user(roles=powerUser)', 'caption=Възложен на,input=none');
        
        $this->FNC('systemIdShow', 'key(mvc=support_Systems, select=name)', 'caption=Система, mandatory, input=none');
    }
    
    
    /**
     * 
     */
    function on_CalcSystemIdShow($mvc, $rec)
    {
        // systemId на съответния компонент
        $systemId = support_Components::fetchField($rec->componentId, 'systemId');
        
        $rec->systemIdShow = $systemId;
    }

    
	/**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        
        return 'opened';
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        // Нормален приоритет по подразбиране
        $data->form->setDefault('priority', 'normal');
        
        // Вземаме systemId' то на документа от URL' то
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        // Опитваме се да вземеме return ult' то
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? $retUrl : array('support_Issues', 'selectSystem');

        // Ако има systemId
        if ($systemId) {
            
            // Вземаме записите
            $iRec = support_Systems::fetch($systemId);
            
            // Ако имаме права за single до папката
            if ($iRec->folderId && doc_Folders::haveRightFor('single', $iRec->folderId)) {    
                
                // Форсираме създаването на папката
                $folderId = support_Systems::forceCoverAndFolder($iRec);
                
                // Задаваме id' то на папката
                $data->form->rec->folderId = $folderId;    
            }
        } 
        
        // Ако все още не сме определили папката
        if (!$folderId) {
            
            // Ако няма подадено systemId, вземаме id' то на папката по подразбиране
            $folderId = $data->form->rec->folderId;
        }
        
        // Записите за класа, който се явява корица
        $coverClassRec = doc_Folders::fetch($folderId);
        
        //id' то на класа, който е корица
        $coverClassId = $coverClassRec->coverClass;
        
        //Името на корицата на класа
        $coverClassName = cls::getClassName($coverClassId);

        // Ако ковъра на класа не е supportSystems
        if ($coverClassName != 'support_Systems') {
            
            // Редиректваме към избор на система
            return redirect(array($mvc, 'selectSystem', 'ret_url' => $retUrl));
        } else {
            
            // Задаваме systemId да е id' то на ковъра
            $systemId = $coverClassRec->coverId;
        }
        
        // Извличаме всички компоненти, със съответното systemId
        $query = support_Components::getQuery();
        $query->where("#systemId = '{$systemId}'");
        
        // Обхождаме всички открити резултати
        while ($rec = $query->fetch()) {
            
            // Създаваме масив с компонентите
            $components[$rec->id] = support_Systems::getVerbal($rec, 'name');
        }
        
        // Ако няма въведен компонент
        if (!$components) {
            
            // Добавяме съобщение за грешка
            core_Statuses::add(tr('Няма въведен компонент на системата.'));
            
            // Ако има права за добавяне на компонент
            if (support_Components::haveRightFor('add')) {
                
                // Линк за препращаме към станицата за добавяне на компонент
                $redirectArr = array('support_Components', 'add', 'systemId' => $systemId, 'ret_url' => $retUrl);    
            } else {
                
                // Ако нямаме права, препащаме където сочи return URL' то
                $redirectArr = $retUrl;
            }
            
            // Препащаме
            return redirect($redirectArr);
        }
        
        // Променяме съдържанието на полето компоненти с определения от нас масив
        $data->form->setOptions('componentId', $components);
        
        // Вземаме записа за съответната система
        $sRec = support_Systems::fetch($systemId);
        
        // Разрешените типове за съответната система
        $allowedTypesArr = type_Keylist::toArray($sRec->allowedTypes);

        // Обхождаме масива с всички разрешени типове
        foreach ($allowedTypesArr as $allowedType) {
            
            // Добавяме в масива вербалната стойност на рарешените типове
            $types[$allowedType] = support_IssueTypes::getVerbal($allowedType, 'type');
        }
        
        // Променяме съдържанието на полето тип с определения от нас масив, за да се показват само избраните
        $data->form->setOptions('typeId', $types);
        
        // Масив с всички потребители, които имат достъп до папката
        $sharedUsersArr = doc_Folders::getSharedUsersArr($folderId, TRUE);
        
        // Ако няма нито един потребители
        if (!count($sharedUsersArr)) {
            
            // Не показваме полето
            $data->form->setField('sharedUsers', 'input=none');
        } else {
            
            // Задаваме да се показват потребителите, които имат достъп
            $data->form->setSuggestions('sharedUsers', $sharedUsersArr);    
        }
    }
    
    
    /**
     * Екшън за избиранер на система
     */
    function act_SelectSystem()
    {
        // Проверяваме за права
        self::requireRightFor('add');
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Създаваме поле за избор на система
        $form->FNC('systemId', 'key(mvc=support_Systems, select=name)', 'caption=Система, mandatory');
        
        // Въвеждаме съдържанието на полетата
        $form->input('systemId');
        
        // Ако формата е изпратена
        if($form->isSubmitted()) {
            
            // Очакваме да е сетнат systemId
            expect($systemId = $form->rec->systemId);
            
            // Редиректваме към създаването на сигнал с избраната система
            return redirect(array($this, 'add', 'systemId' => $systemId, 'ret_url' => TRUE));
        }
        
        // URL' то където ще редиректвамеа
        $retUrl = getRetUrl();
        
        // Ако, няма създаваме си
        $retUrl = ($retUrl) ? $retUrl : array('support_Issues');
        
        // Вземаме всички системи, до които имаме достъп
        $accessedSystemsArr = support_Systems::getAccessed();
        
        // Броя на системите, до които имаме достъп
        $accessedSystemsCnt = count($accessedSystemsArr);
        
        // Ако няма нито една система, до която да имаме достъп
        if (!$accessedSystemsCnt) {
            
            // Добавяме съобщение за грешка
            core_Statuses::add(tr('Няма достъпна система|*.'));
            
            // Ако има права за добавяне на система
            if (support_Systems::haveRightFor('add')) {
                
                // Линк за препращаме към станицата за добавяне на система
                $redirectArr = array('support_Systems', 'add', 'ret_url' => $retUrl);    
            } else {
                
                // Ако нямаме права, препащаме където сочи return URL' то
                $redirectArr = $retUrl;
            }
            
            // Препащаме
            return redirect($redirectArr);
            
        } elseif ($accessedSystemsCnt == 1) {

            // Ако има само една достъпна система, препращане към създаването на докумев в нея
            return redirect(array('support_Issues', 'add', 'systemId' => key($accessedSystemsArr), 'ret_Url' => $retUrl));
        } 
        
        // Ако достъпните ситеми са повече от 1, тогава ги показваме
        $form->setOptions('systemId', $accessedSystemsArr);

        // Кои полета да се показват
        $form->showFields = 'systemId';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Избор', 'select', array('class' => 'btn-select'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Титлата на формата
        $form->title = 'Избор на система';
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
	/**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
     
        $row = new stdClass();
        
        // Типа
        $type = static::getVerbal($rec, 'typeId');

        // Компонента
        $component = static::getVerbal($rec, 'componentId');
        
        // Добавяме типа към заглавието
        $row->title    =  $this->getVerbal($rec, 'title');

        $row->subTitle = "{$type}, {$component}";

        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_Activation($mvc, &$rec)
    {
        // Ако няма компонент и имаме id
        if ((!$rec->componentId) && ($rec->id)) {
            
            // Извличаме записите
            $nRec = $mvc->fetch($rec->id);   
        } elseif ($rec->componentId) {
            
            // Клонираме
            $nRec = clone($rec);
        }
        
        // Ако има компоненти
        if ($nRec->componentId) {
            
            // Отговорниците на компонента
            $maintainers = support_Components::fetchField($nRec->componentId, 'maintainers');
            
            // Обядиняваме отговорниците и споделените потребители
            $rec->sharedUsers = type_Keylist::merge($rec->sharedUsers, $maintainers);      
        }
    }
    
    
    /**
     * Прихваща извикване на функцията afterSubmitAssign @see doc_AssignPlg->on_BeforeAction
     */
    function on_AfterSubmitAssign($mvc, $rec)
    {
        // TODO да се записва в лога
    }
    
    
    /**
     * Затваря сигналите в даден тред
     * 
     * $threadId doc_Threads - id на нишката
     */
    static function closeIssue($threadId)
    {
        // Вземаме всички сингнали от нишката 
        //По сегашната логика трябва да е само един
        $query = static::getQuery();
        $query->where("#threadId = '{$threadId}'");
        
        // Обхождаме записите
        while ($rec = $query->fetch()) {
            
            // Сменяме състоянието на нишката на затворена
            $rec->state = 'closed';
            static::save($rec);
        }
    }
}
