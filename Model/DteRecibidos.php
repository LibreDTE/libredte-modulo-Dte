<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
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

// namespace del modelo
namespace website\Dte;

/**
 * Clase para mapear la tabla dte_recibido de la base de datos
 * Comentario de la tabla:
 * Esta clase permite trabajar sobre un conjunto de registros de la tabla dte_recibido
 * @author SowerPHP Code Generator
 * @version 2015-09-27 19:27:12
 */
class Model_DteRecibidos extends \Model_Plural_App
{

    // Datos para la conexión a la base de datos
    protected $_database = 'default'; ///< Base de datos del modelo
    protected $_table = 'dte_recibido'; ///< Tabla del modelo

    /**
     * Método que entrega el listado de documentos que tienen compras de
     * activos fijos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-01-31
     */
    public function getActivosFijos($filtros)
    {
        if (empty($filtros['desde']) or empty($filtros['hasta'])) {
            return false;
        }
        $where = ['r.fecha BETWEEN :desde AND :hasta'];
        $vars = [
                ':receptor' => $this->getContribuyente()->rut,
                ':certificacion' => $this->getContribuyente()->enCertificacion(),
                ':desde' => $filtros['desde'],
                ':hasta' => $filtros['hasta'],
        ];
        if (isset($filtros['sucursal'])) {
            if ($filtros['sucursal']) {
                $where[] = 'r.sucursal_sii_receptor = :sucursal';
                $vars[':sucursal'] = $filtros['sucursal'];
            } else {
                $where[] = 'r.sucursal_sii_receptor IS NULL';
            }
        }
        list($items, $precios) = $this->db->xml('i.archivo_xml', [
            '/*/SetDTE/DTE/*/Detalle/NmbItem',
            '/*/SetDTE/DTE/*/Detalle/PrcItem',
        ], 'http://www.sii.cl/SiiDte');
        $recibidos = $this->db->getTable('
            SELECT
                r.fecha,
                r.periodo,
                r.sucursal_sii_receptor AS sucursal,
                e.razon_social,
                r.emisor,
                r.intercambio,
                r.dte,
                t.tipo AS documento,
                r.folio,
                r.neto,
                r.monto_activo_fijo,
                r.monto_iva_activo_fijo,
                CASE WHEN r.neto = r.monto_activo_fijo THEN \'Total\' ELSE \'Parcial\' END AS montos,
                CASE WHEN r.intercambio IS NOT NULL THEN '.$items.' ELSE NULL END AS items,
                CASE WHEN r.intercambio IS NOT NULL THEN '.$precios.' ELSE NULL END AS precios
            FROM
                dte_recibido AS r
                JOIN contribuyente AS e ON r.emisor = e.rut
                JOIN dte_tipo AS t ON t.codigo = r.dte
                LEFT JOIN dte_intercambio AS i ON i.receptor = r.receptor AND i.codigo = r.intercambio AND i.certificacion = r.certificacion
            WHERE
                r.receptor = :receptor
                AND r.certificacion = :certificacion
                AND '.implode(' AND ', $where).'
                AND r.monto_activo_fijo IS NOT NULL
            ORDER BY r.fecha, r.sucursal_sii_receptor, r.emisor, r.folio
        ', $vars);
        foreach ($recibidos as &$f) {
            $f['sucursal'] = $this->getContribuyente()->getSucursal($f['sucursal'])->sucursal;
            if ($f['items']) {
                $f['items'] = explode('","', utf8_decode($f['items']));
                $f['precios'] = explode('","', utf8_decode($f['precios']));
            } else {
                $f['items'] = $f['precios'] = [];
            }
        }
        return $recibidos;
    }

    /**
     * Método que busca en los documentos recibidos de un contribuyente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-01-11
     */
    public function buscar($filtros)
    {
        // determinar receptor, fecha desde y hasta para la busqueda
        if (!empty($filtros['fecha'])) {
            $fecha_desde = $fecha_hasta = $filtros['fecha'];
        } else if (!empty($filtros['fecha_desde']) and !empty($filtros['fecha_hasta'])) {
            $fecha_desde = $filtros['fecha_desde'];
            $fecha_hasta = $filtros['fecha_hasta'];
        }
        if (empty($fecha_desde) or empty($fecha_hasta)) {
            throw new \Exception('Debe indicar una fecha o un rango para la búsqueda');
        }
        $where = ['d.receptor = :receptor', 'd.fecha BETWEEN :fecha_desde AND :fecha_hasta'];
        $vars = [':receptor'=>$this->getContribuyente()->rut, ':fecha_desde'=>$fecha_desde, ':fecha_hasta'=>$fecha_hasta];
        // filtro emisor
        if (!empty($filtros['emisor'])) {
            $where[] = 'd.emisor = :emisor';
            $vars[':emisor'] = $filtros['emisor'];
        }
        // filtro dte
        if (!empty($filtros['dte'])) {
            $where[] = 'd.dte = :dte';
            $vars[':dte'] = $filtros['dte'];
        }
        // filtro total
        if (!empty($filtros['total'])) {
            $where[] = 'd.total = :total';
            $vars[':total'] = $filtros['total'];
        } else if (!empty($filtros['total_desde']) and !empty($filtros['total_hasta'])) {
            $where[] = 'd.total BETWEEN :total_desde AND :total_hasta';
            $vars[':total_desde'] = $filtros['total_desde'];
            $vars[':total_hasta'] = $filtros['total_hasta'];
        }
        // realizar consultar
        return $this->db->getTable('
            SELECT
                d.fecha,
                d.emisor,
                e.razon_social,
                d.dte,
                d.folio,
                d.sucursal_sii_receptor AS sucursal,
                d.exento,
                d.neto,
                d.total
            FROM
                dte_recibido AS d
                JOIN contribuyente AS e ON d.emisor = e.rut
            WHERE '.implode(' AND ', $where).'
            ORDER BY d.fecha, d.emisor, d.dte, d.folio
        ', $vars);
    }

    /**
     * Método que entrega el detalle de las compras en un rango de tiempo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-30
     */
    public function getDetalle($desde, $hasta, $detalle)
    {
        if ($detalle) {
            $detalle_items = ', dte_recibido_get_detalle(r.emisor, r.dte, r.folio, r.certificacion) AS detalle';
        } else {
            $detalle_items = '';
        }
        // realizar consulta
        $datos = $this->db->getTable('
            SELECT
                t.codigo AS id,
                t.tipo,
                r.folio,
                r.fecha,
                '.$this->db->concat('e.rut', '-', 'e.dv').' AS rut,
                e.razon_social,
                r.exento,
                r.neto,
                r.iva,
                r.total,
                r.periodo,
                r.sucursal_sii_receptor AS sucursal,
                u.usuario,
                r.rcv_accion,
                r.tipo_transaccion
                '.$detalle_items.'
            FROM
                dte_recibido AS r
                JOIN dte_tipo AS t ON r.dte = t.codigo
                JOIN contribuyente AS e ON r.emisor = e.rut
                JOIN usuario AS u ON r.usuario = u.id
            WHERE
                r.receptor = :receptor
                AND r.certificacion = :certificacion
                AND r.fecha BETWEEN :desde AND :hasta
            ORDER BY r.fecha, r.dte, r.folio
        ', [':receptor'=>$this->getContribuyente()->rut, ':certificacion'=>$this->getContribuyente()->enCertificacion(), ':desde'=>$desde, ':hasta'=>$hasta]);
        foreach ($datos as &$dato) {
            $dato['id'] = 'T'.$dato['id'].'F'.$dato['folio'];
        }
        if ($detalle) {
            $datos = \sowerphp\core\Utility_Array::fromTableWithHeaderAndBody($datos, 15, 'items');
        }
        foreach ($datos as &$d) {
            if ($detalle) {
                $items = [];
                foreach ($d['items'] as $isp) {
                    $item = str_getcsv(trim($isp['detalle'], '()'));
                    if ($item[3]) {
                        $item[3] = $item[7];
                        $item[7] = null;
                    }
                    $items[] = $item;
                }
                $d['items'] = $items;
            }
            $d['sucursal'] = $this->getContribuyente()->getSucursal($d['sucursal'])->sucursal;
            if ($d['rcv_accion'] and !empty(\sasco\LibreDTE\Sii\RegistroCompraVenta::$acciones[$d['rcv_accion']])) {
                $d['rcv_accion'] = \sasco\LibreDTE\Sii\RegistroCompraVenta::$acciones[$d['rcv_accion']];
            }
            if ($d['tipo_transaccion'] and !empty(\sasco\LibreDTE\Sii\RegistroCompraVenta::$tipo_transacciones[$d['tipo_transaccion']])) {
                $d['tipo_transaccion'] = \sasco\LibreDTE\Sii\RegistroCompraVenta::$tipo_transacciones[$d['tipo_transaccion']];
            }
        }
        return $datos;
    }

    /**
     * Método que entrega los totales de documentos emitidos por tipo de DTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-24
     */
    public function getPorTipo($desde, $hasta)
    {
        return $this->db->getTable('
            SELECT t.tipo, COUNT(*) AS total
            FROM dte_recibido AS r JOIN dte_tipo AS t ON r.dte = t.codigo
            WHERE
                r.receptor = :receptor
                AND r.certificacion = :certificacion
                AND r.fecha BETWEEN :desde AND :hasta
            GROUP BY t.tipo
            ORDER BY total DESC
        ', [':receptor'=>$this->getContribuyente()->rut, ':certificacion'=>$this->getContribuyente()->enCertificacion(), ':desde'=>$desde, ':hasta'=>$hasta]);
    }

    /**
     * Método que entrega los totales de documentos emitidos por día
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-30
     */
    public function getPorDia($desde, $hasta)
    {
        return $this->db->getTable('
            SELECT fecha AS dia, COUNT(*) AS total
            FROM dte_recibido
            WHERE
                receptor = :receptor
                AND certificacion = :certificacion
                AND fecha BETWEEN :desde AND :hasta
            GROUP BY fecha
            ORDER BY fecha
        ', [':receptor'=>$this->getContribuyente()->rut, ':certificacion'=>$this->getContribuyente()->enCertificacion(), ':desde'=>$desde, ':hasta'=>$hasta]);
    }

    /**
     * Método que entrega los totales de documentos emitidos por sucursal
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-30
     */
    public function getPorSucursal($desde, $hasta)
    {
        $datos = $this->db->getTable('
            SELECT COALESCE(sucursal_sii_receptor, 0) AS sucursal, COUNT(*) AS total
            FROM dte_recibido
            WHERE
                receptor = :receptor
                AND certificacion = :certificacion
                AND fecha BETWEEN :desde AND :hasta
            GROUP BY sucursal
            ORDER BY total DESC
        ', [':receptor'=>$this->getContribuyente()->rut, ':certificacion'=>$this->getContribuyente()->enCertificacion(), ':desde'=>$desde, ':hasta'=>$hasta]);
        foreach($datos as &$d) {
            $d['sucursal'] = $this->getContribuyente()->getSucursal($d['sucursal'])->sucursal;
        }
        return $datos;
    }

    /**
     * Método que entrega los totales de documentos emitidos por usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-06-30
     */
    public function getPorUsuario($desde, $hasta)
    {
        return $this->db->getTable('
            SELECT u.usuario, COUNT(*) AS total
            FROM dte_recibido AS r JOIN usuario AS u ON r.usuario = u.id
            WHERE
                r.receptor = :receptor
                AND r.certificacion = :certificacion
                AND r.fecha BETWEEN :desde AND :hasta
            GROUP BY u.usuario
            ORDER BY total DESC
        ', [':receptor'=>$this->getContribuyente()->rut, ':certificacion'=>$this->getContribuyente()->enCertificacion(), ':desde'=>$desde, ':hasta'=>$hasta]);
    }

}
