<?php


/**
 * Клас създаване на Progressive web application manifest
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Plugin extends core_Plugin
{
    public function on_Output(&$invoker)
    {
        if(haveRole('powerUser')) {
            $manifestUrl = toUrl(array('pwa_Plugin'));
            $invoker->appendOnce("\n<link  rel=\"manifest\" href=\"{$manifestUrl}\">", 'HEAD');

            $invoker->push(('pwa/js/app.js'), 'JS');
            jquery_Jquery::run($invoker, "sendSrc();");
        }
    }


    public function act_Default()
    {
        $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
        $iconInfoArr = array();

        $domainId = cms_Domains::fetchField("#domain = 'localhost'", 'id');

        if(core_Webroot::isExists('favicon.png', $domainId)) {
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

        $json = array(
            'short_name' => 'bgERP',
            'name' => 'bgERP - система за управление на бизнеса',
            'display' => 'standalone',
            'background_color' => '#fff',
            'theme_color' => '#fff',
            'start_url' => '/Portal/Show',
            'icons' => $iconInfoArr
        );
        
        core_App::outputJson($json);
    }
}
