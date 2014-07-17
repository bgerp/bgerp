<?php


/**
 * 
 *
 * @category  vendors
 * @package   rtac
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rtac_yuku_Textcomplete extends core_Manager
{   
    
    
    /**
     * 
     */
    var $interfaces = 'rtac_AutocompleteIntf';
    
    
    /**
     * 
     */
    var $title = 'Yuku textcomplete';
    
    
    /**
     * Добавя необходимите неща за да работи плъгина
     * 
     * @param core_Et $tpl
     */
    static function loadPacks(&$tpl)
    {
        $conf = core_Packs::getConfig('rtac');
        $tpl->push("rtac/yuku/" . $conf->RTAC_YUKU_VERSION . "/jquery.textcomplete.min.js", "JS");
        
        $tpl->push("rtac/yuku/autocomplete.css", "CSS");
    }
    
    
    /**
     * Стартира autocomplete-а за добавяне на потребители
     * 
     * @param core_Et $tpl
     * @param string $id
     */
    static function runAutocompleteUsers(&$tpl, $rtId)
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Максималния брой на елементи, които 
        $maxCount = $conf->RTAC_MAX_SHOW_COUNT;
        
        jquery_Jquery::run($tpl, "
        	var sharedUsers = sharedUsersObj.{$rtId};
        	
        	$('#{$rtId}').textcomplete(
                {
                    match: /\B@((\w|\.)*)$/,
                    index: 1,
                    search: function (term, callback) {
                        callback($.map(sharedUsersObj.{$rtId}, function (name, nick) {
                        	term = term.toLowerCase();
                        	return nick.indexOf(term) === 0 ? nick : null;
                        }));
                    },
                    replace: function (nick) {
                        return '@' + nick + ' ';
                    },
                    maxCount: {$maxCount},
                    cache: true,
                    template: function(val) {
                    	return val + ' ' + '<span class=\'autocomplete-name\'>' + sharedUsersObj.{$rtId}[val] + '</span>';
    				}
                }
            );
        ");
    }
}
