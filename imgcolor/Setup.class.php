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
 * Класификация на преливките (CMYK отделяне) - прагове на
 * imgcolor_TransitionClassifier. Единици и ефект - виж
 * docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md §4.
 */
defIfNot('IMGCOLOR_TRANS_SPAN', 4);
defIfNot('IMGCOLOR_TRANS_NOISE_DELTAE', 1.0);
defIfNot('IMGCOLOR_TRANS_COHERENCE_MIN', 0.4);
defIfNot('IMGCOLOR_TRANS_AA_RADIUS', 3);
defIfNot('IMGCOLOR_TRANS_MIN_SEED', 20);
defIfNot('IMGCOLOR_TRANS_EDGE_DELTAE', 10.0);
defIfNot('IMGCOLOR_TRANS_MIN_COVERAGE', 0.005);


/**
 * RGB -> CMYK конверсия за акумулатора на преливките. Без включени ICC
 * профили в пакета - при 'auto' без конфигурирани профили се ползва
 * математическата апроксимация (записва се в резултата като fallback).
 */
defIfNot('IMGCOLOR_CMYK_ENGINE', 'auto');
defIfNot('IMGCOLOR_CMYK_ICC_RGB_PROFILE', '');
defIfNot('IMGCOLOR_CMYK_ICC_CMYK_PROFILE', '');


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
    public $version = '0.4';


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
        'imgcolor_Analyses',
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
        array(9.91, 'Инструменти', 'Профили за калибриране', 'imgcolor_Profiles', 'list', 'imgcolor, ceo, admin'),
        array(9.92, 'Инструменти', 'История на анализите', 'imgcolor_Analyses', 'list', 'imgcolor, ceo, admin'),
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

        'IMGCOLOR_TRANS_SPAN' => array('int', 'caption=Преливки->Обхват на пробите (px)'),
        'IMGCOLOR_TRANS_NOISE_DELTAE' => array('double', 'caption=Преливки->Шумов праг (deltaE)'),
        'IMGCOLOR_TRANS_COHERENCE_MIN' => array('double', 'caption=Преливки->Мин. кохерентност (косинус)'),
        'IMGCOLOR_TRANS_AA_RADIUS' => array('int', 'caption=Преливки->Радиус на ерозия (px)'),
        'IMGCOLOR_TRANS_MIN_SEED' => array('int', 'caption=Преливки->Мин. пиксели за seed'),
        'IMGCOLOR_TRANS_EDGE_DELTAE' => array('double', 'caption=Преливки->Праг твърд ръб (deltaE)'),
        'IMGCOLOR_TRANS_MIN_COVERAGE' => array('double', 'caption=Преливки->Мин. покритие (дял)'),

        'IMGCOLOR_CMYK_ENGINE' => array('enum(auto=Автоматично,math=Математическа формула,imagick=Imagick + ICC)', 'caption=CMYK->Енджин'),
        'IMGCOLOR_CMYK_ICC_RGB_PROFILE' => array('varchar', 'caption=CMYK->Път до RGB ICC профил'),
        'IMGCOLOR_CMYK_ICC_CMYK_PROFILE' => array('varchar', 'caption=CMYK->Път до CMYK ICC профил'),
    );


    /**
     * Действия при инсталиране
     *
     * @return string
     */
    public function install()
    {
        $html = parent::install();

        $html .= fileman_Buckets::createBucket('imgcolorImages', 'Изображения за цветови анализ', 'jpg,jpeg,png', '50MB', 'imgcolor,ceo,admin', 'imgcolor,ceo,admin');

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
            imgcolor_TransitionClassifier::normalizeParams(imgcolor_Analyzer::getTransParams());
            new imgcolor_CmykConverter(imgcolor_Analyzer::getCmykConfig());
        } catch (InvalidArgumentException $e) {

            return 'Некоректна imgcolor конфигурация: ' . $e->getMessage();
        }
    }
}
