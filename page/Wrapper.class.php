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
        // Определяме каква да е темата обвивката на страницата
        if (!($tplName = Mode::get('wrapper'))) {
            if(Mode::is('printing')) {
                $tplName =  'page_Print';
            } elseif(haveRole('admin,ceo,manager,officer,executive')) {
                $tplName = 'page_Internal';
            } else {
                $tplName = 'cms_Page';
            }
        }
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get($tplName);
        
        
        // Вземаме плейсхолдерите
        $placeHolders = $wrapperTpl->getPlaceHolders();

        
        // Заместваме специалните плейсхолдери, със съдържанието към което те сочат
        foreach($placeHolders as $place) {
            
            $method = explode('::', $place);

            if(count($method) != 2) continue;


            $html = call_user_func($method);

            $wrapperTpl->replace($html, $place);
        }
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($content, 'PAGE_CONTENT');

        $wrapperTpl->output();
    }
}