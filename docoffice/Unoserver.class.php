<?php

/**
 * Конвертиране с Unoserver и отделен временен LibreOffice процес.
 *
 * @category vendors
 * @package docoffice
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class docoffice_Unoserver extends docoffice_Unoconv
{
    public $title = 'Unoserver';


    /** Използва общите Unoconv ключалки и callback, без да стартира стария Office listener. */
    public static function convertDoc($fileHnd, $toExt, $params = array())
    {
        $params += array('asynch' => true, 'outType' => null, 'callBack' => '');
        $library = docoffice_Setup::getUnoserverPath();
        $python = docoffice_Setup::get('OFFICE_UNOSERVER_PYTHON', true);
        cls::load('docoffice_Office');
        $Script = cls::get('fconv_Script');
        $outFilePath = $Script->tempDir . fileman_Files::getFileNameWithoutExt($fileHnd) . '.' . strtolower($toExt);
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outFilePath);
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        $Script->setParam('PYTHON', $python, true);
        $Script->setParam('WORKER', getFullPath('docoffice/unoserver/convert.py'), true);
        $Script->setParam('LIBRARY', $library, true);
        $Script->setParam('OFFICE', OFFICE_PACKET_PATH, true);
        $Script->lineExec('[#PYTHON#] [#WORKER#] [#LIBRARY#] [#OFFICE#] [#INPUTF#] [#OUTPUTF#]',
            array('LANG' => 'C.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        $Script->callBack('docoffice_Unoconv::afterConvertDoc');
        $params['errFilePath'] = $errFilePath;
        $Script->params = serialize($params);
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;
        $Script->setCheckProgramsArr($python);
        static::lockUnoconv(210, 60);
        docoffice_Office::lockOffice(210, 60);
        $started = false;
        try {
            $started = $Script->run($params['asynch']) !== false;
        } finally {
            if (!$started) {
                static::unlockUnoconv();
                docoffice_Office::unlockOffice();
            }
        }
        if (!$started) {
            if ($params['outType']) {
                $params['type'] = $params['outType'];
            }
            fileman_Indexes::createError($params);

            return;
        }

        return $Script->outFilePath;
    }
}
