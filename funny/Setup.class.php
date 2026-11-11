<?php

/** Начален режим, когато няма пакетна или лична настройка. */
defIfNot('FUNNY_MODE', 'auto');
/** Налични герои за режима "По мой избор". */
defIfNot('FUNNY_CHARACTERS', 'ghost,ghostgirl,ghostplay,casper,zombie,sleigh,elf,deer,pumpkin,vampire,bat,spiderweb,spiders,cat,blackcat,witch,muncher,snowman,martenitsa,stork,bunny,chick,butterfly,firefly');
/** Автоматична поява и визуална реакция при ново известие. */
defIfNot('FUNNY_TRIGGER', 'both');
/** Сегашните нарастващи интервали между случайните появи. */
defIfNot('FUNNY_FREQUENCY', 'normal');
/** Кратък тематичен звук; плашещият вариант се избира изрично. */
defIfNot('FUNNY_SOUND', 'yes');
/** Визуални ефекти; звукът се включва изрично. */
defIfNot('FUNNY_WEATHER', 'on');
/** Запазва досегашната сила, докато потребителят не избере по-деликатни ефекти. */
defIfNot('FUNNY_STRENGTH', 'normal');


/**
 * Локални забавни ефекти с лични настройки.
 *
 * @category  bgerp
 * @package   funny
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class funny_Setup extends core_ProtoSetup
{
    /** @var string Версия на пакета. */
    public $version = '0.1';

    /** @var string Описание в списъка с пакети. */
    public $info = 'Забавни герои - локални сезонни ефекти';

    /** @var array Общи настройки с персонализация само за powerUser. */
    public $configDescription = array(
        'FUNNY_MODE' => array('enum(auto=Според сезона,selected=По мой избор,off=Изключено)', 'caption=Забавни герои->Режим,customizeBy=powerUser,autohide=any'),
        'FUNNY_CHARACTERS' => array('set(ghost=Духче,ghostgirl=Духчица,ghostplay=Палавото духче,casper=Каспър,zombie=Зомби,sleigh=Шейна,elf=Елф,deer=Елен,pumpkin=Тиква,vampire=Вампир,bat=Прилеп,spiderweb=Паяжина,spiders=Паячета,cat=Рижа котка,blackcat=Черна котка,witch=Вещица,muncher=Лакомото чудовище,snowman=Снежен човек,martenitsa=Мартеничка,stork=Щъркел,bunny=Зайче,chick=Пиленце,butterfly=Пеперуда,firefly=Светулка)', 'caption=Забавни герои->Герои,hint=Използват се при режим По мой избор,customizeBy=powerUser,autohide=any'),
        'FUNNY_TRIGGER' => array('enum(random=Случайно,notification=При ново известие,both=Случайно и при ново известие,manual=Само при двоен клик)', 'caption=Забавни герои->Поява,customizeBy=powerUser,autohide=any'),
        'FUNNY_FREQUENCY' => array('enum(normal=Нормална,frequent=Много често,off=Изключено)', 'caption=Забавни герои->Честота,hint=Честота на случайните появи. Изключено спира всички появи включително при двоен клик и известие,customizeBy=powerUser,autohide=any'),
        'FUNNY_SOUND' => array('enum(no=Изключен,yes=Тих и кратък,scary=Плашещ)', 'caption=Забавни герои->Звук (без известията),customizeBy=powerUser,autohide=any'),
        'FUNNY_STRENGTH' => array('enum(normal=Нормални,gentle=Деликатни)', 'caption=Общи ефекти->Сила,hint=Деликатни намалява броя на героите и частиците както и наситеността и звука,customizeBy=powerUser,autohide=any'),
        'FUNNY_WEATHER' => array('enum(off=Изключено,on=Включено,sound=Включено със звук)', 'caption=Време->Ефекти,hint=На всички страници при поява на герой или самостоятелно ако героите са изключени. Почасовата прогноза се зарежда при рефреш с дневна за резервен вариант. Скриват се плавно при движение и веднага при загуба на фокус,customizeBy=powerUser,autohide=any'),
    );


    /**
     * Включва ефектите чрез разширението на активните страници.
     *
     * @return string Резултат от инсталирането
     */
    public function install()
    {
        $html = parent::install();
        $html .= core_Plugins::installPlugin('Локални забавни герои', 'funny_Plugin', 'core_page_Active', 'family');

        return $html;
    }
}
