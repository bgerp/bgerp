<?php


/**
 * Клас 'cms_GalleryDialogWrapper'
 *
 * @category  vendors
 * @package   cms
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_GalleryDialogWrapper extends core_Plugin
{
    
    
    /**
     * 
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param core_ET $tpl
     */
    function on_AfterRenderDialog($mvc, &$res, $tpl)
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
        foreach($tabArr as $name => $params) {
            $params = arr::make($params);
            $url['Ctr'] = $params['Ctr'];
            $url['Act'] = $params['Act'];
            $url['selectedTab'] = $name;
            
            $title = $params['caption'];
            
            if($params['icon'] && !Mode::is('screenMode', 'narrow')) {
                $title = "$title";
            }
            
            $tabs->TAB($name, $title, $url, $name);
        }
        
        $tabs->htmlClass = 'cssGallery';
        
        // Рендираме
        $res = $tabs->renderHtml($res);
        
        // Добавяме икони
        $res->prepend("<style>
        		
            .galleryPicture { background-image:url('" . sbf('img/16/picture.png', '') . "');}
            .galleryGallery { background-image:url('" . sbf('img/16/photos.png', '') . "');}
            .galleryGroups { background-image:url('" . sbf('img/16/grouping.png', '') . "');}

            </style>");
        
        // Добавяме css-файла
       	$res->push('cms/css/dialogGallery.css','CSS');
        
        // Конфигурация на ядрото
        $conf = core_Packs::getConfig('core');
        
        // Добавяме титлата
        $res->prepend(tr("Картинка") . " « " . $conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        // Добавяме клас към бодито
        $res->append('dialog-window', 'BODY_CLASS_NAME');
    }
    
    
	/**
	 * 
	 * 
	 * @param unknown_type $mvc
	 * @param unknown_type $tabs
	 */
    function on_AfterGetGalleryTabsArr($mvc, &$tabs)
    {
        $tabs['galleryPicture'] = array('caption' => 'Добавяне', 'Ctr' => 'cms_GalleryImages', 'Act' => 'addImgDialog');
        $tabs['galleryGallery'] = array('caption' => 'Картинки', 'Ctr' => 'cms_GalleryImages', 'Act' => 'galleryDialog');
        
        if (cms_GalleryGroups::haveRightFor('list')) {
            $tabs['galleryGroups'] = array('caption' => 'Групи', 'Ctr' => 'cms_GalleryGroups', 'Act' => 'dialogList');
        }
    }
}
