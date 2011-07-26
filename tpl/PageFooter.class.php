<?php

/**
 * Клас 'tpl_PageFooter' - Долния завършек на страницата
 *
 * Файлът може да се подмени с друг
 *
 * @category   Experta Framework
 * @package    tpl
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class tpl_PageFooter extends core_ET {
    
    
    /**
     *  @todo Чака за документация...
     */
    function tpl_PageFooter()
    {
        if(Mode::is('screenMode', 'narrow')) {
            if($nick = Users::getCurrent('nick')) {
                $this->append(ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Изход на " . $nick) ));
                $this->append("&nbsp;|&nbsp;");
            }
            $this->append("<a href='#top'>" . tr('Горе') . "</a>");
            
            $this->append("&nbsp;|&nbsp;");
            $this->append(ht::createLink( tr("Широк"), array('core_Browser', 'setWideScreen', 'ret_url' => TRUE) ));
            
            $this->append("&nbsp;|&nbsp;");
            $this->append( ht::createLink( dt::mysql2verbal(dt::verbal2mysql(), 'H:i'), array('Index', 'default'), NULL, array('title' => tr('Страницата е заредена на') . ' ' . dt::mysql2verbal(dt::verbal2mysql(), 'd-m H:i:s') )) );
        } else {
            if($nick = Users::getCurrent('nick')) {
                
                $this->append(ht::createLink("&nbsp;" . tr('изход') . ":" . $nick, array('core_Users', 'logout')) );
                $this->append('&nbsp;|');
            }
            
            $this->append('&nbsp;');
            $this->append(dt::mysql2verbal(dt::verbal2mysql()));
            
            $this->append(" | ");
            $this->append(ht::createLink( tr("Тесен"), array('core_Browser', 'setNarrowScreen', 'ret_url' => TRUE)));
            
            // Добавяме кода, за определяне параметрите на браузъра
            $Browser = cls::get('core_Browser');
            $this->append($Browser->renderBrowserDetectingCode());
            
            if( isDebug() ) {
                $this->append('&nbsp;|&nbsp;<a href="#wer" onclick="toggleDisplay(\'debug_info\')">Debug</a>');
                
                $this->append('<div id="debug_info" style="margin:5px; display:none;">');
                $this->append(" Време за изпълнение: " . DEBUG::getExecutionTime());
                // Вкарваме съдържанието на дебъгера
                $this->append(Debug::getLog());
                $this->append('</div>');
            }
        }
    }
}