<?php


/**
 * Логическо действие за подаване сигнал към изход на контролер
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
class sens2_ScriptActionSignal
{
    public $oldClassName = 'sens2_LogicActionSignal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'sens2_ScriptActionIntf';
    
    
    /**
     * Наименование на действието
     */
    public $title = 'Изходящ сигнал';
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    public function prepareActionForm(&$form)
    {
        $form->FLD('output', 'varchar', 'caption=Изход,mandatory');
        $form->FLD('expr', 'text(rows=2)', 'caption=Израз,width=100%,mandatory');
        $form->FLD('cond', 'text(rows=2)', 'caption=Условие,width=100%');
        
        $opt = self::getOutputOpts();
        
        if (!count($opt)) {
            redirect(array('sens2_Controllers'), false, '|Моля, въведете поне един контролер с изход');
        }
        $form->setOptions('output', array('' => '') + $opt);
        
        
        $vars = sens2_ScriptDefinedVars::getContex($form->rec->scriptId);
        foreach ($vars as $i => $v) {
            $suggestions[$i] = $i;
        }
        $inds = sens2_Indicators::getContex();
        foreach ($inds as $i => $v) {
            $suggestions[$i] = $i;
        }
        asort($suggestions);
        $form->setSuggestions('expr', $suggestions);
        $form->setSuggestions('cond', $suggestions);
    }
    
    
    /**
     * Връща масив с опциите за изходите
     */
    public static function getOutputOpts()
    {
        $cQuery = sens2_Controllers::getQuery();
        while ($cRec = $cQuery->fetch("#state = 'active'")) {
            $ports = sens2_Controllers::getActivePorts($cRec->id, 'outputs');
            foreach ($ports as $port => $pObj) {
                $opt[$pObj->title] = $pObj->title;
                list($ctr, ) = explode('->', $pObj->title);
                $opt[$ctr . '->' . $port] = $ctr . '->' . $port;
            }
        }
        
        return $opt;
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
        $opt = self::getOutputOpts();
        $output = sens2_Scripts::highliteExpr($rec->output, $rec->scriptId);
        if (!isset($opt[$rec->output])) {
            $output = "<span style='border-bottom:dashed 1px red;'>{$output}</span>";
        }
        
        $expr = sens2_Scripts::highliteExpr($rec->expr, $rec->scriptId);
        $cond = sens2_Scripts::highliteExpr($rec->cond, $rec->scriptId);
        
        $res = "{$output} = {$expr}";
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
        $res = sens2_Controllers::setOutput($rec->output, $value);
        
        if (is_array($res)) {
            
            return 'active';
        }
        
        return 'stopped';
    }
}
