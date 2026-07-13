<?php


/**
 * Raster декоратор, който скрива класифицираните като преливка пиксели от
 * pixels() итерацията, за да ги изключи от съществуващото клъстеризиране
 * (ColorHistogram консумира само pixels()). Използва се единствено когато
 * има намерени преливки - иначе оригиналният растер се подава директно и
 * пътят на плътните цветове е байт-идентичен с досегашния.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_MaskedRaster implements \ImageColorAnalyzer\Contracts\Raster
{
    private $raster;
    private $mask;
    private $skipByte;


    /**
     * @param \ImageColorAnalyzer\Contracts\Raster $raster
     * @param string                               $mask     байтова маска W*H (imgcolor_TransitionClassifier)
     * @param string                               $skipByte кой клас да се скрие
     */
    public function __construct(\ImageColorAnalyzer\Contracts\Raster $raster, $mask, $skipByte = imgcolor_TransitionClassifier::CLS_TRANS)
    {
        if (strlen($mask) !== $raster->width() * $raster->height()) {
            throw new InvalidArgumentException('Mask length must equal raster width*height');
        }
        $this->raster = $raster;
        $this->mask = $mask;
        $this->skipByte = $skipByte;
    }


    public function width(): int
    {
        return $this->raster->width();
    }


    public function height(): int
    {
        return $this->raster->height();
    }


    public function hasAlpha(): bool
    {
        return $this->raster->hasAlpha();
    }


    /**
     * Достъп по координати - без маскиране; маската важи само за pixels().
     */
    public function pixelAt(int $x, int $y): \ImageColorAnalyzer\Contracts\ColorRGBA
    {
        return $this->raster->pixelAt($x, $y);
    }


    /**
     * @return iterable<\ImageColorAnalyzer\Contracts\ColorRGBA>
     */
    public function pixels(): iterable
    {
        $i = 0;
        foreach ($this->raster->pixels() as $pixel) {
            if ($this->mask[$i++] === $this->skipByte) {
                continue;
            }
            yield $pixel;
        }
    }


    public function crop(\ImageColorAnalyzer\Contracts\BoundingBox $box): \ImageColorAnalyzer\Contracts\Raster
    {
        throw new \ImageColorAnalyzer\Exception\NotImplementedException('imgcolor_MaskedRaster does not support cropping');
    }
}
