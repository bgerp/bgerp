<?php



/**
 * Клас 'plg_SaveAndNew' - Инструменти за изтриване и редактиране на ред
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_SaveAndNew extends core_Plugin
{
    
    
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if ($data->form->cmd == 'save_n_new') {
            
            $data->retUrl = array($mvc, 'add', 'ret_url' => $data->retUrl);
           
            // Добавяме стойностите на връщане към "тихите" полета
            $fields = $data->form->selectFields("#silent == 'silent'");
           
            if(count($fields)) {
                foreach($fields as $name => $fld) {
                	if($fld->input == 'hidden' || $fld->remember == 'remember' || $fld->type->params['remember'] == 'remember'){
                		$data->retUrl[$name] = Request::get($name);
                	}
                }
            }
           
            // Записваме в сесията, полетата със запомняне
            $fields = $data->form->selectFields("#remember || #name == 'id'");
            

            // Правим статус за информация на потребителя
            if(is_a($mvc, 'core_Detail')) {
                $action = tr("Добавен е нов") . ' ';
                $obj    = tr("ред");
            } else {
                $action = tr("Създаден е нов") . ' ';
                $obj    = tr("обект");
            }
            // status_Messages::newStatus($action . ($mvc->singleTitle ? tr(mb_strtolower($mvc->singleTitle)) : $obj));

            if(count($fields)) {
                foreach($fields as $name => $fld) {
                    $permanentName = cls::getClassName($mvc) . '_' . $name;
                    Mode::setPermanent($permanentName, $data->form->rec->{$name});
                }
            }


        } elseif($data->cmd != 'delete' && $data->form->cmd != 'refresh') {
            
            if (!$data->form->gotErrors()) {
                $fields = $data->form->selectFields("#remember == 'info' || #name == 'id'");
                
                $info = '';

                // Изваждаме от сесията и поставяме като дефолти, полетата със запомняне
                if(count($fields)) {
                    foreach($fields as $name => $fld) {
                        $permanentName = cls::getClassName($mvc) . '_' . $name;
                        
                        if(($value = core_Type::escape(Mode::get($permanentName))) && $name != 'id') {
                            $info .= "<p>{$fld->caption}: <b>{$value}</b></p>";
                        }
                        if($name == 'id') {
                            $id = $value;
                        }
                    }
                }

                if($mvc->rememberTpl && $id) {  
                    $row = $mvc->recToVerbal($mvc->fetch($id));
                    $tpl = new ET($mvc->rememberTpl);
                    $tpl->placeObject($row);
                    $info = $tpl->getContent();
                }
             
                if($info) {
                    $info = '<div style="padding:5px; background-color:#ffffcc; border:solid 1px #cc9;">' .
                    tr('Последно добавенo') . ": <ul style='margin:5px;padding-left:10px;'>{$info}</ul></div>";
                    
                    $data->form->info .= $info;
                }
            }
            
            // Изтриваме от сесията, полетата със запомняне
            $fields = $data->form->selectFields("#remember");
            
            if(count($fields)) {
                foreach($fields as $name => $fld) {
                    $permanentName = cls::getClassName($mvc) . '_' . $name;
                    Mode::setPermanent($permanentName, NULL);
                }
            }
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Ако след записа, трябва да изпратим новото id към друг екшън - не показваме бутона
        $retUrl = getRetUrl();
        if(is_array($retUrl) && in_array($mvc::getUrlPlaceholder('id'), $retUrl)) {
            return;
        }
        if (empty($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', NULL, array('id'=>'saveAndNew', 'order'=>'9.99965', 'ef_icon'=>'img/16/save_and_new.png', 'title'=>'Запиши документа и създай нов'));
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
        if(count($fields)) {
            foreach($fields as $name => $fld) {
                $permanentName = cls::getClassName($mvc) . '_' . $name;
                
                if (Mode::is($permanentName)) {
                	if($data->form->cmd !== 'refresh'){
                		$data->form->setDefault($name, Mode::get($permanentName));
                	}
                }
            }
        }
    }



}