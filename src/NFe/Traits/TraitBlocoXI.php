<?php

namespace NFePHP\DA\NFe\Traits;

/**
 * Bloco decreto 56.670
 */
trait TraitBlocoXI
{
    protected function blocoXI($y)
    {
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        $bandpgto = [];

        foreach ($this->pag as $pgto) {
            $bandeira = (int) $this->getTagValue($pgto, 'tBand');
            $autorizacao = (int) $this->getTagValue($pgto, 'cAut');
            $valor = number_format((float) $this->getTagValue($pgto, 'vPag'), 2, ',', '.');
            $bandpgto[] = [
                'bandeira' => $bandeira,
                'autorizacao' => $autorizacao,
                'valor' => $valor
            ];
        }

        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        foreach ($bandpgto as $p) {
            $p['bandeira'] = $p['bandeira'] ? $this->flagDescription($p['bandeira']) : null;
            if ($p['autorizacao']) {

                $texto = '';
                if ($p['bandeira']) $texto .= 'Band. ' . $p['bandeira'];
                $texto .= ' Nº Aut. ' . str_pad($p['autorizacao'], 6, '0', STR_PAD_LEFT) . ' R$: ' . $valor;

                $this->pdf->textBox($this->margem, $y - 3, $this->wPrint, 3, $texto, $aFont, 'T', 'C', false, '', false);
            }
        }

        $this->pdf->dashedHLine($this->margem, $this->bloco11H + $y, $this->wPrint, 0.1, 30);

        return $this->bloco11H + $y;
    }

    protected function flagDescription($flag)
    {
        $flagList = [
            1 => 'VISA',
            2 => 'MASTERCARD',
            3 => 'AMERICAN EXPRESS',
            4 => 'SOROCRED',
            5 => 'DINERS CLUB',
            6 => 'ELO',
            7 => 'HIPERCARD',
            8 => 'AURA',
            9 => 'CABAL',
            10 => 'ALELO',
            11 => 'BANES CARD',
            12 => 'CALCARD',
            13 => 'CREDZ',
            14 => 'DISCOVER',
            15 => 'GOODCARD',
            16 => 'GREENCARD',
            17 => 'HIPER',
            18 => 'JCB',
            19 => 'MAIS',
            20 => 'MAXVAN',
            21 => 'POLICARD',
            22 => 'REDECOMPRAS',
            23 => 'SODEXO',
            24 => 'VALECARD',
            25 => 'VEROCHEQUE',
            26 => 'VR',
            27 => 'TICKET',
            99 => 'OUTROS'
        ];
        return $flagList[$flag];
    }
}
