<?php


/**
 * Юнит тестове за email_Mime
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/115
 */
class email_tests_Mime extends unit_Class
{
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function test_parseHeaders()
    {
        $h ="Subject:
 =?iso-2022-jp?B?GyRCJzAnZidWJ2InZCdRGyhCIBskQidZJ1EbKEIgGyRCJ18nUSdbGyhC?=
 =?iso-2022-jp?B?GyRCJ10nYCdfJ2AnUydaGyhCIBskQidkJ2AnYidSJ1onaSdcJ1obKEI=?=
 =?iso-2022-jp?B?IC0gGyRCJ2MnXidxJ18nURsoQiAbJEInXydRGyhCIBskQidTJ1obKEI=?=
 =?iso-2022-jp?B?GyRCJ1knWidxGyhC?=";
        
        $mime = cls::get('email_Mime');
        
        $res = $mime->parseHeaders($h);
         
        UT::expectEqual($mime::decodeHeader(trim($res['subject'][0])), "Оферта за найлонови торбички - смяна на визия");
    }
    
    
    public function test_parseEmptyHeaders()
    {
        UT::expectEqual(email_Mime::parseHeaders(''), array());
        UT::expectEqual(email_Mime::parseHeaders(null), array());
    }


    public function test_parseMalformedHeaders()
    {
        $headers = "\torphan\nSubject: First\n continued\nBroken line\n\torphan again\nSubject: Second";
        UT::expectEqual(email_Mime::parseHeaders($headers), array('subject' => array("First\n continued", 'Second')));
    }


    /**
     * Езикът по подразбиране (bg) не надделява над латински текст на друг език
     */
    public function test_getLg()
    {
        // EF_DEFAULT_LANGUAGE се дефинира от настройките на ядрото
        cls::load('core_Setup');

        $cases = array(
            array('it', "Buon pomeriggio Adelina, mi fai sapere il costo della forma di cordonatura del seguente preventivo che ti inoltro.\nMi fai sapere anche se hai qualche fustella già disponibile con qualche misura che si avvicina a quelle che mi occorrono.\nGrazie saluti."),
            array('it', 'Buongiorno, mi mandi il preventivo aggiornato? Saluti, Marco'),
            array('pl', 'Dzień dobry, proszę o ofertę na torby papierowe z logo. Pozdrawiam'),
            array('bg', 'Здравейте, кога ще е готово?'),
            array('bg', 'Zdraveite! Mojete li da mi kajete cenata na kutiite za picca 33 sm? Blagodarq'),
        );

        $mime = cls::get('email_Mime');
        foreach ($cases as $case) {
            list($lg, $text) = $case;
            $mime->textPart = $text;
            $mime->lg = null;
            UT::expectEqual($mime->getLg(), $lg);
        }
    }

}
