<?php
use HeadlessChromium\BrowserFactory;

/**
 * Дефинира име на папка в която ще се съхраняват временните данни
 */
defIfNot('CHROMEPHP_TEMP_DIR', EF_TEMP_PATH . '/chromephp');

/**
 *
 *
 * @category  bgerp
 * @package   chromephp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @title    Chrome PHP конвертиране
 */
class chromephp_Browser
{

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_ConvertToPdfIntf';


    /**
     * Конвертира html към pdf файл
     *
     * @param string $html       - HTML стинга, който ще се конвертира
     * @param string $fileName   - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     * @param array  $jsArr      - Масив с JS и JQUERY_CODE
     *
     * @return string|NULL $fh - Файлов манипулатор на новосъздадения pdf файл
     *
     * @see doc_ConvertToPdfIntf
     */
    public function convert($html, $fileName, $bucketName, $jsArr = array())
    {
        expect($this->isEnabled());

        core_Composer::isInUse();

        // Зареждаме опаковката
        $wrapperTpl = cls::get('page_Print');

        // Обхождаме масива с JS файловете
        foreach ((array) $jsArr['JS'] as $js) {

            // Добавяме в шаблона
            $wrapperTpl->push($js, 'JS');
        }

        // Обхождаме масива с JQUERY кодовете
        if (isset($jsArr['JQUERY_CODE']) && countR((array) $jsArr['JQUERY_CODE'])) {

            // Обхождаме JQuery кодовете
            foreach ((array) $jsArr['JQUERY_CODE'] as $jquery) {

                // Добавяме кодовете
                jquery_Jquery::run($wrapperTpl, $jquery);
            }
        }

        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($html, 'PAGE_CONTENT');

        // Вземаме съдържанието
        // Трети параметър трябва да е TRUE, за да се вземе и CSS
        $html = $wrapperTpl->getContent(null, 'CONTENT', true);

        $browserFactory = new BrowserFactory(self::getBinPath() ?: null);
        $browserFactory->addOptions([
            'ignoreCertificateErrors' => true,
            'enableImages' => true,
            // Разрешава WebSocket връзката на chrome-php към Chrome DevTools.
            // По-новите версии на Chrome иначе могат да върнат 403 при handshake-а.
            'customFlags' => ['--remote-allow-origins=*'],
        ]);

        try {
            $browser = $browserFactory->createBrowser();
        } catch (\RuntimeException $e) {
            // Chrome sandbox-а изисква user namespaces, които често са забранени за www-data
            $browserFactory->addOptions(['noSandbox' => true]);
            $browser = $browserFactory->createBrowser();
        }

        $page = $browser->createPage();

        $page->setHtml($html, 10000);

        $optArr = array();
        $optArr['printBackground'] = true;
        $optArr['displayHeaderFooter'] = false;

        if (chromephp_Setup::get('SHOW_PAGE_NUMBERS') == 'yes') {
            $optArr['displayHeaderFooter'] = true;
            $optArr['footerTemplate'] = '<style type="text/css">.footer{font-size:8px;width:100%;text-align:center;color:#000;padding-left:0.65cm;}</style><div class="footer"><span class="pageNumber"></span> / <span class="totalPages"></span></div>';
            $optArr['headerTemplate'] = '<span></span>';
        }
        core_Debug::startTimer('CHROMEPHP_CONVERT_TO_PDF');
        $x = base64_decode($page->pdf($optArr)->getBase64());
        core_Debug::stopTimer('CHROMEPHP_CONVERT_TO_PDF');
        $fh = fileman::absorbStr($x, $bucketName, $fileName);

        return $fh;
    }


    /**
     * Проверява дали има функция за конвертиране
     *
     * @return bool
     *
     * @see doc_ConvertToPdfIntf
     */
    public function isEnabled()
    {
        $pVersion = phpversion();
        if ((version_compare($pVersion, '7.4') < 0)) {

            return false;
        }

        if (!core_Composer::isInUse()) {

            return false;
        }

        if (!core_Composer::isInstalled('chrome-php/chrome')) {

            return false;
        }

        return (bool) self::getBinPath();
    }


    /**
     * Връща пътя до работещ изпълним файл на Chrome или FALSE, ако няма такъв
     *
     * @return string|FALSE
     */
    public static function getBinPath()
    {
        static $binPath = null;

        if (isset($binPath)) {

            return $binPath;
        }

        $binPath = false;

        // Зададеният в конфигурацията бинарник
        $confPath = trim((string) chromephp_Setup::get('BIN_PATH'));
        if (strlen($confPath) && is_executable($confPath)) {

            return $binPath = $confPath;
        }

        // Системно инсталиран Chrome/Chromium
        foreach (array('chrome', 'google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser') as $bin) {
            $found = trim((string) shell_exec("command -v {$bin} 2>/dev/null"));
            if (strlen($found)) {

                return $binPath = $found;
            }
        }

        return $binPath;
    }
}
