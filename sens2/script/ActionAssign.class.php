 <?php


/**
 * Логическо действие за присвояване на стойност
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_script_ActionAssign
{
    public $oldClassName = 'sens2_ScriptActionAssign';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'sens2_script_ActionIntf';
    
    
    /**
     * Наименование на действието
     */
    public $title = 'Задаване на променлива';
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    public function prepareActionForm(&$form)
    {
        $form->FLD('varId', 'varchar', 'caption=Променлива,mandatory,oldFieldName=var,silent');
        $form->FLD('expr', 'text(rows=2)', 'caption=Нова стойност на променливата->Израз,width=100%,mandatory');
        $form->FLD('cond', 'text(rows=2)', 'caption=Условие за да се присвои->Израз,width=100%');
        
        $vars = sens2_script_DefinedVars::getContex($form->rec->scriptId);
        foreach ($vars as $i => $v) {
            $opt[$i] = $i;
        }
        
        if (!count($opt)) {
            redirect(array('sens2_Scripts', 'single', $vars), false, '|Моля, дефинирайте поне една променлива');
        }
        $form->setOptions('varId', $opt);
        
        $suggestions = sens2_script_Helper::getSuggestions($form->rec->scriptId);
        $form->setSuggestions('expr', $suggestions);
        $form->setSuggestions('cond', $suggestions);
    }
    
    
    /**
     * Проверява след  субмитване формата с настройки на контролера
     * Тук контролера може да зададе грешки и предупреждения, в случай на
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param core_Form   форма с въведени данни от заявката (след $form->input)
     */
    public function checkActionForm($form)
    {
    }
    
    public function toVerbal($rec)
    {
        $varId = sens2_Scripts::highliteExpr($rec->varId, $rec->scriptId);
        $expr = sens2_Scripts::highliteExpr($rec->expr, $rec->scriptId);
        $cond = sens2_Scripts::highliteExpr($rec->cond, $rec->scriptId);
        
        $res = "{$output} = {$expr}";
        if ($rec->cond) {
            $res .= ", ако {$cond}";
        }
        
        $res = "{$varId} = {$expr}";
        if ($rec->cond) {
            $res .= ", ако {$cond}";
        }
        
        return $res;
    }
    
    
    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    public function run($rec)
    {
        // Ако има условие и то не е изпълнено - не правим нищо
        if (trim($rec->cond)) {
            $cond = sens2_Scripts::calcExpr($rec->cond, $rec->scriptId);
            if ($cond === sens2_Scripts::CALC_ERROR) {
                
                return 'stopped';
            }
            if (!$cond) {
                
                return 'closed';
            }
        }
        
        // Изчисляваме израза
        $value = sens2_Scripts::calcExpr($rec->expr, $rec->scriptId);
        if ($value === sens2_Scripts::CALC_ERROR) {
            
            return 'stopped';
        }
        
        // Задаваме го на изхода
        $res = sens2_script_DefinedVars::setValue($rec->scriptId, $rec->varId, $value);
        
        if ($res !== false) {
            
            return 'active';
        }
        
        return 'stopped';
    }
}
