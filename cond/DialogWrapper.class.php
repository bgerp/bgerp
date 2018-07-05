<?php


/**
 * Пасаж
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_DialogWrapper extends core_Plugin
{


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'passage_DialogWrapper';


    /**
     *
     *
     * @param core_Mvc $mvc
     * @param core_ET  $res
     * @param core_ET  $tpl
     */
    public function on_AfterRenderDialog($mvc, &$res, $tpl)
    {
        $res = $tpl;
        
        // Масив с табовете
        $tabArr = $mvc->getGalleryTabsArr();
        
        // Инстанцияна на табовете
        $tabs = cls::get('core_Tabs');
        
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Урл
        $url = array(
            'callback' => $mvc->callback);
        
        // Обхождаме табовете
        foreach ($tabArr as $name => $params) {
            $params = arr::make($params);
            $url['Ctr'] = $params['Ctr'];
            $url['Act'] = $params['Act'];
            $url['selectedTab'] = $name;
            
            $title = $params['caption'];
            
            if ($params['icon'] && !Mode::is('screenMode', 'narrow')) {
                $title = "${title}";
            }
            
            $tabs->TAB($name, $title, $url, $name);
        }
        
        $tabs->htmlClass = 'passageDialog';
        
        // Рендираме
        $res = $tabs->renderHtml($res);
        
        // Добавяме икони
        $res->prepend("<style>
        		
            .passage { background-repeat: no-repeat; padding-left: 20px; background-image:url('" . sbf('img/16/passage.png', '') . "');}

            </style>");
        
        // Добавяме css-файла
        $res->push('doc/css/dialogDoc.css', 'CSS');
        
        // Конфигурация на ядрото
        $conf = core_Packs::getConfig('core');
        
        // Добавяме титлата
        $res->prepend(tr('Пасажи') . ' « ' . $conf->EF_APP_TITLE, 'PAGE_TITLE');
    }
    
    
    /**
     *
     *
     * @param unknown_type $mvc
     * @param unknown_type $tabs
     */
    public function on_AfterGetGalleryTabsArr($mvc, &$tabs)
    {
        $tabs['passage'] = array('caption' => 'Пасажи', 'Ctr' => $mvc, 'Act' => 'Dialog');
    }
}
