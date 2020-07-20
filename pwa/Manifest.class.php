<?php


/**
 * Клас създаване на Progressive Web Application manifest
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Manifest extends core_Mvc
{
    /**
     * Акшън за динамично генериране на манифеста
     */
    public function act_Default()
    {
        $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
        $iconInfoArr = array();
        
        $domainId = cms_Domains::fetchField("#domain = 'localhost' AND #lang = 'bg'", 'id');
        
        if(core_Webroot::isExists('android-chrome-512x512.png', $domainId)) {
            $imageUrl = str_replace('/xxx', '', toUrl(array('xxx'), 'absolute')) . '/android-chrome-512x512.png';
        } elseif(core_Webroot::isExists('favicon.png', $domainId)) {
            $imageUrl = str_replace('/xxx', '', toUrl(array('xxx'), 'absolute')) . '/favicon.png';
        }
        
        foreach ($iconSizes as $size) {
            if ($imageUrl) {
                // Създаваме thumbnail с определени размери
                $thumb = new thumb_Img(array($imageUrl, $size, $size, 'url', 'mode' => 'small-no-change'));
                $tempArray = array();
                $img = $thumb->getUrl('deferred');
                $tempArray['src'] = $img;
            } else {
                $tempArray['src'] = sbf("pwa/icons/icon-{$size}x{$size}.png", '');
            }

            $tempArray['sizes'] = $size .  'x' . $size;
            $tempArray['type'] = 'image/png';
            $iconInfoArr[] = $tempArray;
        }
        
        $appTitle = core_Setup::get('EF_APP_TITLE', true);
        $appTitle = tr($appTitle);
        $text = tr('интегрирана система за управление');
        
        $json = array(
            'short_name' => $appTitle,
            'name' => $appTitle . ' - ' . $text,
            'display' => 'standalone',
            'background_color' => '#fff',
            'theme_color' => '#ddd',
            'start_url' => '/pwa_Share/Portal',
            'scope' => '/pwa_Share',
            'icons' => $iconInfoArr,
            'share_target' => array(
                'action' => '/pwa_Share/Target',
                'method' => 'POST',
                'enctype' => 'multipart/form-data',
                'params' => array (
                    'title' => 'name',
                    'text' => 'description',
                    'url' => 'link',
                    'files' => array(
                        array('name' => 'file',
                              'accept' => array('*/*')
                        ),
                    ),
                )
            ),
        );
        
        core_App::outputJson($json);
    }
}
