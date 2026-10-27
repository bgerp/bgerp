<?php


/**
 * Синтетични PDF документи за сравнение на TCPDF версиите.
 *
 * @category tests
 * @package tcpdf
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class tcpdf_tests_Fixtures
{
    /** Генерира примерен документ през реалния TCPDF или CAD адаптер. */
    public static function render($fixture)
    {
        if ($fixture === 'cad') {
            $canvas = new cad2_PdfCanvas();
            $canvas->setPaper(120, 80);
            $canvas->setAttr(array('stroke' => '#0000fe', 'stroke-width' => 0.4,
                'stroke-linecap' => 'round', 'stroke-dasharray' => '0', 'fill' => 'none',
                'font-size' => 35, 'font-family' => 'dejavusans', 'text-color' => '#020301'));
            $canvas->moveTo(5, 15, true);
            $canvas->lineTo(105, 15, true);
            $canvas->curveTo(115, 25, 110, 55, 90, 65, true);
            $canvas->lineTo(5, 65, true);
            $canvas->closePath();
            $canvas->writeText(10, 40, 'Чертеж - размер 100 мм');

            return $canvas->render();
        }

        expect(in_array($fixture, array('invoice', 'large'), true), 'Непознат PDF пример');
        $pdf = new tcpdf_Instance('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('bgERP test');
        $pdf->SetTitle('Проверка на PDF');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();

        $count = $fixture === 'large' ? 800 : 3;
        $html = '<h1>Тестова фактура</h1><p>Кирилица: България, София. Код: 00123.</p>'
            . '<p><b>Получател:</b> Тестов клиент &amp; партньори</p>'
            . '<table border="1" cellpadding="4"><thead><tr style="background-color:#dce8f2;">'
            . '<th width="10%">№</th><th width="50%">Артикул</th><th width="20%">К-во</th><th width="20%">Сума</th>'
            . '</tr></thead><tbody>';
        for ($i = 1; $i <= $count; $i++) {
            $html .= '<tr><td width="10%">' . $i . '</td><td width="50%">Артикул ' . $i
                . '</td><td width="20%" align="right">2,50</td><td width="20%" align="right">12,50</td></tr>';
        }
        $html .= '</tbody></table><p align="right"><b>Общо: '
            . number_format($count * 12.5, 2, ',', ' ') . ' EUR</b></p>'
            . '<p><i>Край на документа.</i></p>';
        $pdf->writeHTML($html);
        if ($fixture === 'invoice') {
            $y = $pdf->GetY() + 5;
            $pdf->write1DBarcode('123456789012', 'C128', 15, $y, 75, 15, 0.4, array());
            // Фиксиран избор на QR маска за повторимо сравнение на растерните страници.
            srand(12345);
            $pdf->write2DBarcode('BG-TEST-00123', 'QRCODE,H', 155, $y, 25, 25, array());
            $pdf->drawPath(array(array('M', 15, $y + 35), array('L', 60, $y + 35),
                array('C', 70, $y + 35, 70, $y + 55, 60, $y + 55)), 'S', array('width' => 0.6));
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="60">'
                . '<rect x="1" y="1" width="98" height="58" fill="#4682b4"/>'
                . '<circle cx="50" cy="30" r="20" fill="#ffa500"/></svg>';
            $pdf->ImageSVG('@' . $svg, 90, $y + 35, 35, 21);
            $im = imagecreatetruecolor(100, 60);
            imagefilledrectangle($im, 0, 0, 99, 59, imagecolorallocate($im, 32, 128, 80));
            imagefilledrectangle($im, 10, 10, 89, 49, imagecolorallocate($im, 240, 220, 80));
            ob_start();
            imagepng($im);
            $png = ob_get_clean();
            imagedestroy($im);
            $pdf->Image('@' . $png, 140, $y + 35, 35, 21, 'PNG');
            $pdf->startLayer('Hidden test', false, false);
            $pdf->Text(15, $y + 65, 'HIDDEN TEST');
            $pdf->endLayer();
        }

        return $pdf->Output('', 'S');
    }
}
