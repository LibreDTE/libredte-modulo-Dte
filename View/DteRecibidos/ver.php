<ul class="nav nav-pills float-right">
<?php if ($Receptor->config_pdf_imprimir) : ?>
<?php if ($Receptor->config_pdf_imprimir == 'pdf_escpos') : ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-print"></i>
            Imprimir
        </a>
        <div class="dropdown-menu">
            <a href="#" onclick="dte_imprimir('pdf', 'dte_recibido', {emisor: <?=$DteRecibido->emisor?>, dte: <?=$DteRecibido->dte?>, folio: <?=$DteRecibido->folio?>, receptor: <?=$DteRecibido->receptor?>}); return false" class="dropdown-item">PDF</a>
            <a href="#" onclick="dte_imprimir('escpos', 'dte_recibido', {emisor: <?=$DteRecibido->emisor?>, dte: <?=$DteRecibido->dte?>, folio: <?=$DteRecibido->folio?>, receptor: <?=$DteRecibido->receptor?>}); return false" accesskey="P" class="dropdown-item">ESCPOS</a>
        </div>
    </li>
<?php else: ?>
    <li class="nav-item">
        <a href="#" onclick="dte_imprimir('<?=$Receptor->config_pdf_imprimir?>', 'dte_recibido', {emisor: <?=$DteRecibido->emisor?>, dte: <?=$DteRecibido->dte?>, folio: <?=$DteRecibido->folio?>}); return false" title="Imprimir el documento (<?=$Receptor->config_pdf_imprimir?>)" accesskey="P" class="nav-link">
            <i class="fa fa-print"></i>
            Imprimir
        </a>
    </li>
<?php endif; ?>
<?php endif; ?>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_recibidos/listar" title="Ir a los documentos recibidos" class="nav-link">
            <i class="fa fa-sign-in-alt"></i>
            Documentos recibidos
        </a>
    </li>
</ul>

<div class="page-header"><h1>Documento recibido T<?=$DteRecibido->dte?>F<?=$DteRecibido->folio?> <small>de <?=$Emisor->rut.'-'.$Emisor->dv?></small></h1></div>
<p>Esta es la página del documento recibido <?=$DteRecibido->getTipo()->tipo?> (<?=$DteRecibido->dte?>) folio número <?=$DteRecibido->folio?> del emisor <?=$Emisor->razon_social?> (<?=$Emisor->rut.'-'.$Emisor->dv?>) emitido a <?=$Receptor->razon_social?>.</p>

<script type="text/javascript">
$(function() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('#'+url.split('#')[1]+'-tab').tab('show');
    }
});
</script>

<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a href="#datos" aria-controls="datos" role="tab" data-toggle="tab" id="datos-tab" class="nav-link active" aria-selected="true">Datos básicos</a></li>
<?php if ($DteRecibido->hasXML()) : ?>
        <li class="nav-item"><a href="#pdf" aria-controls="pdf" role="tab" data-toggle="tab" id="pdf-tab" class="nav-link">PDF</a></li>
<?php endif; ?>
<?php if ($DteRecibido->getTipo()->permiteIntercambio()): ?>
        <li class="nav-item"><a href="#intercambio" aria-controls="intercambio" role="tab" data-toggle="tab" id="intercambio-tab" class="nav-link">Proceso intercambio</a></li>
<?php endif; ?>
<?php if ($DteRecibido->hasXML()) : ?>
        <li class="nav-item"><a href="#referencias" aria-controls="referencias" role="tab" data-toggle="tab" id="referencias-tab" class="nav-link">Referencias</a></li>
<?php endif; ?>
        <li class="nav-item"><a href="#avanzado" aria-controls="avanzado" role="tab" data-toggle="tab" id="avanzado-tab" class="nav-link">Avanzado</a></li>
    </ul>
    <div class="tab-content pt-4">

<!-- INICIO DATOS BÁSICOS -->
<div role="tabpanel" class="tab-pane active" id="datos" aria-labelledby="datos-tab">
<div class="row">
        <div class="col-md-9">
<?php
$t = new \sowerphp\general\View_Helper_Table();
$t->setShowEmptyCols(false);
echo $t->generate([
    ['Emisor', 'Documento', 'Folio', 'Fecha', 'Período', 'Exento', 'Neto', 'IVA', 'Total'],
    [
        $Emisor->razon_social,
        $DteRecibido->getTipo()->tipo,
        $DteRecibido->folio,
        \sowerphp\general\Utility_Date::format($DteRecibido->fecha),
        $DteRecibido->getPeriodo(),
        num($DteRecibido->exento),
        num($DteRecibido->neto),
        num($DteRecibido->iva),
        num($DteRecibido->total)
    ],
]);
?>
            <div class="row mt-2">
                <div class="col-md-4 mb-2">
                    <a class="btn btn-primary btn-lg btn-block<?=(!$DteRecibido->hasXML()?' disabled':'')?>" href="<?=$_base?>/dte/dte_recibidos/pdf/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" role="button">
                        <span class="far fa-file-pdf"></span>
                        Descargar PDF
                    </a>
                </div>
                <div class="col-md-4 mb-2">
                    <a class="btn btn-primary btn-lg btn-block<?=(!$DteRecibido->hasXML()?' disabled':'')?>" href="<?=$_base?>/dte/dte_recibidos/xml/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" role="button">
                        <span class="far fa-file-code"></span>
                        Descargar XML
                    </a>
                </div>
                <div class="col-md-4 mb-2">
                    <a class="btn btn-primary btn-lg btn-block<?=(!$DteRecibido->hasXML()?' disabled':'')?>" href="<?=$_base?>/dte/dte_recibidos/json/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" role="button">
                        <span class="far fa-file-code"></span>
                        Descargar JSON
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4 bg-light">
                <div class="card-header lead text-center">Estado en SII</div>
                <div class="card-body text-center">
                        <a href="#" onclick="__.popup('<?=$_base?>/dte/sii/verificar_datos/<?=$Receptor->getRUT()?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>/<?=$DteRecibido->fecha?>/<?=$DteRecibido->getTotal()?>/<?=$Emisor->getRUT()?>', 750, 550)" title="Verificar datos del documento en la web del SII">Verificar documento</a><br/>
<?php if ($DteRecibido->hasLocalXML()) : ?>
                        <a href="#" onclick="__.popup('<?=$_base?>/dte/dte_recibidos/verificar_datos_avanzado/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>', 750, 750)" title="Verificar datos avanzados del documento con el servicio web del SII">Verificación avanzada</a>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- FIN DATOS BÁSICOS -->

<!-- INICIO PDF -->
<div role="tabpanel" class="tab-pane" id="pdf" aria-labelledby="pdf-tab">
<?php
$f = new \sowerphp\general\View_Helper_Form();
echo $f->begin(['action'=>$_base.'/dte/dte_recibidos/pdf/'.$Emisor->rut.'/'.$DteRecibido->dte.'/'.$DteRecibido->folio, 'id'=>'pdfForm', 'onsubmit'=>'Form.check(\'pdfForm\')']);
echo $f->input([
    'type' => 'select',
    'name' => 'papelContinuo',
    'label' => 'Tipo papel',
    'options' => \sasco\LibreDTE\Sii\Dte\PDF\Dte::$papel,
    'value' => $Emisor->config_pdf_dte_papel,
    'check' => 'notempty',
]);
echo $f->input(['name'=>'copias_tributarias', 'label'=>'Copias tributarias', 'value'=>1, 'check'=>'notempty integer']);
echo $f->input(['name'=>'copias_cedibles', 'label'=>'Copias cedibles', 'value'=>0, 'check'=>'notempty integer']);
echo $f->end('Descargar PDF');
?>
</div>
<!-- FIN PDF -->

<?php if ($DteRecibido->getTipo()->permiteIntercambio()): ?>
<!-- INICIO INTERCAMBIO -->
<div role="tabpanel" class="tab-pane" id="intercambio" aria-labelledby="intercambio-tab">
<?php if (in_array($DteRecibido->dte, array_keys(\sasco\LibreDTE\Sii\RegistroCompraVenta::$dtes))) : ?>
    <div class="row mb-4">
        <div class="col-sm-6">
            <a href="#" onclick="__.popup('<?=$_base?>/dte/sii/dte_rcv/<?=$Emisor->rut?>-<?=$Emisor->dv?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>', 750, 550); return false" title="Ver datos del registro de compra/venta en el SII" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-search fa-fw"></i>
                Ver datos en el Registro de Compras del SII
            </a>
        </div>
        <div class="col-sm-6">
            <a href="<?=$_base?>/dte/registro_compras/ingresar_accion/<?=$Emisor->rut?>-<?=$Emisor->dv?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" title="Ingresar acción del registro de compra/venta en el SII" class="btn btn-primary btn-lg btn-block">
                <i class="fa fa-edit fa-fw"></i>
                Recibir / Reclamar
            </a>
        </div>
    </div>
<?php endif; ?>
<?php if (!empty($DteIntercambio)) : ?>
    <div class="card mb-4">
        <div class="card-header">Intercambio de DTE entre contribuyentes</div>
        <div class="card-body">
<?php
$de = $DteIntercambio->de;
if ($DteIntercambio->de!=$DteIntercambio->responder_a) {
    $de .= '<br/><span>'.$DteIntercambio->responder_a.'</span>';
}
new \sowerphp\general\View_Helper_Table([
    ['Recibido', 'De', 'Estado', 'Procesado'],
    [
        \sowerphp\general\Utility_Date::format($DteIntercambio->fecha_hora_email, 'd/m/Y H:i'),
        $de,
        $DteIntercambio->getEstado()->estado,
        $DteIntercambio->getUsuario()->usuario
    ],
]);
?>
        </div>
    </div>
    <a href="<?=$_base?>/dte/dte_intercambios/ver/<?=$DteIntercambio->codigo?>" class="btn btn-primary btn-lg btn-block">
        <i class="fa fa-exchange-alt fa-fw"></i>
        Ir a la página de intercambio del DTE
    </a>
<?php endif; ?>
</div>
<!-- FIN INTERCAMBIO -->
<?php endif; ?>

<?php if ($DteRecibido->hasLocalXML()) : ?>
<!-- INICIO REFERENCIAS -->
<div role="tabpanel" class="tab-pane" id="referencias" aria-labelledby="referencias-tab">
    <div class="card mb-4">
        <div class="card-header">Documentos referenciados</div>
        <div class="card-body">
<?php
// referencias que este documento hace a otros
if ($referenciados) {
    foreach($referenciados as &$referenciado) {
        if (!empty($referenciado['FchRef'])) {
            $referenciado['FchRef'] = \sowerphp\general\Utility_Date::format($referenciado['FchRef']);
        }
    }
    array_unshift($referenciados, ['#', 'DTE', 'Ind. Global', 'Folio', 'RUT otro cont.', 'Fecha', 'Código ref.', 'Razón ref.', 'Vendedor', 'Caja']);
    $t = new \sowerphp\general\View_Helper_Table();
    $t->setShowEmptyCols(false);
    echo $t->generate($referenciados);
} else {
    echo '<p>Este documento no hace referencia a otros.</p>',"\n";
}
?>
        </div>
    </div>
</div>
<!-- FIN REFERENCIAS -->
<?php endif; ?>

<!-- INICIO AVANZADO -->
<div role="tabpanel" class="tab-pane" id="avanzado" aria-labelledby="avanzado-tab">
<div class="row mb-4">
    <div class="col-md-6">
        <a class="btn btn-danger btn-lg btn-block" href="<?=$_base?>/dte/dte_recibidos/eliminar/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" role="button" onclick="return Form.confirm(this, '¿Confirmar la eliminación del documento?')">
            Eliminar documento
        </a>
    </div>
    <div class="col-md-6">
        <a class="btn btn-success btn-lg btn-block" href="<?=$_base?>/dte/dte_recibidos/modificar/<?=$DteRecibido->emisor?>/<?=$DteRecibido->dte?>/<?=$DteRecibido->folio?>" role="button" >
            Modificar documento
        </a>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-file-code"></i>
        Datos del documento
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <tbody>
<?php if ($DteRecibido->hasLocalXML()) : ?>
                <tr>
                    <th>ID del DTE</th>
                    <td><?=$DteRecibido->getDatos()['@attributes']['ID']?></td>
                </tr>
                <tr>
                    <th>Timbraje del XML</th>
                    <td><?=\sowerphp\general\Utility_Date::format(str_replace('T', ' ', $DteRecibido->getDatos()['TED']['DD']['TSTED']), 'd/m/Y H:i:s')?></td>
                </tr>
<?php endif; ?>
                <tr>
                    <th>Creación en LibreDTE</th>
                    <td><?=\sowerphp\general\Utility_Date::format($DteRecibido->fecha_hora_creacion, 'd/m/Y H:i:s')?></td>
                </tr>
                <tr>
                    <th>Usuario de LibreDTE</th>
                    <td><?=$DteRecibido->getUsuario()->usuario?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
<!-- FIN AVANZADO -->

    </div>
</div>