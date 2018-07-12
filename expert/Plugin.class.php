<?php


/**
 * Клас 'expert_Plugin' -
 *
 *
 * @category  vendors
 * @package   expert
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class expert_Plugin extends core_Plugin
{
    /**
     * Извиква се преди изпълняването на екшън
     */
    public function on_BeforeAction(&$mvc, &$content, $act)
    {
        $method = 'exp_' . $act;
        
        if (method_exists($mvc, $method)) {
            
            // Създаваме експерта
            $exp = cls::get('expert_Expert', array('mvc' => $mvc));
            
            // Даваме му команда
            $content = $mvc->$method($exp);
            
            if ($content == 'DIALOG') {
                $content = $exp->getResult();
            }
            
            if ($content == 'FAIL') {
                if ($exp->onFail) {
                    $content = $mvc->onFail($exp);
                } else {
                    $exp->setRedirect();
                    $exp->setRedirectMsgType('error');
                    setIfNot($exp->midRes->alert, $exp->message, tr('Не може да се достигне крайната цел'));
                    $content = $exp->getResult();
                }
            }
            
            if ($content == 'SUCCESS') {
                if ($exp->onSuccess) {
                    $content = $mvc->onSuccess($exp);
                } else {
                    $exp->setRedirect();
                    $exp->setRedirectMsgType($exp->redirectMsgType);
                    setIfNot($exp->midRes->alert, $exp->message, tr('Крайната цел е достигната'));
                    $content = $exp->getResult();
                }
            }
            
            $content = $mvc->renderWrapping($content);
            
            return false;
        }
    }
}
