<?php


/**
 * API за конвертиране на PDF чрез Poppler (pdftocairo)
 *
 * @category  bgerp
 * @package   poppler
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class poppler_Api
{
    /**
     * Конвертира PDF към JPG чрез `pdftocairo` (poppler-utils) и връща манипулатор(и) в fileman.
     *
     * @param mixed $fileHnd - манипулатор на PDF файла (или път до файл на диска)
     * @param array $params  - опции:
     *                        'page'       => null|0 - всички страници; int - конкретна страница (1-базирано)
     *                        'resolution' => разделителна способност (DPI); по подразбиране POPPLER_DEFAULT_RESOLUTION
     *                        'bucket'     => име на кофата за резултата; по подразбиране 'fileIndex'
     *
     * @return string|array|null
     *                          - при зададена конкретна страница: манипулатор на един JPG файл
     *                          - при всички страници: масив [номер на страница => манипулатор]
     *                          - null при неуспех
     */
    public static function convertPdfToJpg($fileHnd, $params = array())
    {
        if (!$fileHnd) {

            return null;
        }

        cls::load('fileman_Files');

        // Име на файла без разширението - ползва се за префикс на изходните файлове
        $name = fileman_Files::getFileNameWithoutExt($fileHnd);
        if (!strlen($name)) {
            $name = 'pdf';
        }

        // Разделителна способност
        $resolution = isset($params['resolution']) ? (int) $params['resolution'] : (int) poppler_Setup::get('DEFAULT_RESOLUTION');
        if ($resolution <= 0) {
            $resolution = 150;
        }

        // Конкретна страница или всички
        $page = isset($params['page']) ? (int) $params['page'] : 0;
        $singlePage = ($page > 0);

        $bucketName = !empty($params['bucket']) ? $params['bucket'] : 'fileIndex';

        // Инстанция на конвертиращия скрипт
        $Script = cls::get('fconv_Script');

        // Изходът на pdftocairo е ПРЕФИКС - файловете се създават като `<префикс>-<стр>.jpg`
        // (или `<префикс>.jpg` при -singlefile). Държим ги в tempDir на скрипта.
        $outPrefix = $Script->tempDir . $name;

        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outPrefix);

        $Script->setProgram('pdftocairo', poppler_Setup::get('PDFTOCAIRO_PATH'));

        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outPrefix);

        // Команда за конвертиране
        $lineExec = 'pdftocairo -jpeg -r ' . $resolution;
        if ($singlePage) {
            $lineExec .= ' -f ' . $page . ' -l ' . $page . ' -singlefile';
        }
        $lineExec .= ' [#INPUTF#] [#OUTPUTF#]';

        $Script->lineExec($lineExec, array('errFilePath' => $errFilePath));

        $Script->setCheckProgramsArr('pdftocairo');

        // Стартираме скрипта синхронно
        $Script->run(false);

        // Събираме генерираните JPG файлове от tempDir и ги вкарваме в fileman
        $resArr = self::absorbOutputFiles($Script->tempDir, $name, $bucketName);

        if (!empty($resArr)) {
            // Изтриваме временната директория с всички файлове вътре
            if ($Script->tempDir) {
                core_Os::deleteDir($Script->tempDir);
            }
        } else {
            if (is_file($errFilePath)) {
                $err = @file_get_contents($errFilePath);
                log_System::add('poppler_Api', 'Грешка при конвертиране на PDF към JPG: ' . $err, null, 'err');
            }

            return null;
        }

        // При конкретна страница връщаме единичния манипулатор
        if ($singlePage) {

            return reset($resArr);
        }

        return $resArr;
    }


    /**
     * Намира генерираните от pdftocairo JPG файлове в директорията и ги вкарва в fileman.
     *
     * @param string $tempDir    - директория, в която pdftocairo е записал файловете
     * @param string $name       - префикс на изходните файлове (име без разширение)
     * @param string $bucketName - кофа за записване в fileman
     *
     * @return array - [номер на страница => манипулатор на файл]
     */
    protected static function absorbOutputFiles($tempDir, $name, $bucketName)
    {
        $resArr = array();

        if (!is_dir($tempDir)) {

            return $resArr;
        }

        // Търсим `<name>-<стр>.jpg` (много страници) или `<name>.jpg` (една страница)
        $pattern = '/^' . preg_quote($name, '/') . '(?:-(\d+))?\.jpg$/i';

        $filesArr = array();
        foreach ((array) scandir($tempDir) as $fName) {
            if (!preg_match($pattern, $fName, $m)) {
                continue;
            }

            $pageNo = (isset($m[1]) && $m[1] !== '') ? (int) $m[1] : 1;
            $filesArr[$pageNo] = $tempDir . $fName;
        }

        ksort($filesArr, SORT_NUMERIC);

        foreach ($filesArr as $pageNo => $filePath) {
            if ($fh = fileman::absorb($filePath, $bucketName)) {
                $resArr[$pageNo] = $fh;
            }
        }

        return $resArr;
    }
}
