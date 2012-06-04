<?php



/**
 * Клас 'page_Wrapper' - Опаковка на страниците
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_Wrapper extends core_BaseClass {
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     */
    function render_($content)
    {
        if (!($tplName = Mode::get('wrapper'))) {
            $tplName = Mode::is('printing') ? 'page_Print' : 'page_Internal';
        }
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get($tplName);
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($content, 'PAGE_CONTENT');
        
        // Вземаме плейсхолдерите
        $placeHolders = $wrapperTpl->getPlaceHolders();

        
        // Отново вземаме плейсхолдерите
        $placeHoldersNew = $wrapperTpl->getPlaceHolders();
        
        // Заместваме специалните плейсхолдери, със съдържанието към което те сочат
        foreach($placeHoldersNew as $place) {
            if(!in_array($place, $placeHolders)) continue;
            
            $method = explode('::', $place);

            if(count($method) != 2) continue;


            $html = call_user_func($method);

            $wrapperTpl->replace($html, $place);
        }
        

        $wrapperTpl->output();
    }
}