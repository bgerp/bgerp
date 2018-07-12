<?php


/**
 *
 *
 * @category  vendors
 * @package   rtac
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rtac_yuku_Textcomplete extends core_Manager
{
    public $interfaces = 'rtac_AutocompleteIntf';
    
    
    public $title = 'Yuku textcomplete';
    
    
    /**
     * Добавя необходимите неща за да работи плъгина
     *
     * @param core_Et $tpl
     *
     * @see rtac_AutocompleteIntf::loadPacks(&$tpl)
     */
    public static function loadPacks(&$tpl)
    {
        $conf = core_Packs::getConfig('rtac');
        $tpl->push('rtac/yuku/' . $conf->RTAC_YUKU_VERSION . '/jquery.textcomplete.js', 'JS');
        
        $tpl->push('rtac/yuku/autocomplete.css', 'CSS');
    }
    
    
    /**
     * Стартира autocomplete-а за добавяне на потребители
     *
     * @param core_Et $tpl
     * @param string  $rtId
     *
     * @see rtac_AutocompleteIntf::runAutocompleteUsers(&$tpl, $rtId)
     */
    public static function runAutocompleteUsers(&$tpl, $rtId)
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Максималния брой на елементи, които
        $maxCount = $conf->RTAC_MAX_SHOW_COUNT;
        
        jquery_Jquery::run($tpl, "
        	$('#{$rtId}').textcomplete(
                {
                    match: /\B@((\w|\.|[А-Яа-я])*)$/i,
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
        ", true);
    }
    
    
    /**
     * Стартира autocomplete-а за добавяне на текст
     *
     * @param core_Et $tpl
     * @param string  $textId
     *
     * @see rtac_AutocompleteIntf::runAutocompleteUsers(&$tpl, $rtId)
     */
    public static function runAutocompleteText(&$tpl, $textId)
    {
        $conf = core_Packs::getConfig('rtac');
        
        // Максималния брой на елементи, които
        $maxCount = $conf->RTAC_MAX_SHOW_COUNT;
        
        jquery_Jquery::run($tpl, "
        	$('#{$textId}').textcomplete(
                {
                    match: /([^\s]*)$/,
                    index: 1,
                    search: function (term, callback) {
                		
                    	
                        callback($.map(rtacObj.textCompleteObj.{$textId}, function (element) {
                        	
                        	if (typeof term == 'undefined') return ;
                        	if ((term == ' ') || (term == '')) return ;
                        	term = term.toLowerCase();
                        	
                        	var text = element.toLowerCase();
                        	return text.indexOf(term) === 0 ? element : null;
                    	}));
                    },
                    replace: function (textComplete) {
                        
                        return textComplete + rtacObj.textCompleteStrEnd.{$textId};
                    },
                    maxCount: {$maxCount},
                    cache: true,
                    template: function(textComplete) {
                    	return textComplete;
    				}
                }
            );
        ", true);
    }
}
