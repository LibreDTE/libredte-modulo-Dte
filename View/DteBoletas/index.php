<h1>Libro de boletas electrónicas</h1>
<?php
foreach ($periodos as &$p) {
    $p[] = ' <a href="dte_boletas/xml/'.$p['periodo'].'" title="Descargar XML del libro del período"><span class="fa fa-file-code-o btn btn-default"></span></a>';
}
array_unshift($periodos, ['Período', 'Boletas emitidas', 'Descargar']);
new \sowerphp\general\View_Helper_Table($periodos);
