<?php


/**
 * Клас 'doc_AssignPlg' - Плъгин за възлагане на документи
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_AssignPlg extends core_Plugin
{
    
    
    /**
     * Кой може да възлага
     */
    var $canAssign = 'doc, admin, ceo';
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Ако няма такова поле
        if(!$mvc->fields['assign']) {
            
            // Добавяме в модела
            $mvc->FLD('assign', 'key(mvc=core_Users,select=nick)', 'caption=Възложен на,input=none');
        }
        
        // Ако няма такова поле
        if(!$mvc->fields['assignedOn']) {
            
            // Добавяме в модела
            $mvc->FLD('assignedOn', 'datetime(format=smartTime)', 'caption=Възложено->На,input=none');
        }
        
        // Ако няма такова поле
        if(!$mvc->fields['assignedBy']) {
            
            // Добавяме в модела
            $mvc->FLD('assignedBy', 'key(mvc=core_Users)', 'caption=Възложено->От,input=none');
        }
    }
    
    
	/**
     * Добавя бутони за възлагане на задача към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако имаме права за възлагане
        if ($mvc->haveRightFor('assign', $data->rec)) {
            $assignUrl = array(
                $mvc,
                'assign',
                $data->rec->id,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Възлагане', $assignUrl, 'class=btn-assign, order=14');
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Определяме правата за възлагане
        if ($action == 'assign') {
            
            // Само активните документи могат да се възлат
            if ($rec &&$rec->state != 'active') {
                
                // Никой няма такива права, ако не е активен
                $requiredRoles = 'no_one';
            }   
        }
    }
    
    
    /**
     * Реализация на екшън-а 'act_Assign'
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        // Ако екшъна не е assign
        if($action != 'assign') return;
        
        // Проверяваме за права
        $mvc->requireRightFor('assign');
        
        // Вземаме формата към този модел
        $form = $mvc->getForm();
        
        // Въвеждаме id-то
        $form->input('id, assign', 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $mvc->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за възлагане на съответния запис
        $mvc->requireRightFor('assign', $rec);
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма такова URL, връщаме към single' а
        $retUrl = ($retUrl) ? ($retUrl) : array($mvc, 'single', $form->rec->id);
        
        // Името на документа
        $docSingleTitle = $mvc->singleTitle; 
        $docSingleTitleLower = mb_strtolower($docSingleTitle); 
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            // На кого е била възложена задачата преди това
            $oldAssigned = $mvc->fetchField($form->rec->id, 'assign');
            
            // Ако е била възложена на някой друг преди това
            if ($oldAssigned && ($oldAssigned != $form->rec->assign)) {
                
                // URL' то което ще се премахва или показва от нотификациите
                $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                
                // Премахваме контейнера от достъпните
                doc_ThreadUsers::removeContainer($rec->containerId);
                
                // Премахваме този документ от нотификациите за стария потребител
                bgerp_Notifications::setHidden($keyUrl, 'yes', $oldAssigned);
                
                // Добавяме документа в нотификациите за новия потреибител
                bgerp_Notifications::setHidden($keyUrl, 'no', $form->rec->assign);
                
                // Премахваме документа от "Последно" за стария потребител
                bgerp_Recently::setHidden('document', $rec->containerId, 'yes', $oldAssigned);
                
                // Добавяме документа в "Последно" за новия потребител
                bgerp_Recently::setHidden('document', $rec->containerId, 'no', $form->rec->assign);
            }
            
            // Определяме кой е модифицирал записа
            $form->rec->assignedBy = Users::getCurrent();
            
            // Записваме момента на създаването
            $form->rec->assignedOn = dt::verbal2Mysql();
            
            //Упдейтва състоянието и данните за имейл-а
            $mvc->save($form->rec, 'assign, assignedBy, assignedOn');
            
            // Нотифицираме възложения потребител
            $mvc->notificateAssigned($form->rec->id);
            
            // Даваме възможност на други функции да се прикачат след приключване на възлагането
            $mvc->invoke('afterSubmitAssign', array($rec));
            
            // Добавяме съобщение
            core_Statuses::add(tr("Успешно възложихте|* {$docSingleTitleLower} |на|*: " . $mvc->getVerbal($form->rec, 'assign')));
            
            // Редиректваме
            return redirect($retUrl);
        }
        
        // Ако вече е била възложена
        if ($rec->assign) {
            
            // Избираме по подразбиране
            $form->setDefault('assign', $rec->assign);
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'assign';
        
        // Променяме името на полете
        $form->fields['assign']->caption = 'Потребител';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Титлата на формата
        $form->title = "Възлагане на |*{$docSingleTitleLower}";

        // Титлата на документа
        $title = $mvc->getDocumentRow($form->rec->id)->title;

        // Информацията
        $form->info = new ET ('[#1#]', tr("|*<b>|{$docSingleTitle}|*: <i style='color:blue'>{$title}</i></b>"));
        
        // Рендираме изгледа
        $res = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * Дефолт имплементацията на notificateAssigned($id)
     * Изпраща нотификация до възложения потребител
     */
    static function on_AfterNotificateAssigned($mvc, $res, $id)
    {
        // Записа за съответния сигнал
        $iRec = $mvc->fetch($id);
        
        // Нишката
        $threadId = $iRec->threadId;
        
        // Документа
        $containerId = $iRec->containerId;
        
        // Потребителя, на който е възложен
        $assignUserId = $iRec->assign;
        
        // id' то на потребителя, който възлага задачата
        $currUserId = core_Users::getCurrent('id');
        
        // Ако, възлагащия също е отговорник
        if ($assignUserId == $currUserId) return ;
        
        // Вербалния ник на потребителя
        $nick = core_Users::getVerbal($currUserId, 'nick');
        
        // Манипулатора на документа
        $docHnd = $mvc->getHandle($id);
        
        // Титлата на документа в долния регистър
        $docSingleTitleLower = mb_strtolower($mvc->singleTitle); 
        
        // Заглавието на сигнала във НЕвербален вид
        $title = str::limitLen($mvc->getDocumentRow($id)->recTitle, 90);
        
        // Съобщението, което ще се показва и URL' то
        $message = tr("|*{$nick} |възложи|* {$docSingleTitleLower}: \"{$title}\"");
        $url = array('doc_Containers', 'list', 'threadId' => $threadId);
        $customUrl = array('doc_Containers', 'list', 'threadId' => $threadId, 'docId' => $docHnd, '#' => $docHnd);
//        $url = $customUrl = array($mvc, 'single', $id);
        
        // Определяме приоритете на нотификацията
        if ($iRec->priority) {
            
            // Приорите в долен регистър
            switch (strtolower($iRec->priority)) {
                
                case 'normal':
                case 'low':
                    $priority = 'normal';
                break;
                
                case 'warning':
                case 'high':
                    $priority = 'warning';
                break;
                
                case 'alert':
                case 'critical':
                    $priority = 'alert';
                break;
                
                default:
                    ;
                break;
            }
        }
        
        // Ако все още не сме определили приоритете по подразбиране да не нормален
        $priority = ($priority) ? $priority : 'normal';
        
        // Добавяме нотофикация
        bgerp_Notifications::add($message, $url, $assignUserId, $priority, $customUrl);
    }
    
    
	/**
     * Добавя ново поле, което съдържа датата, в чист вид
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има данни
        if ($rec->assignedBy) {
            
            // Вербалната стойност
            $row->assignedBy = core_Users::getVerbal($rec->assignedBy, 'nick');    
        }
        
        // Ако има данни
        if ($rec->assignedDate) {
            
            // Вербалната стойност
            $row->assignedDate = dt::mysql2verbal($rec->assignedDate, 'd-m-Y');    
        }
    }
    
    
    /**
     * Потребителя, на когото е възложена задачата
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        // Възложен на
        $assignedUser = $mvc->fetchField($id, 'assign');
        
        // Обединява с другите шерната потребители
        $shared = type_Keylist::merge($assignedUser, $shared);
    }
}
