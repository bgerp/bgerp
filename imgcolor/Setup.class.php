<?php


/**
 * Кой loader да се използва (GD по подразбиране; Imagick е опционален)
 */
defIfNot('IMGCOLOR_LOADER', 'gd');


/**
 * Изрязване на бял фон - прагове (CIELAB)
 */
defIfNot('IMGCOLOR_CROP_LIGHTNESS_MIN', 95.0);
defIfNot('IMGCOLOR_CROP_CHROMA_MAX', 5.0);
defIfNot('IMGCOLOR_CROP_LINE_CONTENT_FRACTION', 0.002);
defIfNot('IMGCOLOR_CROP_ALPHA_THRESHOLD', 8);


/**
 * Клъстеризиране на цветовете
 */
defIfNot('IMGCOLOR_CLUSTER_FIXED_K', '');
defIfNot('IMGCOLOR_CLUSTER_KMAX', 8);
defIfNot('IMGCOLOR_CLUSTER_HISTOGRAM_BITS', 5);
defIfNot('IMGCOLOR_CLUSTER_MERGE_DELTAE', 3.0);
defIfNot('IMGCOLOR_CLUSTER_MIN_COVERAGE', 0.01);
defIfNot('IMGCOLOR_CLUSTER_SEED', 1);
defIfNot('IMGCOLOR_CLUSTER_ALPHA_THRESHOLD', 8);


/**
 * Пакет за анализ на основните цветове за печат в изображение (PNG/JPEG)
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.1
 */
class imgcolor_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.2';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'imgcolor_Demo';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'analyze';


    /**
     * Описание на модула
     */
    public $info = 'Анализ на основните цветове за печат в изображение (PNG/JPEG)';


    /**
     * Зависимости
     */
    public $depends = 'fileman=0.1';


    /**
     * Мениджъри
     */
    public $managers = array(
        'imgcolor_Demo',
        'imgcolor_Profiles',
    );


    /**
     * Роли
     */
    public $roles = array(
        array('imgcolor'),
        array('imgcolorMaster', 'imgcolor'),
    );


    /**
     * Менюта
     */
    public $menuItems = array(
        array(9.9, 'Инструменти', 'Цветове за печат', 'imgcolor_Demo', 'analyze', 'imgcolor, ceo, admin'),
        array(9.91, 'Инструменти', 'Профили за калибриране', 'imgcolor_Profiles', 'list', 'imgcolor, ceo, admin, officer'),
    );


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'IMGCOLOR_LOADER' => array('enum(gd=GD,imagick=Imagick)', 'caption=Зареждане->Библиотека'),

        'IMGCOLOR_CROP_LIGHTNESS_MIN' => array('double', 'caption=Изрязване->Мин. светлота (L*)'),
        'IMGCOLOR_CROP_CHROMA_MAX' => array('double', 'caption=Изрязване->Макс. насищане (chroma)'),
        'IMGCOLOR_CROP_LINE_CONTENT_FRACTION' => array('double', 'caption=Изрязване->Праг съдържание на линия'),
        'IMGCOLOR_CROP_ALPHA_THRESHOLD' => array('int', 'caption=Изрязване->Праг прозрачност (0-255)'),

        'IMGCOLOR_CLUSTER_FIXED_K' => array('int', 'caption=Клъстеризиране->Фиксиран брой цветове (празно=авто)'),
        'IMGCOLOR_CLUSTER_KMAX' => array('int', 'caption=Клъстеризиране->Макс. брой цветове'),
        'IMGCOLOR_CLUSTER_HISTOGRAM_BITS' => array('int', 'caption=Клъстеризиране->Резолюция хистограма (битове/канал)'),
        'IMGCOLOR_CLUSTER_MERGE_DELTAE' => array('double', 'caption=Клъстеризиране->Сливане при deltaE под'),
        'IMGCOLOR_CLUSTER_MIN_COVERAGE' => array('double', 'caption=Клъстеризиране->Мин. покривност на клъстер'),
        'IMGCOLOR_CLUSTER_SEED' => array('int', 'caption=Клъстеризиране->Seed (детерминизъм)'),
        'IMGCOLOR_CLUSTER_ALPHA_THRESHOLD' => array('int', 'caption=Клъстеризиране->Праг прозрачност (0-255)'),
    );


    /**
     * Действия при инсталиране
     *
     * @return string
     */
    public function install()
    {
        $html = parent::install();

        $html .= fileman_Buckets::createBucket('imgcolorImages', 'Изображения за цветови анализ', '', '50MB', 'imgcolor,ceo,admin', 'imgcolor,ceo,admin');

        return $html;
    }


    /**
     * Проверка на конфигурацията
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        if (!extension_loaded('gd')) {

            return 'Липсва PHP разширението GD, което е задължително за пакета';
        }

        if (imgcolor_Setup::get('LOADER') === 'imagick' && !extension_loaded('imagick')) {

            return 'Избран е Imagick loader, но разширението imagick липсва';
        }

        try {
            imgcolor_Analyzer::buildOptions();
        } catch (InvalidArgumentException $e) {

            return 'Некоректна imgcolor конфигурация: ' . $e->getMessage();
        }
    }
}
