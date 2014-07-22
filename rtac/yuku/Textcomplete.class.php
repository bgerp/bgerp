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
     * @see rtac_AutocompleteIntf::loadPacks(&$tpl)
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
     * @see rtac_AutocompleteIntf::runAutocompleteUsers(&$tpl, $rtId)
     */
    static function runAutocompleteUsers(&$tpl, $rtId)
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Максималния брой на елементи, които 
        $maxCount = $conf->RTAC_MAX_SHOW_COUNT;
        
        jquery_Jquery::run($tpl, "
        	$('#{$rtId}').textcomplete(
                {
                    match: /\B@((\w|\.)*)$/,
                    index: 1,
                    search: function (term, callback) {
                        getEfae().process({url: rtacObj.shareUsersURL.{$rtId}}, {term:term, roles:rtacObj.shareUserRoles.{$rtId}, rtid: '{$rtId}'}, false);
                        callback(rtacObj.sharedUsers.{$rtId});
                    },
                    replace: function (userObj) {
                        return '@' + userObj.nick + ' ';
                    },
                    maxCount: {$maxCount},
                    cache: true,
                    template: function(userObj) {
                    	return userObj.nick + ' ' + '<span class=\'autocomplete-name\'>' + userObj.names + '</span>';
    				}
                }
            );
        ", TRUE);
    }

    
    /**
     * Стартира autocomplete-а за добавяне на блокови елементи
     * 
     * @param core_Et $tpl
     * @param string $id
     * @see rtac_AutocompleteIntf::runAutocompleteBlocks(&$tpl, $rtId)
     */
    static function runAutocompleteBlocks(&$tpl, $rtId)
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Максималния брой на елементи, които 
        $maxCount = $conf->RTAC_MAX_SHOW_COUNT;
        
        jquery_Jquery::run($tpl, "
        	$('#{$rtId}').textcomplete(
                {
                    match: /\[(\w*)$/,
                    index: 1,
                    search: function (term, callback) {
                        callback($.map(rtacObj.blockElementsObj.{$rtId}, function (element) {
                        	term = term.toLowerCase();
                        	var text = element.text.toLowerCase();
                            return text.indexOf(term) === 0 ? element : null;
                        }));
                    },
                    replace: function (element) {
                    	var begin = (element.begin) ? element.begin : element.text;
                    	var end = (element.end) ? element.end : element.text;
                        return ['[' + begin + ']', '[/' + end + ']'];
                    },
                    maxCount: {$maxCount},
                    cache: true,
                    template: function(val) {
                    	var text = '<span class=\'autocomplete-block\'>';
                    	if (val.icon) {
                    		text += '<img class=\'autocomplete-block-image\' src=' + val.icon + '></img>';
    					}
        				text += '<span class=\'autocomplete-block-title\'>' + val.title + '</span>' + '<span class=\'autocomplete-block-text\'>' + val.text + '</span></span>';
        				
        				return text;
    				}
                }
            );
        ", TRUE);
    }
}
