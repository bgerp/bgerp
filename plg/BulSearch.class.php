<?php


/**
 * Клас 'plg_BulSearch' - Подобрява търсенето на български
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_BulSearch extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterParseSearchQuery(&$mvc, &$words)
    {
        foreach ($words as &$w) {
            $w = preg_replace('/^([\\p{Cyrillic}]{5,})(и|ен|ът|ав|ан|ов|ят|ев|ащ)$/ui', '$1', $w);
            $w = preg_replace('/^([\\p{Cyrillic}]{4,})(а|о|у|е|я|и|че|нце)$/ui', '$1', $w);
        }
    }
}
