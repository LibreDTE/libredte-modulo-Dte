<?php

/**
 * SowerPHP
 * Copyright (C) SowerPHP (http://sowerphp.org)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

// namespace del controlador
namespace website\Dte;

/**
 * Clase para el controlador asociado a la tabla boleta_tercero de la base de
 * datos
 * Comentario de la tabla:
 * Esta clase permite controlar las acciones entre el modelo y vista para la
 * tabla boleta_tercero
 * @author SowerPHP Code Generator
 * @version 2019-08-09 15:59:48
 */
class Controller_BoletaTerceros extends \Controller_App
{

    /**
     * Acción que muestra un resumen por período donde hayan boletas emitidas
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function index()
    {
        $Emisor = $this->getContribuyente();
        $periodos = (new Model_BoletaTerceros())->setContribuyente($Emisor)->getPeriodos();
        $this->set('periodos', $periodos);
    }

    /**
     * Acción para el buscador de boletas de honorario electróncias
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function buscar()
    {
        $Emisor = $this->getContribuyente();
        $this->set('Emisor', $Emisor);
        if (isset($_POST['submit'])) {
            unset($_POST['submit']);
            // obtener PDF desde servicio web
            $r = $this->consume('/api/dte/boleta_terceros/buscar/'.$Emisor->rut, $_POST);
            if ($r['status']['code']!=200) {
                \sowerphp\core\Model_Datasource_Session::message($r['body'], 'error');
                return;
            }
            if (empty($r['body'])) {
                \sowerphp\core\Model_Datasource_Session::message('No se encontraron boletas para la búsqueda solicitada', 'warning');
            }
            $this->set('boletas', $r['body']);
        }
    }

    /**
     * API que permite buscar boletas de honorario electrónicas recibidas en el SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-13
     */
    public function _api_buscar_POST($emisor)
    {
        // usuario autenticado
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        // crear emisor
        $Emisor = new Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/boleta_terceros/buscar')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada', 403);
        }
        // obtener boletas
        $filtros = [];
        foreach ($this->Api->data as $key => $val) {
            if (!empty($val)) {
                $filtros[$key] = $val;
            }
        }
        if (empty($filtros)) {
            $this->Api->send('Debe definir a lo menos un filtro para la búsqueda', 400);
        }
        $boletas = (new Model_BoletaTerceros())->setContribuyente($Emisor)->buscar($filtros, 'DESC');
        $this->Api->send($boletas, 200, JSON_PRETTY_PRINT);
    }

    /**
     * Acción que permite descargar el HTML de una boleta de terceros electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-13
     */
    public function html($numero)
    {
        $Emisor = $this->getContribuyente();
        $BoletaTercero = new Model_BoletaTercero($Emisor->rut, $numero);
        if (!$BoletaTercero->exists()) {
            \sowerphp\core\Model_Datasource_Session::message('No existe la boleta solicitada', 'error');
            $this->redirect('/dte/boleta_terceros');
        }
        // obtener PDF desde servicio web
        $r = $this->consume('/api/dte/boleta_terceros/html/'.$BoletaTercero->numero.'/'.$BoletaTercero->emisor);
        if ($r['status']['code']!=200) {
            \sowerphp\core\Model_Datasource_Session::message($r['body'], 'error');
            $this->redirect('/dte/boleta_terceros');
        }
        $this->Api->response()->type('text/html');
        $this->Api->response()->header('Content-Disposition', 'attachment; filename=bte_'.$BoletaTercero->emisor.'_'.$BoletaTercero->numero.'.html');
        $this->Api->response()->header('Pragma', 'no-cache');
        $this->Api->response()->header('Expires', 0);
        $this->Api->send($r['body']);
    }

    /**
     * API que permite descargar el HTML de una boleta de terceros electrónica
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-13
     */
    public function _api_html_GET($numero, $emisor)
    {
        // usuario autenticado
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        // crear emisor
        $Emisor = new Model_Contribuyente($emisor);
        if (!$Emisor->exists()) {
            $this->Api->send('Emisor no existe', 404);
        }
        if (!$Emisor->usuarioAutorizado($User, '/dte/boleta_terceros/html')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada', 403);
        }
        // obtener boleta
        $BoletaTercero = new Model_BoletaTercero($emisor, $numero);
        if (!$BoletaTercero->exists()) {
            $this->Api->send('No existe la boleta solicitada', 404);
        }
        // obtener pdf
        try {
            $html = $BoletaTercero->getHTML();
        } catch (\Exception $e) {
            $this->Api->send($e->getMessage(), 500);
        }
        // entregar boleta
        $this->Api->response()->type('text/html');
        $this->Api->response()->header('Content-Disposition', 'attachment; filename=bte_'.$BoletaTercero->emisor.'_'.$BoletaTercero->numero.'.html');
        $this->Api->response()->header('Pragma', 'no-cache');
        $this->Api->response()->header('Expires', 0);
        $this->Api->send($html);
    }

    /**
     * Acción para ver boletas de un período en particular
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function ver($periodo)
    {
        $Emisor = $this->getContribuyente();
        $boletas = (new Model_BoletaTerceros())->setContribuyente($Emisor)->buscar(['periodo'=>$periodo]);
        if (empty($boletas)) {
            \sowerphp\core\Model_Datasource_Session::message('No existen boletas para el período solicitado', 'error');
            $this->redirect('/dte/boleta_terceros');
        }
        $this->set([
            'Emisor' => $Emisor,
            'periodo' => $periodo,
            'boletas' => $boletas,
        ]);
    }

    /**
     * Acción para descargar el CSV con las boletas de un periodo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function csv($periodo)
    {
        $Emisor = $this->getContribuyente();
        $boletas = (new Model_BoletaTerceros())->setContribuyente($Emisor)->buscar(['periodo'=>$periodo]);
        if (empty($boletas)) {
            \sowerphp\core\Model_Datasource_Session::message('No existen boletas para el período solicitado', 'error');
            $this->redirect('/dte/boleta_terceros');
        }
        foreach ($boletas as &$b) {
            unset($b['codigo']);
        }
        array_unshift($boletas, array_keys($boletas[0]));
        $csv = \sowerphp\general\Utility_Spreadsheet_CSV::get($boletas);
        $this->response->sendContent($csv, $Emisor->rut.'-'.$Emisor->dv.'_bte_'.(int)$periodo.'.csv');
    }

    /**
     * Acción para emitir una boleta de terceros electrónica
     * @todo Programar acción
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function emitir()
    {
        $Emisor = $this->getContribuyente();
        $this->set([
            'Emisor' => $Emisor,
        ]);
    }

    /**
     * Acción para actualizar el listado de boletas desde el SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-08-10
     */
    public function actualizar()
    {
        $Emisor = $this->getContribuyente();
        try {
            (new Model_BoletaTerceros())->setContribuyente($Emisor)->sincronizar(2);
            \sowerphp\core\Model_Datasource_Session::message('Boletas actualizadas', 'ok');
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message($e->getMessage(), 'error');
        }
        $this->redirect('/dte/boleta_terceros');
    }

}
