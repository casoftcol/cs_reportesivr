<?php

/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.4-1                                                |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2009-01-06 09:01:38 bmacias bmacias@palosanto.com Exp $ */
//include issabel framework

//DESARROLLADO POR CASOFT
//SEPTIEMBRE DE 2018

include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/misc.lib.php";

$db = Conectar();

function _moduleContent(&$smarty, $module_name) {
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoReportCall.class.php";
    include_once "libs/paloSantoConfig.class.php";

    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    //folder path for custom templates
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/" . $templates_dir . '/' . $arrConf['theme'];

    //conexion resource
    //actions
    $accion = getAction();
    $content = "";

    switch ($accion) {

        default:
            //$content = reportReportCall($smarty, $module_name, $local_templates_dir, $pDB_cdr, $pDB_billing, $arrConf);
            $fechai = getParameter("fechai");
            $fechaf = getParameter("fechaf");
            $content = LeeEncuesta($smarty, $fechai, $fechaf);

            break;
    }
    return $content;
}

function LeeEncuesta($smarty, $fechai, $fechaf) {
    global $arrLang;
    $oGrid = new paloSantoGrid($smarty);
    $arrGrid = array("title" => _tr('ECCP User List'), "url" => $url, "icon" => 'images/user.png', "width" => "99%", "columns" => array(0 => array("name" => 'Nº Accesos'), 1 => array("name" => 'Nombre Menu'), 2 => array("name" => 'Id Menu'), 3 => array("name" => 'P1')));

    if (($fechai == "") || ($fechaf == "")) {
        $fechai = date("Y-m-d");
        $fechaf = date("Y-m-d");
    }


    $msg = "<h3>Reportes IVR</h3><br>";
    $hoy = date("Y-m-d");
    
    //Consulto la tabla cdr para revisar donde termino la llamada
    $consul = "SELECT count(*) as orden, dcontext, dcontext FROM `cdr` where (dcontext like 'ivr-%' or dcontext like '%app-%') and (calldate >= '$fechai 00:00:00' and calldate <= '$fechaf 23:59:59') group by dcontext order by orden desc";
    $mires = mysql_query($consul);
    $data = [];
    $m1 = 0;
    $m2 = 0;
    $m3 = 0;
    $m4 = 0;
    $m5 = 0;
    $m6 = 0;
    $m7 = 0;
    while ($micon = mysql_fetch_row($mires)) {
        $nmenu = BuscaMenu($micon[1]);
//Determino el numero de accesos a las opciones de mi IVR. nmenu es el nombre que le puse a mi menu desde la parte grafica.
//Como se los destinos a las que van  las siguientes opciones, sumo los destinos para al final saber a que opciones ingree        
        if (($nmenu == 'M_D') || ($nmenu == 'OP_E'))
            $m1 = $m1 + $micon[0];
        if (($nmenu == 'M_F') || ($nmenu == 'OP_G') || ($nmenu == 'OP_H'))
            $m2 = $m2 + $micon[0];

        if ($nmenu == 'OP_I')
            $m3 = $m3 + $micon[0];

        if ($nmenu == 'OP_J')
            $m4 = $m4 + $micon[0];

        if ($nmenu == 'OP_K')
            $m5 = $m5 + $micon[0];

        if (($nmenu == 'M_R') || ($nmenu == 'OP_M') || ($nmenu == 'OP_N'))
            $m6 = $m6 + $micon[0];
        if ($nmenu == 'OP_L')
            $m7 = $m7 + $micon[0];

        array_push($data, [
            $micon[0],
            $nmenu,
            $micon[2],
            $micon[3],
            $micon[4],
            $micon[5],
            $micon[6],
            $micon[7],
        ]);
    }



    $contenido .= '<h3>Seleccione la fecha de el reporte (Actual: Desde: ' . $fechai . ' hasta el ' . $fechaf . ')</h3> <form action="index.php?menu=cs_reportesivr" method="get"> <label>Fecha inicial: </label><input type="date" name="fechai"> <label>Fecha final: </label><input type="date" name="fechaf"> <input type="submit" > </form><hr>';
    $contenido .= $oGrid->fetchGrid($arrGrid, $data, $arrLang);
    $contenido .= "Opcion 1: $m1 <br> Opcion 2: $m2 <br> Opcion 3: $m3 <br>Opcion 4: $m4 <br> Opcion 5: $m5 <br>Opcion 6: $m6 <br> Opcion 7: $m7";
    return $contenido;
}

function getAction() {
    if (getParameter("show")) //Get parameter by POST (submit)
        return "show";
    else if (getParameter("action") == "show") //Get parameter by GET (command pattern, links)
        return "show";
    else if (getParameter("action") == "graph") //Get parameter by GET (command pattern, links)
        return "graph";
    else if (getParameter("action") == "imageTop10Entrantes")
        return "imageTop10Entrantes";
    else if (getParameter("action") == "imageTop10Salientes")
        return "imageTop10Salientes";
    else
        return "report";
}

function Conectar() {
    if (!($conexion = mysql_pconnect("localhost", "root", "XXXXX")))
        die("No se pudo conectar a la base de datos. Pueda que exista un problema con el servidor");
    if (!mysql_select_db("asteriskcdrdb"))
        die("La base de datos no existe en el servidor.");
//    mysql_set_charset('iso-8859-1', $conexion);
    mysql_set_charset('utf8');
    return $conexion;
}

function BuscaMenu($idmenu) {
    if (!($conexion = mysql_pconnect("localhost", "root", "XXXXX")))
        die("No se pudo conectar a la base de datos. Pueda que exista un problema con el servidor");
    if (!mysql_select_db("asterisk"))
        die("La base de datos no existe en el servidor.");
//    mysql_set_charset('iso-8859-1', $conexion);
    mysql_set_charset('utf8');


    $porciones = explode("-", $idmenu);

    switch ($porciones[0]) {
        case 'ivr':
            $consul = "select description from ivr_details where id='$porciones[1]'";
            break;

        case 'app':
            $consul = "select description from announcement where announcement_id='$porciones[2]'";
            break;
    }

    $micon = mysql_query($consul);
    $mires = mysql_fetch_row($micon);

    return $mires[0];
}



?>
