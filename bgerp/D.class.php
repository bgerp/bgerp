<?php



/**
 * Клас за показване на документи
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_D extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Показване на документи';
    
    
    /**
     * Екшъна за показване на документи
     */
    function act_S()
    {
        //Провярява дали сме логнат потребител. Ако не сме редиректва в страницата за вход.
        requireRole('user');
        
        //Вземаме get параметрите
        $cid = Request::get('cid', 'int');
        
        //Вземаме документа
        $doc = doc_Containers::getDocument($cid);
        
        //Инстанцията на документа
        $instance = $doc->instance;
        
        //Името на класа
        $className = $doc->className;
        
        //id' то на документа
        $that = $doc->that;
        
        //Проверявме дали имаме права за разглеждане на документа
        if ($instance->haveRightFor('single', $that)) {
            
            //Подготвяме URL' то където ще редиректнем
            $retUrl = array($instance, 'single', $that);
            
            //Спираме режима за принтиране
            Mode::set('printing', FALSE);
            
            //Редиректваме към sinlgle' a на документа
            redirect($retUrl);
        } else {
            
            //Ако нямаме права, показваме съобщение за грешка
            expect(NULL, 'Нямате права за разглеждане на документа.');
        }
    }
}
