<?php


/**
 * Клас създаване на Progressive web application manifest
 *
 * @package   pwa
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pwa_Plugin extends core_Plugin
{

    function on_Output(&$invoker)
    {
        $manifestUrl = toUrl(array('pwa_Plugin'));

        $invoker->appendOnce("\n<link  rel=\"manifest\" href=\"$manifestUrl\">", "HEAD");

        $invoker->push(('pwa/js/app.js'), 'JS');
        $src = sbf('pwa/js/sw.js', '');
        jquery_Jquery::run($invoker, "sendSrc('{$src}');");


    }


    function act_Default()
    {
        $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
        $iconInfoArr = array();
        $conf = core_Packs::getConfig('pwa');

        foreach($iconSizes as $size) {
            if($conf->PWA_IMAGE) {
                // Създаваме thumbnail с определени размери
                $thumb = new thumb_Img(array($conf->PWA_IMAGE, $size, $size, 'fileman', 'mode' => 'small-no-change'));
                $tempArray = array();
                $img = $thumb->getUrl('deferred');
                $tempArray['src'] = $img;
            } else {
                $tempArray['src'] = sbf("pwa/icons/icon-{$size}x{$size}.png", '');
            }
            $tempArray['sizes'] = $size .  "x" . $size;
            $tempArray['type'] = "image/png";
            $iconInfoArr[] = $tempArray;
        }

        $json = array(
            "short_name" => "bgERP",
            "name" => "bgERP - система за управление на бизнеса",
            "display" => "standalone",
            "start_url" => "/",
            "icons" => $iconInfoArr
        );

        core_App::outputJson($json);
    }
}
