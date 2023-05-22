<?php


/**
 * Мобилното приложение активно ли е?
 */
defIfNot('PWA_DOMAINS', '');


/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
    public $info = 'bgERP progressive web application';


    /**
     * Необходими пакети
     */
    public $depends = 'cms=0.1';



    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PWA_DOMAINS' => array('keylist(mvc=cms_Domains, select=domain, forceGroupBy=domain)', 'caption=Мобилно приложение->Домейни'),
    );

    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('bgERP PWA', 'pwa_Plugin', 'core_page_Active', 'family');

        $html .= fileman_Buckets::createBucket('pwa', 'Файлове качени с PWA', '', '100MB', 'user', 'every_one');
        
        $existDArr = array();
        $dArr = type_Keylist::toArray($this->get('DOMAINS'));
        foreach ($dArr as $domainId) {
            $dRec = cms_Domains::fetch($domainId);
            if ($dRec) {
                $domainName = $dRec->domain;
                $existDArr[$domainName] = $domainName;
            }
        }

        $dQuery = cms_Domains::getQuery();
        $dQuery->notIn('domain', $existDArr);
        while ($dRec = $dQuery->fetch()) {
            core_Webroot::remove('serviceWorker.js', $dRec->id);
            core_Webroot::remove('pwa.webmanifest', $dRec->id);
        }

        $sw = getFileContent('pwa/js/sw.js');

        foreach ($dArr as $domainId) {
            $manifest = pwa_Manifest::getPWAManifest($domainId);

            $dRec = cms_Domains::fetch($domainId);
            if ($dRec->wrFiles) {
                try {
                    $inst = cls::get('archive_Adapter', array('fileHnd' => $dRec->wrFiles));

                    $entries = $inst->getEntries();

                    if(is_array($entries) && countR($entries)) {
                        foreach($entries as $i => $e) {
                            if(preg_match("/[a-z0-9\\-\\_\\.]+/i", $e->path)) {
                                if ((trim(strtolower($e->path)) === 'serviceworker.js') || (trim(strtolower($e->path)) === 'pwa.webmanifest')) {
                                    $fh = $inst->getFile($i);
                                    $fiContent = fileman_Files::getContent($fh);
                                    if ((trim(strtolower($e->path)) === 'serviceworker.js')) {
                                        $sw = $fiContent;
                                    } else {
                                        $manifest = $fiContent;
                                    }
                                }
                            }
                        }
                    }
                } catch (Archive_7z_Exception $e) {
                    wp($e);
                }
            }

            if (core_Webroot::isExists('pwa.webmanifest', $domainId)) {
                $pwaPrevContent = core_Webroot::getContents('pwa.webmanifest', $domainId);
            } else {
                $pwaPrevContent = '';
            }
            if ($pwaPrevContent != $manifest) {
                core_Webroot::remove('pwa.webmanifest', $domainId);
                core_Webroot::register($manifest, 'Content-Type: application/json', 'pwa.webmanifest', $domainId);

                $html .= '<li>Генериране на манифест на PWA за ' . cms_Domains::fetchField($domainId, 'domain') . '</li>';
            }

            if (core_Webroot::isExists('serviceworker.js', $domainId)) {
                $swPrevContent = @core_Webroot::getContents('serviceworker.js', $domainId);
            } else {
                $swPrevContent = '';
            }

            if ($swPrevContent != $sw) {
                core_Webroot::remove('serviceworker.js', $domainId);
                core_Webroot::register($sw, 'Content-Type: text/javascript', 'serviceworker.js', $domainId);

                $html .= '<li>Регистриране на PWA за ' . cms_Domains::fetchField($domainId, 'domain') . '</li>';
            }
        }

        return $html;
    }
}
