<?php
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Message;

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

        // A4 размери и използваема ширина при зададените по-долу PDF полета
        $paperWidth = 8.27;
        $paperHeight = 11.69;
        $horizontalMargin = 0.1;
        $verticalMargin = 0.2;
        $portraitWidth = (int) round(($paperWidth - 2 * $horizontalMargin) * 96);
        $portraitHeight = (int) round(($paperHeight - 2 * $verticalMargin) * 96);
        $landscapeWidth = (int) round(($paperHeight - 2 * $horizontalMargin) * 96);
        $landscapeHeight = (int) round(($paperWidth - 2 * $verticalMargin) * 96);
        $page->setViewport($portraitWidth, $portraitHeight)->await();
        $page->getSession()->sendMessage(
            new Message('Emulation.setEmulatedMedia', array('media' => 'print'))
        )->waitForResponse();

        $page->setHtml($html, 10000);

        $reportColumnCount = (int) $page->evaluate(
            "(() => {
                let maxColumns = 0;
                document.querySelectorAll(\".reportHolder table.frame2Table tr\").forEach((row) => {
                    let columns = 0;
                    Array.from(row.cells).forEach((cell) => {
                        columns += cell.colSpan || 1;
                    });
                    maxColumns = Math.max(maxColumns, columns);
                });

                return maxColumns;
            })()"
        )->getReturnValue();
        $useWideReportLayout = $reportColumnCount > 6;

        if ($useWideReportLayout) {
            // Нормализиране на инлайннатия screen layout само за широки PDF справки
            $page->evaluate(
                "document.querySelectorAll(\"html, body, .printing, .printing > .wide, .singleView, .document\").forEach((element) => {
                    element.style.setProperty(\"width\", \"100%\", \"important\");
                    element.style.setProperty(\"max-width\", \"none\", \"important\");
                    element.style.setProperty(\"margin-left\", \"0\", \"important\");
                    element.style.setProperty(\"margin-right\", \"0\", \"important\");
                    element.style.setProperty(\"padding-left\", \"0\", \"important\");
                    element.style.setProperty(\"padding-right\", \"0\", \"important\");
                    element.style.setProperty(\"box-sizing\", \"border-box\", \"important\");
                });
                document.querySelectorAll(\".singleView\").forEach((element) => {
                    element.style.setProperty(\"display\", \"block\", \"important\");
                });
                document.querySelectorAll(\".reportHolder\").forEach((holder) => {
                    holder.style.setProperty(\"width\", \"100%\", \"important\");
                    holder.style.setProperty(\"max-width\", \"none\", \"important\");
                    holder.style.setProperty(\"margin-left\", \"0\", \"important\");
                    holder.style.setProperty(\"margin-right\", \"0\", \"important\");
                    holder.style.setProperty(\"overflow\", \"visible\", \"important\");
                    holder.style.setProperty(\"overflow-x\", \"visible\", \"important\");
                    holder.style.setProperty(\"overflow-y\", \"visible\", \"important\");
                });
                document.querySelectorAll(\".reportHolder table.frame2Table\").forEach((table) => {
                    table.style.setProperty(\"display\", \"table\", \"important\");
                    table.style.setProperty(\"width\", \"100%\", \"important\");
                    table.style.setProperty(\"max-width\", \"none\", \"important\");
                    table.style.setProperty(\"table-layout\", \"auto\", \"important\");
                    table.style.setProperty(\"margin-left\", \"0\", \"important\");
                });"
            )->waitForResponse();
        } else {
            // Стандартни A4 полета и оригинална ширина за малките справки
            $horizontalMargin = 0.4;
            $verticalMargin = 0.4;
            $portraitWidth = (int) round(($paperWidth - 2 * $horizontalMargin) * 96);
            $portraitHeight = (int) round(($paperHeight - 2 * $verticalMargin) * 96);
            $page->setViewport($portraitWidth, $portraitHeight)->await();
        }

        $optArr = array();
        $optArr["printBackground"] = true;
        $optArr["displayHeaderFooter"] = false;
        $optArr["paperWidth"] = $paperWidth;
        $optArr["paperHeight"] = $paperHeight;
        $optArr["marginLeft"] = $horizontalMargin;
        $optArr["marginRight"] = $horizontalMargin;
        $optArr["marginTop"] = $verticalMargin;
        $optArr["marginBottom"] = $verticalMargin;

        if ($useWideReportLayout) {
            // Адаптиране на широките таблици, без да се променя подредбата на клетките
            $reportTableWidthScript = "(() => {
                let maxWidth = 0;
                document.querySelectorAll(\".reportHolder table.frame2Table\").forEach((table) => {
                    const rect = table.getBoundingClientRect();
                    const tableWidth = Math.max(table.scrollWidth, rect.width);
                    const rightEdge = rect.right - Math.min(0, rect.left);
                    maxWidth = Math.max(maxWidth, tableWidth, rightEdge);
                });

                return maxWidth;
            })()";
            $reportTableWidth = (float) $page->evaluate($reportTableWidthScript)->getReturnValue();

            if ($reportTableWidth > $portraitWidth) {
                $portraitScale = $portraitWidth / $reportTableWidth;

                if ($portraitScale >= 0.8) {
                    $optArr["scale"] = round(0.98 * $portraitScale, 3);
                } else {
                    $optArr["landscape"] = true;
                    $page->setViewport($landscapeWidth, $landscapeHeight)->await();
                    $reportTableWidth = (float) $page->evaluate($reportTableWidthScript)->getReturnValue();
                    $landscapeScale = min(1, 0.99 * $landscapeWidth / $reportTableWidth);
                    $optArr["scale"] = round(max(0.1, $landscapeScale), 3);
                }
            }
        }

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
