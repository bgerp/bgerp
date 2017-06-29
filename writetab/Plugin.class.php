<?php




/**
 * Клас 'writetab_Plugin' - сигнализация, в кой таб пишем нещо
 *
 *
 * @category  bgerp
 * @package   writetab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class writetab_Plugin extends core_Plugin {
    
    
    /**
     * Извиква се преди рендирането на HTML input
     */
    function on_AfterRenderLayout($form, &$layout)
    {
        if((strtoupper($form->method) != 'GET') && (strtolower($form->view) != 'horizontal') && (strtolower($form->class) != 'simpleform')) {
 
            $symbol = writetab_Setup::get('SYMBOL');
            $color = writetab_Setup::get('COLOR');
            $bground = writetab_Setup::get('BGROUND');

            $layout->push("writetab/js/favico-0.3.10.min.js", 'JS');
            jquery_Jquery::run($layout, "if($('#main-container').length){ var favicon=new Favico({type : 'circle', bgColor:'{$bground}',textColor:'{$color}',animation:'none'});" .  
            "if(favicon.browser.supported){favicon.badge('{$symbol}')} else {var title = document.title; if(title.indexOf('✍') != 0) {setTitle('✍ ' + title)}}}");
        }
    }
    
}