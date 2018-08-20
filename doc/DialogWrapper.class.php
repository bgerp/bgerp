<?php


/**
 *
 *
 *
 * @category  vendors
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_DialogWrapper extends core_Plugin
{
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
        
        $tabs->htmlClass = 'addDoc';
        
        // Рендираме
        $res = $tabs->renderHtml($res);
        
        // Добавяме икони
        $res->prepend("<style>
        		
            .docLog { background-image:url('" . sbf('img/16/documents16.png', '') . "');}

            </style>");
        
        // Добавяме css-файла
        $res->push('doc/css/dialogDoc.css', 'CSS');
        
        // Конфигурация на ядрото
        $conf = core_Packs::getConfig('core');
        
        // Добавяме титлата
        $res->prepend(tr('Документи') . ' « ' . $conf->EF_APP_TITLE, 'PAGE_TITLE');
    }
    
    
    /**
     *
     *
     * @param core_Mvc $mvc
     * @param array    $tabs
     */
    public function on_AfterGetGalleryTabsArr($mvc, &$tabs)
    {
        $tabs['docLog'] = array('caption' => 'Документи', 'Ctr' => $mvc, 'Act' => 'addDocDialog');
    }
}
