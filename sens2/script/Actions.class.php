<?php


/**
 * Детайл с действия към логическите блокове
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_script_Actions extends core_Detail
{
    public $oldClassName = 'sens2_ScriptActions';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools, sens2_Wrapper, plg_State, plg_Search';
    
    
    /**
     * Заглавие
     */
    public $title = 'Редове към Логическите блокове';
    
    public $singleTitle = 'Действие';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'sensMaster';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'sensMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Ключ към матера
     */
    public $masterKey = 'scriptId';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Скриптове';
    
    
    public $listFields = 'order,action';
    
    
    public $rowToolsField = 'order';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'action, data, comment';

    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('order', 'int', 'caption=Ред №');
        $this->FLD('scriptId', 'key(mvc=sens2_Scripts,title=name)', 'caption=Блок,column=none,silent,oldFieldName=logicId');
        $this->FLD('action', 'class(interface=sens2_script_ActionIntf, select=title, allowEmpty)', 'caption=Действие,mandatory,silent,refreshForm');
        $this->FLD('state', 'enum(active,closed,stopped)', 'caption=Състояние,input=none');
        $this->FLD('data', 'blob(serialize)', 'caption=Данни,input=none');
        $this->FLD('comment', 'varchar(128)', 'caption=Допълнително->Коментар,after=minNotifyTime,cond');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if ($rec->id) {
            $form->setReadOnly('action');
            $data = (array) self::fetch($rec->id)->data;
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $rec->{$key} = $value;
                }
            }
        }
        
        if ($rec->action) {
            $action = cls::get($rec->action);
            $action->prepareActionForm($form);
        } else {
            $form->setField('order', 'input=none');
        }
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    public function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->rec->action && !$form->rec->order) {
            $query = $mvc->getQuery();
            $query->orderBy('#order', 'DESC');
            $query->limit(1);
            $maxOrder = (int) $query->fetch("#scriptId = {$form->rec->scriptId}")->order;
            $form->setDefault('order', round(($maxOrder + 1) / 10) * 10 + 10);
        }
        if ($form->isSubmitted() && $form->rec->action) {
            $action = cls::get($form->rec->action);
            $action->checkActionForm($form);
            if (!$form->gotErrors()) {
                $dataFields = array_keys($form->selectFields("(#input == 'input' || #input == '') && !#notData"));
                $form->rec->data = new stdClass();
                foreach ($dataFields as $field) {
                    $form->rec->data->{$field} = $form->rec->{$field};
                }
            }
        }
    }
    
    /**
     * Посреждане
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#order', 'ASC');
    }
    
    
    public function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if (cls::load($rec->action, TRUE)) {
            $action = cls::get($rec->action);
            
            $rec->data->scriptId = $rec->scriptId;

            if(isset($rec->data->cond) && ($button = self::extractButton($rec->data->cond))) {
                $rec->data->cond = '';
                $context = sens2_Scripts::getContext($rec->scriptId);
                $button = strtr($button, $context);
                $go =  '<span style="padding-left:10px;">' . ht::createBtn($button, array('sens2_Scripts', 'Run', $rec->scriptId, 'startId' => $rec->order), 'Наистина ли искате да стартирате действието?') . '</span>';
            } else {
                $go = '';
            }
            
            $row->action = "<div style='font-family: Courier New,monospace !important; font-size:0.8em;'>" . $action->toVerbal($rec->data) . $go . '</div>';
            
            if(isset($rec->comment) && strlen($rec->comment)) {
                $row->action .= "<div style='padding-top:4px;font-size:0.9em;color:#696'>" . $mvc->getVerbal($rec, 'comment') . "</div>";
            }
        }

        if(Request::get('order') == $rec->order) {
            $row->ROW_ATTR['style'] .= 'background-color:yellow !important;';
        }
    }
    
    
    /**
     * Изпълнява указания скрипт
     */
    public static function runScript($scriptId)
    {
        $query = self::getQuery();
        $query->orderBy('#order', 'ASC');
        if($startId = Request::get('startId', 'int')) {
            $query->where("#order = {$startId}");
        } else {
            $startId = -1000;
        }
        while ($rec = $query->fetch("#scriptId = {$scriptId}")) {
            
            if (!cls::load($rec->action, TRUE)) {
                
                self::logWarning('Грешка с данните на екшъна', $rec->id);
                
                continue; 
            }
            
            $action = cls::get($rec->action);
            $rec->data->scriptId = $rec->scriptId;
            $exState = $rec->state;
            // Ако изпълняваме точно този ред
            if($startId == $rec->order && isset($rec->data->cond)) {
                $rec->data->cond = '1 == 1';
            }
            if(isset($rec->data->cond) && self::extractButton($rec->data->cond) !== null) {
                $rec->data->cond = '1 != 1';
            }
            $rec->data->id = $rec->id;
            try {
                $rec->state = $action->run($rec->data);
            } catch (core_exception_Expect $e) {
                reportException($e);
                self::logWarning('Грешка при изпъление на скрипт: ' . $e->getMessage());
            } catch (Exception $e) {
                reportException($e);
                self::logWarning('Грешка при изпъление на скрипт: ' . $e->getMessage());
            } catch (Error $e) {
                reportException($e);
                self::logWarning('Грешка при изпъление на скрипт: ' . $e->getMessage());
            } catch (Throwable $t) {
                reportException($t);
                self::logWarning('Грешка при изпъление на скрипт: ' . $t->getMessage());
            }

            if ($rec->state != $exState) {
                self::save($rec, 'state');
            }
        }
    }

    /**
     * Ако в стринга е дефиниран бутон, ограден с [] - връщаме го
     */
    public static function extractButton($str) 
    {
        $res = null;

        if(strlen($str) >= 3 && $str[0] == '[' && $str[strlen($str)-1] == ']') {
            $res = substr($str, 1, strlen($str)-2);
            if($res == 'go') {
                $res = 'Run';
            }
        }

        return $res;
    }
}
