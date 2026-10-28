<?php


/**
 * Прозрачен слой с написаното върху страниците на формуляр (TCPDF, шрифт с кирилица)
 *
 * Всяка страница на слоя е с размера на показаната страница на формуляра;
 * pdfform.py го наслагва върху оригинала, като отчита завъртането.
 *
 * @category  bgerp
 * @package   pdfform
 *
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pdfform_Overlay
{
    /**
     * Шрифт с кирилица от TCPDF
     */
    const FONT = 'dejavusans';


    /**
     * Най-малък размер на шрифта при смаляване до ширината
     */
    const MIN_FONT_SIZE = 4;


    /**
     * Отношение на височината на реда към размера на шрифта
     */
    const LINE_HEIGHT = 1.2;


    /**
     * Размер по подразбиране на квадратче за отметка, в пунктове
     */
    const CHECK_SIZE = 10;


    /**
     * Създава слоя
     *
     * @param string $path   - изходен PDF
     * @param array  $pages  - [['width' => pt, 'height' => pt], ...]
     * @param array  $items  - проверените елементи от pdfform_Api
     * @param array  $fields - [['field' => полето от четенето, 'value' => видим текст], ...]
     *
     * @return array - празен или ['error' => ...]
     */
    public static function create($path, $pages, $items, $fields)
    {
        $pdf = self::newPdf();
        foreach (array_values($pages) as $i => $p) {
            self::addPage($pdf, $p);
            $no = $i + 1;
            foreach ($fields as $f) {
                if ((int) ($f['field']['page'] ?? 0) === $no) {
                    self::drawField($pdf, $p, $f['field'], $f['value']);
                }
            }
            foreach ($items as $item) {
                if ($item['page'] !== $no) continue;
                if ($item['kind'] === 'text') {
                    self::drawText($pdf, $p, $item);
                } elseif ($item['kind'] === 'check') {
                    self::drawCheck($pdf, $p, $item);
                } else {
                    $res = self::drawImage($pdf, $p, $item);
                    if (!empty($res['error'])) return $res;
                }
            }
        }

        return self::output($pdf, $path);
    }


    /**
     * PDF от една страница с изображението, разпънато по цялата страница
     *
     * @param string $image
     * @param string $path
     * @param array  $page - ['width' => pt, 'height' => pt]
     *
     * @return array - празен или ['error' => ...]
     */
    public static function imageToPdf($image, $path, $page)
    {
        $pdf = self::newPdf();
        self::addPage($pdf, $page);
        try {
            $pdf->Image($image, 0, 0, $page['width'], $page['height'], '', '', '', false, 300);
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            reportException($e);

            return array('error' => 'The image could not be placed in a PDF page.', 'reason' => 'invalid_image');
        }

        return self::output($pdf, $path);
    }


    /**
     * Нов TCPDF документ в пунктове, без полета, колонтитули и автоматично пренасяне
     *
     * @return tcpdf_Instance
     */
    protected static function newPdf()
    {
        cls::load('tcpdf_Instance');
        $pdf = new tcpdf_Instance('P', 'pt', 'A4', true, 'UTF-8', false);

        // Без надписа и връзката "Powered by TCPDF", които иначе влизат в чуждия формуляр
        (function () { $this->tcpdflink = false; })->call($pdf);
        $pdf->SetCreator('bgERP');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setCellHeightRatio(self::LINE_HEIGHT);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);

        return $pdf;
    }


    /**
     * Добавя страница с точния размер
     */
    protected static function addPage($pdf, $p)
    {
        $w = (float) $p['width'];
        $h = (float) $p['height'];
        $pdf->AddPage($w > $h ? 'L' : 'P', array($w, $h));
    }


    /**
     * Записва PDF-а във файл
     */
    protected static function output($pdf, $path)
    {
        try {
            $pdf->Output($path, 'F');
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            reportException($e);
        }
        if (!is_file($path) || !filesize($path)) {

            return array('error' => 'The PDF layer could not be created.', 'reason' => 'overlay_failed');
        }

        return array();
    }


    /**
     * Правоъгълник в пунктове от проценти
     */
    protected static function box($p, $x, $y, $w = null, $h = null)
    {
        return array($x * $p['width'] / 100, $y * $p['height'] / 100,
            $w === null ? null : $w * $p['width'] / 100, $h === null ? null : $h * $p['height'] / 100);
    }


    /**
     * Размер на шрифта, при който текстът се събира в ширината
     */
    protected static function fitSize($pdf, $text, $size, $width)
    {
        if ($width === null || $width <= 0) return $size;
        $pdf->SetFont(self::FONT, '', $size);
        $textWidth = $pdf->GetStringWidth($text);
        if ($textWidth > $width) {
            $size = max(self::MIN_FONT_SIZE, floor($size * $width / $textWidth * 10) / 10);
        }

        return $size;
    }


    /**
     * Текст: от горния ляв ъгъл, смален до ширината; с височина - пренасян в правоъгълника
     */
    protected static function drawText($pdf, $p, $item)
    {
        list($x, $y, $w, $h) = self::box($p, $item['x'], $item['y'], $item['width'] ?? null, $item['height'] ?? null);
        $align = array('left' => 'L', 'center' => 'C', 'right' => 'R')[$item['align']];
        $size = $item['fontSize'];

        if ($h !== null) {
            $pdf->SetFont(self::FONT, '', $size);
            $pdf->MultiCell($w, $h, $item['text'], 0, $align, false, 1, $x, $y, true, 0, false, false, $h, 'T', true);

            return;
        }

        foreach (preg_split('/\r?\n/', $item['text']) as $line) {
            $lineSize = self::fitSize($pdf, $line, $size, $w);
            $pdf->SetFont(self::FONT, '', $lineSize);
            $pdf->SetXY($x, $y);
            if ($w !== null) {
                $pdf->Cell($w, $lineSize * self::LINE_HEIGHT, $line, 0, 0, $align);
            } else {
                $pdf->Cell($pdf->GetStringWidth($line) + 1, $lineSize * self::LINE_HEIGHT, $line);
            }
            $y += $size * self::LINE_HEIGHT;
        }
    }


    /**
     * Отметка "X" в квадратчето
     */
    protected static function drawCheck($pdf, $p, $item)
    {
        list($x, $y, $w, $h) = self::box($p, $item['x'], $item['y'], $item['width'] ?? null, $item['height'] ?? null);
        $w = $w ?? ($h ?? self::CHECK_SIZE);
        $h = $h ?? $w;

        // Квадратен знак в центъра на кутийката, за да не се разтяга
        $side = min($w, $h) * 0.7;
        $x += ($w - $side) / 2;
        $y += ($h - $side) / 2;
        $pdf->SetLineStyle(array('width' => max(0.8, $side / 7), 'cap' => 'round', 'color' => array(0, 0, 0)));
        $pdf->Line($x, $y, $x + $side, $y + $side);
        $pdf->Line($x + $side, $y, $x, $y + $side);
    }


    /**
     * Изображение (печат, подпис, лого) в правоъгълника, със запазени пропорции
     */
    protected static function drawImage($pdf, $p, $item)
    {
        list($x, $y, $w, $h) = self::box($p, $item['x'], $item['y'], $item['width'], $item['height'] ?? null);
        try {
            if ($h === null) {
                $pdf->Image($item['path'], $x, $y, $w, 0, '', '', '', false, 300);
            } else {
                $pdf->Image($item['path'], $x, $y, $w, $h, '', '', '', false, 300, '', false, false, 0, 'CM');
            }
        } catch (core_exception_Redirect $e) {
            throw $e;
        } catch (Exception $e) {
            reportException($e);

            return array('error' => 'An image could not be placed on the page.', 'reason' => 'invalid_image');
        }

        return array();
    }


    /**
     * Стойност на поле при заключване - в правоъгълника на полето
     */
    protected static function drawField($pdf, $p, $f, $value)
    {
        $rect = $f['rect'] ?? null;
        if (!is_array($rect) || countR($rect) < 4) return;
        list($x, $y, $w, $h) = self::box($p, $rect[0], $rect[1], $rect[2], $rect[3]);

        // Текстът върви по посоката на полето: рисува се в завъртян правоъгълник
        $turn = (int) ($f['rotation'] ?? 0);
        if (!in_array($turn, array(90, 180, 270), true)) {
            self::drawFieldBox($pdf, $f, $value, $x, $y, $w, $h);

            return;
        }
        $anchor = array(90 => array($x + $w, $y), 180 => array($x + $w, $y + $h), 270 => array($x, $y + $h))[$turn];
        if ($turn !== 180) {
            list($w, $h) = array($h, $w);
        }
        $pdf->StartTransform();
        $pdf->Rotate(-$turn, $anchor[0], $anchor[1]);
        self::drawFieldBox($pdf, $f, $value, $anchor[0], $anchor[1], $w, $h);
        $pdf->StopTransform();
    }


    /**
     * Стойност на поле в правоъгълник в собствената му посока
     */
    protected static function drawFieldBox($pdf, $f, $value, $x, $y, $w, $h)
    {
        $align = array('left' => 'L', 'center' => 'C', 'right' => 'R')[$f['align'] ?? 'left'] ?? 'L';
        $size = (float) ($f['fontSize'] ?? 0);
        $pad = min(2, $w / 10);

        // Полета с клетки за всеки знак
        if (!empty($f['comb']) && !empty($f['maxLength'])) {
            $cell = $w / $f['maxLength'];
            $size = $size > 0 ? $size : min(12, $h * 0.7);
            $pdf->SetFont(self::FONT, '', $size);
            foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) as $i => $char) {
                $pdf->SetXY($x + $i * $cell, $y);
                $pdf->Cell($cell, $h, $char, 0, 0, 'C', false, '', 0, false, 'T', 'M');
            }

            return;
        }

        if (!empty($f['multiline'])) {
            $pdf->SetFont(self::FONT, '', $size > 0 ? $size : 10);
            $pdf->setCellPaddings($pad, $pad, $pad, $pad);
            $pdf->MultiCell($w, $h, $value, 0, $align, false, 1, $x, $y, true, 0, false, true, $h, 'T', true);
            $pdf->setCellPaddings(0, 0, 0, 0);

            return;
        }

        $size = $size > 0 ? $size : min(12, $h * 0.7);
        $size = self::fitSize($pdf, $value, $size, $w - 2 * $pad);
        $pdf->SetFont(self::FONT, '', $size);
        $pdf->setCellPaddings($pad, 0, $pad, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $value, 0, 0, $align, false, '', 0, false, 'T', 'M');
        $pdf->setCellPaddings(0, 0, 0, 0);
    }
}
