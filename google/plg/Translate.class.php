<?php


/**
 * @deprecated
 */
class google_plg_Translate extends core_Plugin
{
    protected static $initJs = <<<EOT
function googleSectionalElementInit() {
    new google.translate.SectionalElement({
        sectionalNodeClassName: 'goog-trans-section',
        controlNodeClassName: 'goog-trans-control',
        background: '#f4fa58'
    }, 'google_sectional_element');
} 
EOT;
    
    protected static $elementJsUrl = '//translate.google.com/translate_a/element.js?cb=googleSectionalElementInit&amp;ug=section&amp;hl=%s';

    protected static $markupTpl = <<<EOT
<div class="goog-trans-section">
    <div class="goog-trans-control"></div>
    %s
</div>
EOT;
    
    protected static $css = <<<EOT
.goog-trans-section {
    border: none;
    padding: 5px;
    margin: -5px;
    width: 100%;
}

.goog-trans-section .goog-trans-control {
    display: block;
    float: right;
    margin: 0.1em 0.5em;
}
@media print {
.goog-trans-control, .goog-te-sectional-gadget-checkbox-text, .goog-te-sectional-gadget-link-text {
    display:none !important;
}
}

EOT;

    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    { 
        if ($rec->lg != core_Lg::getCurrent() && 
            !(Mode::is('text', 'xhtml') && !Mode::is('printing')) &&
            !Mode::is('text', 'plain')  && 
            $fields['-single'] && trim($row->textPart)
             ) {

            $row->textPart = new core_ET(
                sprintf(static::$markupTpl, $row->textPart)
            );
            
            if(!Request::get('ajax_mode')) {
                $row->textPart->push(sprintf(static::$elementJsUrl, core_Lg::getCurrent()), 'JS');
                $row->textPart->appendOnce(static::$initJs, 'SCRIPTS');
                $row->textPart->appendOnce(static::$css, 'STYLES');
            }
        }
    }
}