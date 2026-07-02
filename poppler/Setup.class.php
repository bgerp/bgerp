<?php


/**
 * Път до изпълнимия файл `pdftocairo` (от пакета poppler-utils)
 */
defIfNot('POPPLER_PDFTOCAIRO_PATH', 'pdftocairo');


/**
 * Разделителна способност по подразбиране (DPI) при конвертиране към изображение
 */
defIfNot('POPPLER_DEFAULT_RESOLUTION', 150);


/**
 * Конвертиране на PDF чрез Poppler (poppler-utils)
 *
 * Изисква инсталиран `poppler-utils` на сървъра (pdftocairo).
 * Инсталация: `sudo apt-get install poppler-utils`
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
class poppler_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';


    /**
     * Описание на модула
     */
    public $info = 'Конвертиране на PDF чрез Poppler (pdftocairo)';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'POPPLER_PDFTOCAIRO_PATH' => array('varchar', 'caption=Път до pdftocairo->Път'),
        'POPPLER_DEFAULT_RESOLUTION' => array('int(min=1)', 'caption=Разделителна способност по подразбиране->DPI, unit=dpi'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Проверяваме дали pdftocairo е наличен на сървъра
        $path = poppler_Setup::get('PDFTOCAIRO_PATH');
        @exec(escapeshellcmd($path) . ' -v 2>&1', $out, $returnVar);

        if ($returnVar === 0 || stripos(implode(' ', (array) $out), 'pdftocairo') !== false) {
            $html .= "<li class='debug-new'>Намерен е <b>pdftocairo</b> ({$path})</li>";
        } else {
            $html .= "<li class='debug-error'>Не е намерен <b>pdftocairo</b> ({$path}). Инсталирайте `poppler-utils` на сървъра.</li>";
        }

        return $html;
    }
}
