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
    }


    function act_Default()
    {
        $icon72 = sbf('img/icons/icon-72x72.png', '');
        $icon96 = sbf("img/icons/icon-96x96.png", "");
        $icon128 = sbf("img/icons/icon-128x128.png", "");
        $icon144 = sbf("img/icons/icon-144x144.png", "");
        $icon152 = sbf("img/icons/icon-152x152.png", "");
        $icon192 = sbf("img/icons/icon-192x192.png", "");
        $icon384 = sbf("img/icons/icon-384x384.png", "");
        $icon512 = sbf("img/icons/icon-512x512.png", "");

        $json = array(
            "short_name" => "bgERP",
            "name" => "bgERP - система за управление на бизнеса",
            "display" => "standalone",
            "start_url" => "/",
            "icons" => array (
                array(
                    "src" =>$icon72,
                    "sizes"=> "72x72",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon96,
                    "sizes"=> "96x96",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon128,
                    "sizes"=> "128x128",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon144,
                    "sizes"=> "144x144",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon152,
                    "sizes"=> "152x152",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon192,
                    "sizes"=> "192x192",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon384,
                    "sizes"=> "384x384",
                    "type"=> "image/png"
                ),
                array(
                    "src" =>$icon512,
                    "sizes"=> "512x512",
                    "type"=> "image/png"
                )
            )
        );

        core_App::outputJson($json);
    }
}
