<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco8
{
    protected function bloco8($y)
    {
        $flagVTT = $this->hasApproxTaxInfo();

        $y = $this->fillTributosInfo($y, $flagVTT);
        $y = $this->fillComplementaryInfo($y, $flagVTT);

        return $y + 4;
    }

    private function hasApproxTaxInfo(): bool
    {
        $infCplLower = strtolower(trim($this->infCpl));

        return strpos($infCplLower, 'aprox') !== false
            && (strpos($infCplLower, 'trib') !== false || strpos($infCplLower, 'imp') !== false);
    }

    protected function fillTributosInfo($y, $flagVTT)
    {
        if ($flagVTT) {
            return $y;
        }

        $valor = $this->getTagValue($this->ICMSTot, 'vTotTrib');
        $trib = !empty($valor) ? number_format((float) $valor, 2, ',', '.') : '-----';
        $texto = "Informação dos Tributos Totais Incidentes (Lei Federal 12.742/2012): R$ {$trib}";

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        $y += $this->pdf->textBox(
            $this->margem,
            $y,
            $this->wPrint,
            8,
            $texto,
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );

        return $y;
    }

    protected function fillComplementaryInfo($y, $flagVTT = false)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
        if ($this->paperwidth < 70) {
            $aFont['size'] = 5;
        }

        $offset = $flagVTT ? 0 : 4;
        $y += $this->pdf->textBox(
            $this->margem,
            $y + $offset,
            $this->wPrint,
            8,
            str_replace(";", "\n", $this->infCpl),
            $aFont,
            'T',
            'L',
            false,
            '',
            false
        );

        return $y;
    }
}
