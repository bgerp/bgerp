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
     * Извиква се преди изпълняването на екшън
     */
    public static function on_AfterAction($mvc, &$res, $action)
    {
        if (($action != 'changepassword' && $mvc->className != 'crm_Profiles') && ($action != 'login' && $mvc->className != 'core_Users')) {

            return ;
        }

        $res->push('zxcvbn/dropboxLib/zxcvbn.js', 'JS');

        $minPoints = zxcvbn_Setup::get('MIN_SCORE');

        $warningTxt = tr('Паролата е много слаба.') . ' ';
        if ($action == 'login' && $mvc->className == 'core_Users') {
            $warningTxt .= tr('Сменете я след логване.');
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
                        passColor = 'red';
                        var zxcvbnRes = zxcvbn(val);
                        if (zxcvbnRes.score < {$minPoints}) {
                            if (showWarning) {
                                alert('{$warningTxt}');
                            }
                        } else {
                            passColor = 'green';
                            disableElVal = false;
                        }
                        if (el.classList.contains('colorPass')) {
                            el.style.backgroundColor = passColor;
                        }
                        
                        document.querySelectorAll('.checkPassDisable').forEach(function(disableEl) {
                            disableEl.disabled = disableElVal;
                        });
                    }
                }";

        $res->appendOnce($js, 'SCRIPTS');
    }
}
