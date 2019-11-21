<?php


/**
 * Клас 'doc_plg_MultiPrint'
 *
 * Плъгин за  принтиране на няколко копия на даден документ
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_MultiPrint extends core_Plugin
{
    
    
    /**
     * Дупликираме шаблона, колкото пъти е зададено, в режим принтиране
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Ако не сме в режим принтиране не правим нищо
        if (!Mode::get('printing')) {
            
            return;
        }
        
        $originalTpl = clone($tpl);
        $tpl = new ET('');
        
        $copiesNum = isset($mvc->copiesOnPrint) ? $mvc->copiesOnPrint : ((countR($mvc->printParams)) ? countR($mvc->printParams) : 2);
        
        for ($i = 1; $i <= $copiesNum; $i++) {
            
            // Ако сме в режим принтиране, добавяме копие на ордера
            $clone = clone($originalTpl);
            
            // Добавяме зададените параметри в $mvc->printParams, най отгоре на документа
            if (countR($mvc->printParams) > 0) {
                $paramET = '';
                foreach ($mvc->printParams[$i - 1] as $param) {
                    $paramET .= $param . ' &nbsp;&nbsp;&nbsp;';
                }
                $clone->prepend(new ET("<span style='margin-left:24px'>[#paramET#]</span>"));
                $clone->replace($paramET, 'paramET');
            }
            
            // Контейнер в който ще вкараме документа + шаблона с параметрите му
            $container = new ET("<div class='print-break'>[#clone#]</div>");
            $container->replace($clone, 'clone');
            
            // За всяко копие предизвикваме ивент в документа, ако той иска да добави нещо към шаблона на копието
            $mvc->invoke('AfterRenderPrintCopy', array($container, $i, $data->rec));
            
            $tpl->append($container);
            
            $tpl->removeBlocks();
        }
    }
}
