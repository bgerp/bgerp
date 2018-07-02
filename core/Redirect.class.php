<?php



/**
 * Клас  'core_Redirect' ('Redirect') - Шаблон, който съдържа нова локация за браузъра
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Redirect extends core_ET
{
    
    
    /**
     * Конструктор на шаблона - редирект
     */
    public function __construct($url, $msg = null, $type = 'notice')
    {
        if (isset($msg)) {
            Mode::set('redirectMsg', array('msg' => $msg, 'type' => $type));
        }
        
        $this->push(toUrl($url), '_REDIRECT_');
    }
}
