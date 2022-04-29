<?php


/**
 * Клас 'plg_SaveAndNew' - Плъгин за добавяне на "Запис и Нов"
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_SaveAndNew extends core_Plugin
{
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if ($data->form->cmd == 'save_n_new') {
            $data->retUrl = array($mvc, 'add', 'ret_url' => $data->retUrl);
            
            // Добавяме стойностите на връщане към "тихите" полета
            $fields = $data->form->selectFields("#silent == 'silent'");
            
            if (countR($fields)) {
                foreach ($fields as $name => $fld) {
                    if ($fld->input == 'hidden' || $fld->remember == 'remember' || $fld->type->params['remember'] == 'remember') {
                        $data->retUrl[$name] = Request::get($name);
                    }
                }
            }
            
            // Записваме в сесията, полетата със запомняне
            $fields = $data->form->selectFields("#remember || #name == 'id'");
            
            
            // Правим статус за информация на потребителя
            if (is_a($mvc, 'core_Detail')) {
                $action = tr('Добавен е нов') . ' ';
                $obj = tr('ред');
            } else {
                $action = tr('Създаден е нов') . ' ';
                $obj = tr('обект');
            }
            
            // status_Messages::newStatus($action . ($mvc->singleTitle ? tr(mb_strtolower($mvc->singleTitle)) : $obj));
            
            if (countR($fields)) {
                foreach ($fields as $name => $fld) {
                    $permanentName = cls::getClassName($mvc) . '_' . $name;
                    Mode::setPermanent($permanentName, $data->form->rec->{$name});
                }
            }

            Mode::setPermanent(cls::getClassName($mvc) . '_SAVE_AND_NEW', true);
        } elseif ($data->cmd != 'delete' && $data->form->cmd != 'refresh') {
            if (!$data->form->gotErrors()) {
                $fields = $data->form->selectFields("#remember == 'info' || #name == 'id'");
                
                $info = '';
                
                // Изваждаме от сесията и поставяме като дефолти, полетата със запомняне
                if (countR($fields)) {
                    foreach ($fields as $name => $fld) {
                        $permanentName = cls::getClassName($mvc) . '_' . $name;
                        $captionArr = explode('->', $fld->caption);
                        if (countR($captionArr) == 2) {
                            $caption = tr($captionArr[0]) . '->' . tr($captionArr[1]);
                        } else {
                            $caption = tr($fld->caption);
                        }
                        
                        if (($value = core_Type::escape(Mode::get($permanentName))) && $name != 'id') {
                            $info .= "<p>{$caption}: <b>{$value}</b></p>";
                        }
                        if ($name == 'id') {
                            $id = $value;
                        }
                    }
                }
                
                if ($mvc->rememberTpl && $id) {
                    $rec = $mvc->fetch($id);
                    $row = $mvc->recToVerbal($rec);
                    $tpl = new ET($mvc->rememberTpl);
                    $tpl->placeObject($row);
                    $info = $tpl->getContent();
                }
                
                if ($info) {
                    $info = '<div style="padding:5px; background-color:#ffffcc; border:solid 1px #cc9;">' .
                    tr('Последно добавено') . ": <ul style='margin:5px;padding-left:10px;'>{$info}</ul></div>";
                    $data->form->info .= $info;
                }
            }
            
            // Изтриваме от сесията, полетата със запомняне
            $fields = $data->form->selectFields('#remember');

            if (countR($fields)) {
                foreach ($fields as $name => $fld) {
                    $permanentName = cls::getClassName($mvc) . '_' . $name;
                    Mode::setPermanent($permanentName, null);
                }
            }
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Ако след записа, трябва да изпратим новото id към друг екшън - не показваме бутона
        $retUrl = getRetUrl();
        if (is_array($retUrl) && in_array($mvc::getUrlPlaceholder('id'), $retUrl)) {
            
            return;
        }

        if (empty($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', null, array('id' => 'saveAndNew', 'order' => '9.99965', 'ef_icon' => 'img/16/save_and_new.png', 'title' => 'Запиши документа и създай нов'));
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if ($data->form->rec->id) {
            
            return;
        }
        
        $fields = $data->form->selectFields("#remember == 'remember'");
        
        // Изваждаме от сесията и поставяме като дефолти, полетата със запомняне
        if (countR($fields)) {
            foreach ($fields as $name => $fld) {
                $permanentName = cls::getClassName($mvc) . '_' . $name;
                
                if (Mode::is($permanentName)) {
                    if ($data->form->cmd !== 'refresh') {
                        $data->form->setDefault($name, Mode::get($permanentName));
                    }
                }
            }
        }

        if(Mode::get(cls::getClassName($mvc) . '_SAVE_AND_NEW')){
            $data->_isSaveAndNew = true;
            Mode::setPermanent(cls::getClassName($mvc) . '_SAVE_AND_NEW', null);
        }
    }
}
