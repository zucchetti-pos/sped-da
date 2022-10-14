<?php

namespace NFePHP\DA\NFSe;

use NFePHP\DA\Legacy\Dom;
use NFePHP\DA\Legacy\Pdf;
use NFePHP\DA\Common\DaCommon;

/**
 * @author Ian Grasman <iangrasman@gmail.com>
 */

class Danfse extends DaCommon
{
    /**
     * XML NFSe
     *
     * @var string
     */
    protected $xml;
    /**
     * @var boolean
     */
    protected $qCanhoto = true;
    /**
     * @var string
     */
    protected $errMsg = '';
    /**
     * @var boolean
     */
    protected $errStatus = false;
    /**
     * Largura
     *
     * @var float
     */
    protected $wAdic = 0;
    /**
     * largura do canhoto (25mm) apenas para a formatação paisagem
     *
     * @var float
     */
    protected $wCanhoto = 25;
    /**
     * 1-Retrato/ 2-Paisagem
     *
     * @var integer
     */
    protected $tpImp;
    /**
     * quantidade de itens já processados na montagem do DANFE
     *
     * @var integer
     */
    protected $qtdeItensProc;
    /**
     * Dom Document
     *
     * @var \NFePHP\DA\Legacy\Dom
     */
    protected $dom;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $infNfse;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $identificacaoRps;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $servico;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $prestadorServico;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $tomadorServico;
    /**
     * Node
     *
     * @var \DOMNode
     */
    protected $orgaoGerador;
    /**
     * Número de casas para a quantidade de itens da unidade comercial.
     *
     * @var integer
     */
    protected $qComCasasDec = 4;
    /**
     * Número de casas decimais para o valor da unidade comercial.
     *
     * @var integer
     */
    protected $vUnComCasasDec = 4;

    /**
     * Alinhamento da logo da empresa, de momento funciona apenas com "L"
     * 
     * @var string
     */
    protected $logoAlign = 'L';
    /**
     * @param string $xml Conteúdo XML da NF-e (com ou sem a tag nfeProc)
     */
    public function __construct(string $xml)
    {
        $this->loadXml($xml);
    }

    protected function monta(
        $logo = ''
    ) {
        $this->pdf = '';
        $this->logomarca = $this->adjustImage($logo);
        //se a orientação estiver em branco utilizar o padrão estabelecido na NF
        if (empty($this->orientacao)) {
            if ($this->tpImp == '2') {
                $this->orientacao = 'L';
            } else {
                $this->orientacao = 'P';
            }
        }
        //instancia a classe pdf
        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        //margens do PDF, em milímetros. Obs.: a margem direita é sempre igual à
        //margem esquerda. A margem inferior *não* existe na FPDF, é definida aqui
        //apenas para controle se necessário ser maior do que a margem superior
        // posição inicial do conteúdo, a partir do canto superior esquerdo da página
        $xInic = $this->margesq;
        if ($this->orientacao == 'P') {
            if ($this->papel == 'A4') {
                $this->maxW = 210;
                $this->maxH = 297;
            }
        } else {
            if ($this->papel == 'A4') {
                $this->maxW = 297;
                $this->maxH = 210;
                $xInic      = $this->margesq + 10;
                //se paisagem multiplica a largura do canhoto pela quantidade de canhotos
                //$this->wCanhoto *= $this->qCanhoto;
            }
        }
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm: largura da folha menos as margens esq/direita
        $this->wPrint = $this->maxW - ($this->margesq * 2);
        //comprimento (altura) imprimivel em mm: altura da folha menos as margens
        //superior e inferior
        $this->hPrint = $this->maxH - $this->margsup - $this->marginf;
        // estabelece contagem de paginas
        $this->pdf->aliasNbPages();
        // fixa as margens
        $this->pdf->setMargins($this->margesq, $this->margsup);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        // inicia o documento
        $this->pdf->open();
        // adiciona a primeira página
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setLineWidth(0.1);
        $this->pdf->settextcolor(0, 0, 0);

        // //##################################################################
        // // CALCULO DO NUMERO DE PAGINAS A SEREM IMPRESSAS
        // //##################################################################
        // //Verificando quantas linhas serão usadas para impressão das duplicatas
        // $linhasDup = 0;
        // $qtdPag    = 0;
        // if (isset($this->dup) && $this->dup->length > 0) {
        //     $qtdPag = $this->dup->length;
        // } elseif (isset($this->detPag) && $this->detPag->length > 0) {
        //     $qtdPag = $this->detPag->length;
        // }
        // if (($qtdPag > 0) && ($qtdPag <= 7)) {
        //     $linhasDup = 1;
        // } elseif (($qtdPag > 7) && ($qtdPag <= 14)) {
        //     $linhasDup = 2;
        // } elseif (($qtdPag > 14) && ($qtdPag <= 21)) {
        //     $linhasDup = 3;
        // } elseif ($qtdPag > 21) {
        //     // chinnonsantos 11/05/2016: Limite máximo de impressão de duplicatas na NFe,
        //     // só vai ser exibito as 21 primeiras duplicatas (parcelas de pagamento),
        //     // se não oculpa espaço d+, cada linha comporta até 7 duplicatas.
        //     $linhasDup = 3;
        // }
        // //verifica se será impressa a linha dos serviços ISSQN
        // $linhaISSQN = 0;
        // if ((isset($this->ISSQNtot)) && ($this->getTagValue($this->ISSQNtot, 'vServ') > 0)) {
        //     $linhaISSQN = 1;
        // }
        // //calcular a altura necessária para os dados adicionais
        // if ($this->orientacao == 'P') {
        //     $this->wAdic = round($this->wPrint * 0.66, 0);
        // } else {
        //     $this->wAdic = round(($this->wPrint - $this->wCanhoto) * 0.5, 0);
        // }
        // $fontProduto = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        // $this->hdadosadic = $this->calculoEspacoVericalDadosAdicionais();

        // //altura disponivel para os campos da DANFE
        // $hcabecalho    = 47; //para cabeçalho
        // $hdestinatario = 25; //para destinatario
        // $hduplicatas   = 12; //para cada grupo de 7 duplicatas
        // if (isset($this->entrega)) {
        //     $hlocalentrega = 25;
        // } else {
        //     $hlocalentrega = 0;
        // }
        // if (isset($this->retirada)) {
        //     $hlocalretirada = 25;
        // } else {
        //     $hlocalretirada = 0;
        // }
        // $himposto    = 18; // para imposto
        // $htransporte = 25; // para transporte
        // $hissqn      = 11; // para issqn
        // $hfooter     = 5; // para rodape
        // $hCabecItens = 4; //cabeçalho dos itens
        // $hOCUPADA    = $hcabecalho
        //     + $hdestinatario
        //     + $hlocalentrega
        //     + $hlocalretirada
        //     + ($linhasDup * $hduplicatas)
        //     + $himposto + $htransporte
        //     + ($linhaISSQN * $hissqn)
        //     + $this->hdadosadic
        //     + $hfooter
        //     + $hCabecItens
        //     + $this->sizeExtraTextoFatura();

        // //alturas disponiveis para os dados
        // $hDispo1 = $this->hPrint - $hOCUPADA;
        // /*($hcabecalho +
        // //$hdestinatario + ($linhasDup * $hduplicatas) + $himposto + $htransporte +
        // $hdestinatario + $hlocalentrega + $hlocalretirada +
        // ($linhasDup * $hduplicatas) + $himposto + $htransporte +
        // ($linhaISSQN * $hissqn) + $this->hdadosadic + $hfooter + $hCabecItens +
        // $this->sizeExtraTextoFatura());*/

        // if ($this->orientacao == 'P') {
        //     $hDispo1 -= 24 * $this->qCanhoto; //para canhoto
        //     $w       = $this->wPrint;
        // } else {
        //     $hcanhoto = $this->hPrint; //para canhoto
        //     $w        = $this->wPrint - $this->wCanhoto;
        // }
        // //$hDispo1 += 14;
        // $hDispo2 = $this->hPrint - ($hcabecalho + $hfooter + $hCabecItens);
        // //Contagem da altura ocupada para impressão dos itens
        // $aFont     = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];
        // $numlinhas = 0;
        // $hUsado    = $hCabecItens;
        // $w2        = round($w * 0.25, 0);
        // $hDispo    = $hDispo1;
        // $totPag    = 1;
        // $i         = 0;
        // while ($i < $this->det->length) {
        //     $itemProd = $this->det->item($i);
        //     $texto = $this->descricaoProduto($itemProd);
        //     $mostrarUnidadeTributavel = false;

        //     $prod = $itemProd->getElementsByTagName('prod')->item(0);
        //     $uCom = $prod->getElementsByTagName("uCom")->item(0)->nodeValue;
        //     $vUnCom = $prod->getElementsByTagName("vUnCom")->item(0)->nodeValue;
        //     $uTrib = $prod->getElementsByTagName("uTrib")->item(0);
        //     $qTrib = $prod->getElementsByTagName("qTrib")->item(0);
        //     $vUnTrib = !empty($prod->getElementsByTagName("vUnTrib")->item(0)->nodeValue)
        //         ? $prod->getElementsByTagName("vUnTrib")->item(0)->nodeValue
        //         : 0;
        //     //se as unidades forem diferentes e q qtda de qTrib for maior que 0
        //     //mostrat as unidades
        //     $mostrarUnidadeTributavel = (!$this->ocultarUnidadeTributavel
        //         && !empty($uTrib)
        //         && !empty($qTrib)
        //         && number_format($vUnCom, 2, ',', '') !== number_format($vUnTrib, 2, ',', '')
        //     );
        //     $hUsado += $this->calculeHeight($itemProd, $mostrarUnidadeTributavel);
        //     if ($hUsado > $hDispo) {
        //         $totPag++;
        //         $hDispo = $hDispo2;
        //         $hUsado = $hCabecItens;
        //         $i--; // decrementa para readicionar o item que não coube nessa pagina na outra.
        //     }
        //     $i++;
        // } //fim da soma das areas de itens usadas
        // $qtdeItens = $i; //controle da quantidade de itens no DANFE
        //montagem da primeira página
        $pag = 1;

        $x = $this->margesq;
        $y = $this->margsup;

        if ($this->orientacao == 'P') {
            $y = $this->cabecalho($this->margesq, $this->margsup);
        } else {
            $this->cabecalho($this->margesq, $this->margsup);
            $x = 25;
        }
        // coloca o prestador
        $y = $this->prestador($x, $y);

        // //coloca o cabeçalho
        // $y = $this->header($x, $y, $pag, $totPag);
        // //coloca os dados do destinatário
        // $y = $this->destinatarioDANFE($x, $y + 1);
        // //coloca os dados do local de retirada
        // if (isset($this->retirada)) {
        //     $y = $this->localRetiradaDANFE($x, $y + 1);
        // }
        // //coloca os dados do local de entrega
        // if (isset($this->entrega)) {
        //     $y = $this->localEntregaDANFE($x, $y + 1);
        // }

        // //Verifica as formas de pagamento da nota fiscal
        // $formaPag = [];
        // if (isset($this->detPag) && $this->detPag->length > 0) {
        //     foreach ($this->detPag as $k => $d) {
        //         $fPag            = !empty($this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue)
        //             ? $this->detPag->item($k)->getElementsByTagName('tPag')->item(0)->nodeValue
        //             : '0';
        //         $formaPag[$fPag] = $fPag;
        //     }
        // }
        // //caso tenha boleto imprimir fatura
        // if ($this->dup->length > 0) {
        //     $y = $this->fatura($x, $y + 1);
        // } else {
        //     //Se somente tiver a forma de pagamento sem pagamento não imprimir nada
        //     if (count($formaPag) == '1' && isset($formaPag[90])) {
        //         $y = $y;
        //     } else {
        //         //caso tenha mais de uma forma de pagamento ou seja diferente de boleto exibe a
        //         //forma de pagamento e o valor
        //         $y = $this->pagamento($x, $y + 1);
        //     }
        // }
        // //coloca os dados dos impostos e totais da NFe
        // $y = $this->imposto($x, $y + 1);
        // //coloca os dados do trasnporte
        // $y = $this->transporte($x, $y + 1);
        // //itens da DANFE
        // $nInicial = 0;

        // $y = $this->itens($x, $y + 1, $nInicial, $hDispo1, $pag, $totPag, $hCabecItens);

        // //coloca os dados do ISSQN
        // if ($linhaISSQN == 1) {
        //     $y = $this->issqn($x, $y + 4);
        // } else {
        //     $y += 4;
        // }
        // //coloca os dados adicionais da NFe
        // $y = $this->dadosAdicionais($x, $y, $this->hdadosadic);
        // //coloca o rodapé da página
        // if ($this->orientacao == 'P') {
        //     $this->rodape($xInic);
        // } else {
        //     $this->rodape($xInic);
        // }

        // //loop para páginas seguintes
        // for ($n = 2; $n <= $totPag; $n++) {
        //     // fixa as margens
        //     $this->pdf->setMargins($this->margesq, $this->margsup);
        //     //adiciona nova página
        //     $this->pdf->addPage($this->orientacao, $this->papel);
        //     //ajusta espessura das linhas
        //     $this->pdf->setLineWidth(0.1);
        //     //seta a cor do texto para petro
        //     $this->pdf->settextcolor(0, 0, 0);
        //     // posição inicial do relatorio
        //     $x = $this->margesq;
        //     $y = $this->margsup;
        //     //coloca o cabeçalho na página adicional
        //     $y = $this->header($x, $y, $n, $totPag);
        //     //coloca os itens na página adicional
        //     $y = $this->itens($x, $y + 1, $nInicial, $hDispo2, $n, $totPag, $hCabecItens);
        //     //coloca o rodapé da página
        //     if ($this->orientacao == 'P') {
        //         $this->rodape($this->margesq);
        //     } else {
        //         $this->rodape($this->margesq);
        //     }
        //     //se estiver na última página e ainda restar itens para inserir, adiciona mais uma página
        //     if ($n == $totPag && $this->qtdeItensProc < $qtdeItens) {
        //         $totPag++;
        //     }
        // }
    }

    protected function cabecalho(float $x, float $y): float
    {
        if ($this->orientacao == 'P') {
            $w = round($this->wPrint * 0.81, 0);
        }

        $h = 25;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => ''];

        if ($this->orientacao == 'P') {
            $this->pdf->textBox($x, $y, $w, $h, '', $aFont, 'C', 'L', 1, '', false);
        } else {
            $this->pdf->textBox90($x, $y, $w, $h, '', $aFont, 'C', 'L', 1, '', false);
        }

        if ($this->orientacao == 'P') {
            $texto = "MUNICÍPIO";
            $aFont = ['font' => $this->fontePadrao, 'size' => 12, 'style' => 'B'];
            $this->pdf->textBox($x, $y - 5, $w, $h, $texto, $aFont, 'C', 'C', 0, '', false);
            $texto = "NOTA FISCAL DE SERVIÇOS ELETRÔNICA - NFS-e";
            $aFont = ['font' => $this->fontePadrao, 'size' => 10, 'style' => 'B'];
            $this->pdf->textBox($x, $y + 3, $w, $h, $texto, $aFont, 'C', 'C', 0, '', false);
            $texto = "RPS Nº {$this->identificacaoRps->Numero}, emitido em {$this->toDate($this->infNfse->DataEmissao)}";
            $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
            $this->pdf->textBox($x, $y + 9.5, $w, $h, $texto, $aFont, 'C', 'C', 0, '', false);
            $x1 = $x + $w;
            $w1 = $this->wPrint - $w;
            $texto = "NÚMERO NOTA";
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x1, $y, $w1, 8.3, $texto, $aFont, 'T', 'L', 1, '');
            $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $this->pdf->textBox($x1, $y, $w1, 10, (string)$this->infNfse->Numero, $aFont, 'C', 'C', 0, '');
            $texto = "DATA E HORA DA EMISSÃO";
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x1, $y + 8.3, $w1, 8.3, $texto, $aFont, 'T', 'L', 1, '');
            $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $this->pdf->textBox($x1, $y  + 8.3, $w1, 10, $this->toDate($this->infNfse->DataEmissao, true), $aFont, 'C', 'C', 0, '');
            $texto = "CÓDIGO DE VERIFICAÇÃO";
            $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
            $this->pdf->textBox($x1, $y + 16.6, $w1, 8.3, $texto, $aFont, 'T', 'L', 1, '');
            $aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => 'B'];
            $this->pdf->textBox($x1, $y + 16.6, $w1, 10, (string)$this->infNfse->CodigoVerificacao, $aFont, 'C', 'C', 0, '');
        }

        return $y + 25;
    }

    private function prestador(float $x, float $y): float
    {
        if ($this->orientacao == 'P') {
            $maxW = $this->wPrint;
        } else {
            $maxW = $this->wPrint - $this->wCanhoto;
        }

        $w     = $maxW;
        $h     = 6;
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        if ($this->orientacao == 'P') {
            $this->drawBox($x, $y, $w, $h);
            $texto = "PRESTADOR DE SERVIÇOS";
            $aFont = ['font' => $this->fontePadrao, 'size' => 9, 'style' => 'B'];
            $this->pdf->textBox($x, $y - 0.3, $w, $h, $texto, $aFont, 'C', 'C', 0, '', false);
        }

        $y += $h;
        $h = 35;
        $this->drawBox($x, $y, $w, $h);
        if (!empty($this->logomarca)) {
            $logoInfo = getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0] / 72) * 25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1] / 72) * 25.4;
            if ($this->logoAlign == 'L') {
                $nImgW = round($w / 4, 0);
                $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);
                $xImg  = $x + 1;
                $yImg  = round(($h - $nImgH) / 2, 0) + $y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW + 1, 0);
                $y1 = round($h / 3 + $y, 0);
                $tw = round(2 * $w / 3, 0);
            }

            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h / 3 + $y, 0);
            $tw = $w;
        }


        // CPF/CNPJ
        $x += $nImgW;
        $w = round($maxW * 0.15, 0);
        $h = 17;
        $texto = "CPF/CNPJ:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $texto = $this->formatField($this->prestadorServico->IdentificacaoPrestador->Cnpj, "###.###.###/####-##");
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 11, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);

        // NOME/RAZÃO
        $y += 5;
        $w = round($maxW * 0.40, 0);
        $texto = "NOME/RAZÃO:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $texto = (string) $this->prestadorServico->RazaoSocial;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 16, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);

        // ENDEREÇO
        $y += 5;
        $texto = "ENDEREÇO:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $numero = (string)$this->prestadorServico->Endereco->Numero ?: "N/A";
        $texto = "{$this->prestadorServico->Endereco->Endereco}, {$numero}, {$this->prestadorServico->Endereco->Bairro}";
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 13, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);

        // COMPLEMENTO
        $y += 5;
        $texto = "COMPLEMENTO:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $texto = (string)$this->prestadorServico->Endereco->Complemento;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 18, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);

        // INSCRIÇÃO ESTADUAL
        $y = 38;
        $x += 70;
        $texto = "INSCRICAO MUNICIPAL:";
        $aFont = ['font' => $this->fontePadrao, 'size' => 6, 'style' => ''];
        $this->pdf->textBox($x, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);
        $texto = (string) $this->prestadorServico->IdentificacaoPrestador->InscricaoMunicipal;
        $aFont = ['font' => $this->fontePadrao, 'size' => 7, 'style' => 'B'];
        $this->pdf->textBox($x + 26, $y, $w, $h, $texto, $aFont, 'C', 'L', 0, '', false);

        return $y;
    }

    private function loadXml(string $xml): void
    {
        $this->xml = $xml;
        if (!empty($xml)) {
            $this->dom = simplexml_load_string($this->xml);
            if (empty($this->dom->Nfse->InfNfse)) {
                throw new \Exception('Isso não é uma NFSe.');
            }
            $this->infNfse = $this->dom->Nfse->InfNfse;
            $this->identificacaoRps = $this->dom->Nfse->InfNfse->IdentificacaoRps;
            $this->servico = $this->dom->Nfse->InfNfse->Servico;
            $this->prestadorServico = $this->dom->Nfse->InfNfse->PrestadorServico;
            $this->tomadorServico = $this->dom->Nfse->InfNfse->TomadorServico;
            $this->orgaoGerador = $this->dom->Nfse->InfNfse->OrgaoGerador;
        }
    }

    private function drawBox(
        float $x,
        float $y,
        float $w,
        float $h
    ): void {
        $this->pdf->textBox($x, $y, $w, $h);
    }
}
