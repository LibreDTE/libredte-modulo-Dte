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

// namespace del controlador
namespace website\Dte;

/**
 * Clase para el controlador asociado a la tabla contribuyente de la base de
 * datos
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2015-09-19
 */
class Controller_Contribuyentes extends \Controller_App
{

    private $permisos_usuarios = [
        'todos' => 'Todos los permisos',
        /*'folios' => 'Cargar folios',
        'emitir' => 'Emitir documentos tributarios electrónicos',
        'libros' => 'Generar y consultar libros de compra y venta',
        'intercambio' => 'Intercambio entre contribuyentes',
        'respaldos' => 'Realizar respaldos de los datos',*/
    ];

    /**
     * Método que permite entrar a las opciones de cualquier empresa para dar
     * soporte, se debe estar en el grupo soporte para que la acción funcione
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-12
     */
    public function soporte()
    {
        // si el usuario no está en el grupo soporte error
        if (!$this->Auth->User->inGroup(['soporte'])) {
            \sowerphp\core\Model_Datasource_Session::message('No está autorizado a prestar soporte a otros contribuyentes', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // verificar datos de formulario
        if (empty($_POST['rut']) or empty($_POST['accion'])) {
            \sowerphp\core\Model_Datasource_Session::message('Debe indicar RUT de empresa y acción a realizar', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // rederigir
        $rut = \sowerphp\app\Utility_Rut::normalizar($_POST['rut']);
        $this->redirect('/dte/contribuyentes/'.$_POST['accion'].'/'.$rut);
    }

    /**
     * Método que selecciona la empresa con la que se trabajará en el módulo DTE
     * @param rut Si se pasa un RUT se tratará de seleccionar
     * @param url URL a la que redirigir después de seleccionar el contribuyente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-06-17
     */
    public function seleccionar($rut = null, $url = null)
    {
        $referer = \sowerphp\core\Model_Datasource_Session::read('referer');
        // si se está pidiendo una empresa en particular se tratará de usar
        if ($rut) {
            $Emisor = new Model_Contribuyente($rut);
            if (!$Emisor->usuario) {
                \sowerphp\core\Model_Datasource_Session::message('Empresa solicitada no está registrada', 'error');
                $this->redirect('/dte/contribuyentes/seleccionar');
            }
            if (!$Emisor->usuarioAutorizado($this->Auth->User)) {
                \sowerphp\core\Model_Datasource_Session::message('No está autorizado a operar con la empresa solicitada', 'error');
                $this->redirect('/dte/contribuyentes/seleccionar');
            }
            if (!$url) {
                \sowerphp\core\Model_Datasource_Session::message('Desde ahora estará operando con '.$Emisor->razon_social);
            }
        }
        // si no se indicó una empresa por su rut se tratará de usar la que
        // esté configurada (si existe) o bien se mostrará listado de las
        // empresas a las que el usuario tiene acceso para poder elegir alguna
        else {
            // si hay una empresa forzada a través de la configuración se crea
            $empresa = \sowerphp\core\Configure::read('dte.empresa');
            if ($empresa) {
                $Emisor = new Model_Contribuyente();
                $Emisor->set($empresa);
                \sowerphp\core\Model_Datasource_Session::message(); // borrar mensaje de sesión si había
            }
        }
        // si se llegó acá con un emisor se guarda en la sesión
        if (isset($Emisor)) {
            $this->setContribuyente($Emisor);
            // redireccionar
            if ($referer)
                \sowerphp\core\Model_Datasource_Session::delete('referer');
            else if ($url)
                $referer = base64_decode($url);
            else
                $referer = '/dte';
            $this->redirect($referer);
        }
        // asignar variables para la vista
        $this->set([
            'empresas' => (new Model_Contribuyentes())->getByUsuario($this->Auth->User->id),
            'registrar_empresa' => $this->Auth->check('/dte/contribuyentes/registrar'),
            'soporte' => $this->Auth->User->inGroup(['soporte']),
        ]);
    }

    /**
     * Método que permite registrar un nuevo contribuyente y asociarlo a un usuario
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-08-01
     */
    public function registrar()
    {
        // asignar variables para la vista
        $ImpuestosAdicionales = new \website\Dte\Admin\Mantenedores\Model_ImpuestoAdicionales();
        $impuestos_adicionales = $ImpuestosAdicionales->getListConTasa();
        $impuestos_adicionales_tasa = $ImpuestosAdicionales->getTasas();
        $this->set([
            '_header_extra' => ['js'=>['/dte/js/dte.js', '/dte/js/contribuyente.js']],
            'actividades_economicas' => (new \website\Sistema\General\Model_ActividadEconomicas())->getList(),
            'comunas' => (new \sowerphp\app\Sistema\General\DivisionGeopolitica\Model_Comunas())->getList(),
            'impuestos_adicionales' => $impuestos_adicionales,
            'impuestos_adicionales_tasa' => $impuestos_adicionales_tasa,
            'cuentas' => [],
            'titulo' => 'Registrar nueva empresa',
            'descripcion' => 'Aquí podrá registrar una nueva empresa para la cual usted será el usuario administrador de la misma. Deberá completar los datos obligatorios de las pestañas "Datos empresa", "Ambientes" e "Emails". Las otras dos pestañas son opcionales.',
            'form_id' => 'registrarContribuyente',
            'boton' => 'Registrar empresa',
        ]);
        // si se envió el formulario se procesa
        if (isset($_POST['submit'])) {
            // crear objeto del contribuyente con el rut y verificar que no esté ya asociada a un usuario
            list($rut, $dv) = explode('-', str_replace('.', '', $_POST['rut']));
            $Contribuyente = new Model_Contribuyente($rut);
            if ($Contribuyente->usuario) {
                if ($Contribuyente->usuario==$this->Auth->User->id) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'Ya tiene asociada la empresa a su usuario'
                    );
                } else {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'La empresa ya está registrada a nombre del usuario '.$Contribuyente->getUsuario()->nombre.' ('.$Contribuyente->getUsuario()->email.'). Si cree que esto es un error o bien puede ser alguien suplantando la identidad de su empresa por favor <a href="'.$this->request->base.'/contacto" target="_blank">contáctenos</a>.', 'error'
                    );
                }
                $this->redirect('/dte/contribuyentes/seleccionar');
            }
            // rellenar campos de la empresa
            $this->prepararDatosContribuyente($Contribuyente);
            $Contribuyente->set($_POST);
            $Contribuyente->rut = $rut;
            $Contribuyente->dv = $dv;
            $Contribuyente->usuario = $this->Auth->User->id;
            $Contribuyente->modificado = date('Y-m-d H:i:s');
            // guardar contribuyente
            try {
                $Contribuyente->save(true);
                // guardar los DTE por defecto que la empresa podrá usar
                $dtes = \sowerphp\core\Configure::read('dte.dtes');
                foreach ($dtes as $dte) {
                    $ContribuyenteDte = new \website\Dte\Admin\Mantenedores\Model_ContribuyenteDte(
                        $Contribuyente->rut, $dte
                    );
                    try {
                        $ContribuyenteDte->save();
                    } catch (\sowerphp\core\Exception_Model_Datasource_Database $e){}
                }
                // redireccionar
                \sowerphp\core\Model_Datasource_Session::message('Empresa '.$Contribuyente->razon_social.' registrada y asociada a su usuario', 'ok');
                $this->redirect('/dte/contribuyentes/seleccionar');
            } catch (\Exception $e) {
                \sowerphp\core\Model_Datasource_Session::message('No fue posible registrar la empresa:<br/>'.$e->getMessage(), 'error');
            }
        }
        // renderizar vista
        $this->autoRender = false;
        $this->render('Contribuyentes/registrar_modificar');
    }

    /**
     * Método que permite modificar contribuyente previamente registrado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-09-27
     */
    public function modificar($rut)
    {
        // crear objeto del contribuyente
        try {
            $Contribuyente = new Model_Contribuyente($rut);
        } catch (\sowerphp\core\Exception_Model_Datasource_Database $e) {
            \sowerphp\core\Model_Datasource_Session::message('No se encontró la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // verificar que el usuario sea el administrador o de soporte autorizado
        if ($Contribuyente->usuario!=$this->Auth->User->id and (!$Contribuyente->config_app_soporte or !$this->Auth->User->inGroup(['soporte']))) {
            \sowerphp\core\Model_Datasource_Session::message('Usted no es el administrador de la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // asignar variables para editar
        $ImpuestosAdicionales = new \website\Dte\Admin\Mantenedores\Model_ImpuestoAdicionales();
        $impuestos_adicionales = $ImpuestosAdicionales->getListConTasa();
        $impuestos_adicionales_tasa = $ImpuestosAdicionales->getTasas();
        $this->set([
            '_header_extra' => ['js'=>['/dte/js/contribuyente.js']],
            'Contribuyente' => $Contribuyente,
            'actividades_economicas' => (new \website\Sistema\General\Model_ActividadEconomicas())->getList(),
            'comunas' => (new \sowerphp\app\Sistema\General\DivisionGeopolitica\Model_Comunas())->getList(),
            'impuestos_adicionales' => $impuestos_adicionales,
            'impuestos_adicionales_tasa' => $impuestos_adicionales_tasa,
            'cuentas' => [],
            'titulo' => 'Modificar empresa '.$Contribuyente->razon_social,
            'descripcion' => 'Aquí podrá modificar los datos de la empresa '.$Contribuyente->razon_social.' RUT '.num($Contribuyente->rut).'-'.$Contribuyente->dv.', para la cual usted es el usuario administrador.',
            'form_id' => 'modificarContribuyente',
            'boton' => 'Modificar empresa',
            'tipos_dte' => $Contribuyente->getDocumentosAutorizados(),
        ]);
        // editar contribuyente
        if (isset($_POST['submit'])) {
            $this->prepararDatosContribuyente($Contribuyente);
            $Contribuyente->set($_POST);
            $Contribuyente->modificado = date('Y-m-d H:i:s');
            try {
                $Contribuyente->save(true);
                \sowerphp\core\Model_Datasource_Session::message('Empresa '.$Contribuyente->razon_social.' ha sido modificada', 'ok');
                $this->redirect('/dte/contribuyentes/seleccionar');
            } catch (\Exception $e) {
                \sowerphp\core\Model_Datasource_Session::message('No fue posible modificar la empresa:<br/>'.$e->getMessage(), 'error');
            }
        }
        // renderizar vista
        $this->autoRender = false;
        $this->render('Contribuyentes/registrar_modificar');
    }

    /**
     * Método que prepara los datos de configuraciones del contribuyente para
     * ser guardados
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-12-06
     */
    private function prepararDatosContribuyente(&$Contribuyente)
    {
        // si hay cualquier campo que empiece por 'config_libredte_' se quita ya que son
        // configuraciones reservadas para los administradores de LibreDTE y no pueden
        // ser asignadas por los usuarios (esto evita que envién "a la mala" una
        // configuración del sistema)
        foreach ($_POST as $var => $val) {
            if (strpos($var, 'config_libredte_')===0) {
                unset($_POST[$var]);
            }
        }
        // crear arreglo con actividades económicas secundarias
        if (!empty($_POST['config_extra_otras_actividades_actividad'])) {
            $n_codigos = count($_POST['config_extra_otras_actividades_actividad']);
            for ($i=0; $i<$n_codigos; $i++) {
                if (!empty($_POST['config_extra_otras_actividades_actividad'][$i])) {
                    $_POST['config_extra_otras_actividades'][] = [
                        'actividad' => (int)$_POST['config_extra_otras_actividades_actividad'][$i],
                        'giro' => $_POST['config_extra_otras_actividades_giro'][$i],
                    ];
                }
            }
            unset($_POST['config_extra_otras_actividades_actividad']);
            unset($_POST['config_extra_otras_actividades_giro']);
        } else {
            $_POST['config_extra_otras_actividades'] = null;
        }
        // crear arreglo con sucursales
        if (!empty($_POST['config_extra_sucursales_codigo'])) {
            $_POST['config_extra_sucursales'] = [];
            $n_codigos = count($_POST['config_extra_sucursales_codigo']);
            for ($i=0; $i<$n_codigos; $i++) {
                if (!empty($_POST['config_extra_sucursales_codigo'][$i]) and !empty($_POST['config_extra_sucursales_sucursal'][$i]) and !empty($_POST['config_extra_sucursales_direccion'][$i]) and !empty($_POST['config_extra_sucursales_comuna'][$i])) {
                    $_POST['config_extra_sucursales'][] = [
                        'codigo' => (int)$_POST['config_extra_sucursales_codigo'][$i],
                        'sucursal' => $_POST['config_extra_sucursales_sucursal'][$i],
                        'direccion' => $_POST['config_extra_sucursales_direccion'][$i],
                        'comuna' => $_POST['config_extra_sucursales_comuna'][$i],
                        'actividad_economica' => $_POST['config_extra_sucursales_actividad_economica'][$i],
                    ];
                }
            }
            unset($_POST['config_extra_sucursales_codigo']);
            unset($_POST['config_extra_sucursales_sucursal']);
            unset($_POST['config_extra_sucursales_direccion']);
            unset($_POST['config_extra_sucursales_comuna']);
            unset($_POST['config_extra_sucursales_actividad_economica']);
        } else {
            $_POST['config_extra_sucursales'] = null;
        }
        // crear arreglo de impuestos adicionales
        if (!empty($_POST['config_extra_impuestos_adicionales_codigo'])) {
            $_POST['config_extra_impuestos_adicionales'] = [];
            $n_codigos = count($_POST['config_extra_impuestos_adicionales_codigo']);
            for ($i=0; $i<$n_codigos; $i++) {
                if (!empty($_POST['config_extra_impuestos_adicionales_codigo'][$i]) and !empty($_POST['config_extra_impuestos_adicionales_tasa'][$i])) {
                    $_POST['config_extra_impuestos_adicionales'][] = [
                        'codigo' => (int)$_POST['config_extra_impuestos_adicionales_codigo'][$i],
                        'tasa' => $_POST['config_extra_impuestos_adicionales_tasa'][$i],
                    ];
                }
            }
            unset($_POST['config_extra_impuestos_adicionales_codigo']);
            unset($_POST['config_extra_impuestos_adicionales_tasa']);
        } else {
            $_POST['config_extra_impuestos_adicionales'] = null;
        }
        // crear arreglo con observaciones
        if (!empty($_POST['config_emision_observaciones_dte'])) {
            $_POST['config_emision_observaciones'] = [];
            $n_codigos = count($_POST['config_emision_observaciones_dte']);
            for ($i=0; $i<$n_codigos; $i++) {
                if (!empty($_POST['config_emision_observaciones_dte'][$i]) and !empty($_POST['config_emision_observaciones_glosa'][$i])) {
                    $dte = (int)$_POST['config_emision_observaciones_dte'][$i];
                    $glosa = $_POST['config_emision_observaciones_glosa'][$i];
                    $_POST['config_emision_observaciones'][$dte] = $glosa;
                }
            }
            unset($_POST['config_emision_observaciones_dte']);
            unset($_POST['config_emision_observaciones_glosa']);
        } else {
            $_POST['config_emision_observaciones'] = null;
        }
        // crear arreglo con anchos de columnas del detalle del PDF
        $config_pdf_detalle_ancho = [];
        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 25)=='config_pdf_detalle_ancho_') {
                $config_pdf_detalle_ancho[substr($key, 25)] = $val;
                unset($_POST[$key]);
            }
        }
        if ($config_pdf_detalle_ancho) {
            $_POST['config_pdf_detalle_ancho'] = $config_pdf_detalle_ancho;
        }
        // crear arreglo con datos de contacto comercial
        if (!empty($_POST['config_app_contacto_comercial_email'])) {
            $n_emails = count($_POST['config_app_contacto_comercial_email']);
            for ($i=0; $i<$n_emails; $i++) {
                if (!empty($_POST['config_app_contacto_comercial_email'][$i])) {
                    $_POST['config_app_contacto_comercial'][] = [
                        'nombre' => !empty($_POST['config_app_contacto_comercial_nombre'][$i]) ? $_POST['config_app_contacto_comercial_nombre'][$i] : null,
                        'email' => $_POST['config_app_contacto_comercial_email'][$i],
                        'telefono' => !empty($_POST['config_app_contacto_comercial_telefono'][$i]) ? $_POST['config_app_contacto_comercial_telefono'][$i] : null,
                    ];
                }
            }
            unset($_POST['config_app_contacto_comercial_nombre']);
            unset($_POST['config_app_contacto_comercial_email']);
            unset($_POST['config_app_contacto_comercial_telefono']);
        } else {
            $_POST['config_app_contacto_comercial'] = null;
        }
        // poner valores por defecto
        foreach (Model_Contribuyente::$defaultConfig as $key => $value) {
            if (!isset($_POST['config_'.$key])) {
                $Contribuyente->{'config_'.$key} = $value;
            }
        }
    }

    /**
     * Método que permite editar los usuarios autorizados de un contribuyente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-12
     */
    public function usuarios($rut)
    {
        // crear objeto del contribuyente
        try {
            $Contribuyente = new Model_Contribuyente($rut);
        } catch (\sowerphp\core\Exception_Model_Datasource_Database $e) {
            \sowerphp\core\Model_Datasource_Session::message('No se encontró la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // verificar que el usuario sea el administrador o sea soporte autorizado
        if ($Contribuyente->usuario!=$this->Auth->User->id and (!$Contribuyente->config_app_soporte or !$this->Auth->User->inGroup(['soporte']))) {
            \sowerphp\core\Model_Datasource_Session::message('Usted no es el administrador de la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // asignar variables para editar
        $this->set([
            'Contribuyente' => $Contribuyente,
            'permisos_usuarios' => $this->permisos_usuarios,
        ]);
        // editar usuarios autorizados
        if (isset($_POST['submit'])) {
            $usuarios = [];
            if (isset($_POST['usuario'])) {
                $n_usuarios = count($_POST['usuario']);
                for ($i=0; $i<$n_usuarios; $i++) {
                    if (!empty($_POST['usuario'][$i]) and !empty($_POST['permiso'][$i])) {
                        if (!isset($usuarios[$_POST['usuario'][$i]]))
                            $usuarios[$_POST['usuario'][$i]] = [];
                        if (!array_key_exists($_POST['permiso'][$i], $this->permisos_usuarios)) {
                            \sowerphp\core\Model_Datasource_Session::message(
                                'El permiso <em>'.$_POST['permiso'][$i].'</em> no existe', 'warning'
                            );
                            return;
                        }
                        $usuarios[$_POST['usuario'][$i]][] = $_POST['permiso'][$i];
                    }
                }
                if (!$usuarios) {
                    \sowerphp\core\Model_Datasource_Session::message(
                        'No indicó ningún usuario autorizado para agregar', 'warning'
                    );
                    return;
                }
            }
            try {
                $Contribuyente->setUsuarios($usuarios);
                \sowerphp\core\Model_Datasource_Session::message(
                    'Se editaron los usuarios autorizados de la empresa '.$Contribuyente->razon_social, 'ok'
                );
                $this->redirect('/dte/contribuyentes/seleccionar');
            } catch (\sowerphp\core\Exception_Model_Datasource_Database $e) {
                \sowerphp\core\Model_Datasource_Session::message(
                    'No fue posible editar los usuarios autorizados<br/>'.$e->getMessage(), 'error'
                );
            } catch (\Exception $e) {
                \sowerphp\core\Model_Datasource_Session::message(
                    $e->getMessage(), 'error'
                );
            }
        }
    }

    /**
     * Método que permite transferir una empresa a un nuevo usuario administrador
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-22
     */
    public function transferir($rut)
    {
        if (empty($_POST['usuario'])) {
            \sowerphp\core\Model_Datasource_Session::message('Debe especificar el nuevo usuario administrador', 'error');
            $this->redirect('/dte/contribuyentes/usuarios/'.$rut);
        }
        // crear objeto del contribuyente
        try {
            $Contribuyente = new Model_Contribuyente($rut);
        } catch (\sowerphp\core\Exception_Model_Datasource_Database $e) {
            \sowerphp\core\Model_Datasource_Session::message('No se encontró la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // verificar que el usuario sea el administrador
        if ($Contribuyente->usuario!=$this->Auth->User->id) {
            \sowerphp\core\Model_Datasource_Session::message('Usted no es el administrador de la empresa solicitada', 'error');
            $this->redirect('/dte/contribuyentes/seleccionar');
        }
        // transferir al nuevo usuario administrador
        $Usuario = new \sowerphp\app\Sistema\Usuarios\Model_Usuario($_POST['usuario']);
        if (!$Usuario->exists()) {
            \sowerphp\core\Model_Datasource_Session::message('Usuario '.$_POST['usuario'].' no existe', 'error');
            $this->redirect('/dte/contribuyentes/usuarios/'.$rut);
        }
        if ($Contribuyente->usuario == $Usuario->id) {
            \sowerphp\core\Model_Datasource_Session::message('El usuario administrador ya es '.$_POST['usuario'], 'warning');
            $this->redirect('/dte/contribuyentes/usuarios/'.$rut);
        }
        $Contribuyente->usuario = $Usuario->id;
        if ($Contribuyente->save()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Se actualizó el usuario administrador a '.$_POST['usuario'], 'ok'
            );
        } else {
            \sowerphp\core\Model_Datasource_Session::message(
                'No fue posible cambiar el administrador de la empresa', 'error'
            );
        }
        $this->redirect('/dte/contribuyentes/seleccionar');
    }

    /**
     * Acción que entrega el logo del contribuyente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2015-09-29
     */
    public function logo($rut)
    {
        $Contribuyente = new Model_Contribuyente(substr($rut, 0, -4));
        if (!$Contribuyente->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Contribuyente solicitado no existe'
            );
            $this->redirect('/');
        }
        $dir = \sowerphp\core\Configure::read('dte.logos.dir');
        $logo = $dir.'/'.$Contribuyente->rut.'.png';
        if (!is_readable($logo))
            $logo = $dir.'/default.png';
        header('Content-Type: image/png');
        header('Content-Length: '.filesize($logo));
        header('Content-Disposition: inline; filename="'.$Contribuyente->rut.'.png"');
        print file_get_contents($logo);
        exit;
    }

    /**
     * Método de la API que permite obtener los datos de un contribuyente
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-03-18
     */
    public function _api_info_GET($rut)
    {
        if ($this->Auth->User) {
            $User = $this->Auth->User;
        } else {
            $User = $this->Api->getAuthUser();
            if (is_string($User)) {
                $this->Api->send($User, 401);
            }
        }
        $Contribuyente = new Model_Contribuyente($rut);
        if (!$Contribuyente->exists())
            $this->Api->send('Contribuyente solicitado no existe', 404);
        $Contribuyente->config_ambiente_produccion_fecha;
        $Contribuyente->config_ambiente_produccion_numero;
        $Contribuyente->config_email_intercambio_user;
        $Contribuyente->config_extra_representante_rut;
        $Contribuyente->config_extra_contador_rut;
        $Contribuyente->config_extra_web;
        $this->Api->send($Contribuyente, 200, JSON_PRETTY_PRINT);
    }

}
