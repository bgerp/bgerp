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
 *
 * @deprecated
 */
class pwa_Manifest extends core_Mvc
{

    /**
     * Подготвя манифест файла за PWA за съответния домейн
     *
     * @param $domainId
     * @return false|string
     *
     * @deprecated
     */
    public static function getPWAManifest($domainId)
    {
        $iconSizes = array(72, 96, 128, 144, 152, 192, 384, 512);
        $iconInfoArr = array();

        $imageUrl = null;

        if (core_Webroot::isExists('android-chrome-512x512.png', $domainId)) {
            $imageUrl = '/android-chrome-512x512.png';
        } elseif (core_Webroot::isExists('favicon.png', $domainId)) {
            $imageUrl = '/favicon.png';
        }

        foreach ($iconSizes as $size) {
            $tempArray = array();

            if (isset($imageUrl)) {
                $tempArray['src'] = $imageUrl;
            } else {
                $fName = "pwa-icon-{$size}x{$size}.png";
                $content = getFileContent("pwa/icons/icon-{$size}x{$size}.png");

                core_Webroot::register($content, '', $fName, $domainId);
                $tempArray['src'] = "/{$fName}";
            }

            $tempArray['sizes'] = $size .  'x' . $size;
            $tempArray['type'] = 'image/png';
            $iconInfoArr[] = $tempArray;
        }

        $appTitle = core_Setup::get('EF_APP_TITLE', true);
        $appTitle = tr($appTitle);
        $text = tr('интегрирана система за управление');
 
        $startUrl = '/?isPwa=yes';
    
        $shortcuts = array(
            (object) array(
                'name' => 'Сканиране на баркод',
                'short_name' => 'Баркод',
                'description' => 'Сканиране и търсене на информация за баркод',
                'url' => '/barcode_Search',
                'icons' => array(
                    (object) array(
                        'src' => sbf('pwa/icons/barcode-scan.png', ''),
                        'sizes' => '512x512',
                    ),
                 ),
            ),
        );
        $json = array(
            'short_name' => $appTitle,
            'name' => $appTitle . ' - ' . $text,
            'description' => $appTitle . ' - ' . $text,
            'display' => 'standalone',
            'background_color' => '#fff',
            'theme_color' => '#ddd',
            'start_url' => $startUrl,
            'shortcuts' => $shortcuts,
            'id' => $startUrl,
            'scope' => '/',
            'icons' => $iconInfoArr,
            'share_target' => array(
                'action' => '/pwa_Share/Target',
                'method' => 'POST',
                'enctype' => 'multipart/form-data',
                'params' => array(
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

        return json_encode($json);
    }



    /**
     * Помощна фунцкция за проверка дали може да се използва PWA
     *
     * @return string - yes|no
     *
     * @deprecated
     */
    public static function canUse($dId = null)
    {
        $defSettings = pwa_Setup::get('DOMAINS');
        if (empty($defSettings)) {

            return 'no';
        }

        $defSettings = keylist::toArray($defSettings);
        if (empty($defSettings)) {

            return 'no';
        }

        if (isset($dId) && $dId > 0) {
            $pDomain = cms_Domains::fetchField($dId, 'domain');
        } else {
            $pDomain = cms_Domains::getPublicDomain('domain');
        }

        foreach ($defSettings as &$domainId) {
            $domainName = cms_Domains::fetchField($domainId, 'domain');

            if ($pDomain == $domainName) {

                return 'yes';
            }
        }

        return 'no';
    }
}
