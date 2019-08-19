<?php


/**
 * Клас 'tooltip_Plugin'
 *
 *
 * @category  bgerp
 * @package   tooltip
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tooltip_Plugin
{
    public static function renderView($text, $isShown, $icon = 'img/mark.png', $closeURL)
    {
        $imageUrl = sbf($icon, '');
        $img = ht::createElement('img', array('src' => $imageUrl, 'alt' => 'help', 'width' => 24));
        
        $tpl = new ET("<a class='tooltip-button'>[#1#]</a>", $img);
        
        if ($isShown) {
            $mustSeeClass = 'show-tooltip';
        }
        $block = new ET("<div class='tooltip-text {$mustSeeClass}'><div class='tooltip-arrow'></div><a class='close-tooltip'></a>[#1#]</div>", $text);
        $tpl->append($block);
        
        $tpl->push('tooltip/lib/tooltip.css', 'CSS');
        $tpl->push('tooltip/lib/tooltipCustom.js', 'JS');
        
        jquery_Jquery::run($tpl, "\n tooltipCustom('{$closeURL}');", true);
        
        return $tpl;
    }
}
