<?php


/**
 *
 *
 * @category  bgerp
 * @package   zxcvbn
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class zxcvbn_Plugin extends core_Plugin
{


    /**
     *
     * Изпълнява се след рендирането на input
     *
     * @param core_Mvc $invoker
     * @param core_Et  $tpl
     * @param string   $name
     * @param string   $value
     * @param array    $attr
     */
    public function on_AfterRenderInput(&$mvc, &$tpl, $name, $value, $attr = array())
    {
        if ($mvc->params['checkPassAfterLogin'] && (zxcvbn_Setup::get('CHECK_ON_LOGIN') == 'no')) {

            return ;
        }

        $disableBtn = "document.querySelectorAll('.checkPassDisable').forEach(function(disableEl) {
                            disableEl.disabled = disableElVal;
                        });";

        if ($mvc->params['checkPassAfterLogin'] && (zxcvbn_Setup::get('CHECK_ON_LOGIN') == 'yes')) {
            $disableBtn = '';
        }

        $minPoints = zxcvbn_Setup::get('MIN_SCORE');

        if ($minPoints < 1) {

            return ;
        }

        $tpl->push('zxcvbn/dropboxLib/zxcvbn.js', 'JS', true);

        $warningTxt = tr('Паролата е много слаба.') . ' ';
        if ($mvc->params['checkPassAfterLogin']) {
            $warningTxt .= tr('Използвайте бутона "Забравена парола".');
        } else {
            $warningTxt .= tr('Опитайте да добавите големи и малки букви и/или специални символи.');
        }

        $js = "document.querySelectorAll('.checkPass').forEach(function(el) {
                    el.addEventListener('keyup', (event) => {checkPass(el);});
                    el.addEventListener('change', (event) => {checkPass(el, true);});
                });
                
                function checkPass(el, showWarning = false) {
                    var val = el.value;
                    if (val) {
                        disableElVal = true;
                        var passColor = 'red'; 
                        var zxcvbnRes = zxcvbn(val);
                        var score = zxcvbnRes.score;
                
                        // Базови цветове по нива
                        var colors = {
                            0: '#8B0000', // много слаба
                            1: '#FF4500', // слаба
                            2: '#FFD700', // средна
                            3: '#9ACD32', // добра
                            4: '#008000'  // много добра
                        };
                        
                        if (score >= {$minPoints}) {
                            passColor = colors[4];
                            disableElVal = false;
                        } else {
                            passColor = colors[score] || colors[0];
                            if (showWarning) {
                                alert('{$warningTxt}');
                            }
                            disableElVal = true;
                        }
                        
                        if (el.classList.contains('colorPass')) {
                            el.style.backgroundColor = passColor;
                        }
                        
                        {$disableBtn}
                    }
                }";

        $tpl->appendOnce($js, 'SCRIPTS');
    }
}
