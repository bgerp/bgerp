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
    function on_AfterPrepareRetUrl($mvc, $data)
    {
        if ($data->form->cmd == 'save_n_new') {
            
            $data->retUrl = array($mvc, 'add', 'ret_url' => $data->retUrl);
            
            // Добавяме стойностите на връщане към "тихите" полета
            $fields = $data->form->selectFields("#silent == 'silent'");
            
            if(count($fields)) {
                foreach($fields as $name => $fld) {
                    $data->retUrl[$name] = Request::get($name);
                }
            }
            
            // Записваме в сесията, полетата със запомняне
            $fields = $data->form->selectFields("#remember");
            
            if(count($fields)) {
                foreach($fields as $name => $fld) {
                    $permanentName = cls::getClassName($mvc) . '_' . $name;
                    Mode::setPermanent($permanentName, $data->form->rec->{$name});
                }
            }
        } elseif($data->cmd != 'delete') {
            
            if (!$data->form->gotErrors()) {
                $fields = $data->form->selectFields("#remember == 'info'");
                
                // Изваждаме от сесията и поставяме като дефолти, полетата със запомняне
                if(count($fields)) {
                    foreach($fields as $name => $fld) {
                        $permanentName = cls::getClassName($mvc) . '_' . $name;
                        
                        if($value = core_Type::escape(Mode::get($permanentName))) {
                            $info .= "<p>{$fld->caption}: <b>{$value}</b></p>";
                        }
                    }
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
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (empty($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', 'class=btn-save-n-new,order=9.99965');
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, &$res, $data)
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
                    $data->form->setDefault($name, Mode::get($permanentName));
                }
            }
        }
    }
}