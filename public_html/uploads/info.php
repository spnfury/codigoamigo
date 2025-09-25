<?php
if (!isset($_SESSION)) {
    session_start();
}
$MM_authorizedUsers = "";
$MM_donotCheckaccess = "true";

// *** Restrict Access To Page: Grant or deny access to this page
function isAuthorized($strUsers, $strGroups, $UserName, $UserGroup)
{
    // For security, start by assuming the visitor is NOT authorized.
    $isValid = False;

    // When a visitor has logged into this site, the Session variable MM_Username_atucasa set equal to their username.
    // Therefore, we know that a user is NOT logged in if that Session variable is blank.
    if (!empty($UserName)) {
        // Besides being logged in, you may restrict access to only certain users based on an ID established when they login.
        // Parse the strings into arrays.
        $arrUsers = Explode(",", $strUsers);
        $arrGroups = Explode(",", $strGroups);
        if (in_array($UserName, $arrUsers)) {
            $isValid = true;
        }
        // Or, you may restrict access to only certain users based on their username.
        if (in_array($UserGroup, $arrGroups)) {
            $isValid = true;
        }
        if (($strUsers == "") && true) {
            $isValid = true;
        }
    }
    return $isValid;
}

$MM_restrictGoTo = "index.php";
if (!((isset($_SESSION['MM_Username_atucasa'])) && (isAuthorized("", $MM_authorizedUsers, $_SESSION['MM_Username_atucasa'], $_SESSION['MM_UserGroup'])))) {
    $MM_qsChar = "?";
    $MM_referrer = $_SERVER['PHP_SELF'];
    if (strpos($MM_restrictGoTo, "?")) $MM_qsChar = "&";
    if (isset($QUERY_STRING) && strlen($QUERY_STRING) > 0)
        $MM_referrer .= "?" . $QUERY_STRING;
    $MM_restrictGoTo = $MM_restrictGoTo . $MM_qsChar . "accesscheck=" . urlencode($MM_referrer);
    header("Location: " . $MM_restrictGoTo);
    exit;
}
?>
<?php extract($_GET); ?>
<?php extract($_POST); ?>
<?php
session_start();
$sessionid = session_id();
error_reporting(5);
$_SESSION['sessionid'] = $sessionid;
$bbbb = $idR;
?>
<?php
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        if (PHP_VERSION < 6) {
            $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
        }

        $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}

$currentPage = $_SERVER["PHP_SELF"];
?>
<?php
mysql_select_db($database_conex, $conex);
$query_identClient = "SELECT * FROM conf_datos_cliente WHERE idR='$idR'";
$identClient = mysql_query($query_identClient, $conex) or die(mysql_error());
$row_identClient = mysql_fetch_assoc($identClient);
$totalRows_identClient = mysql_num_rows($identClient);

$de = $row_identClient['correo1'];
$nombre = $row_identClient['nombre'];
$direccion = $row_identClient['direccionFiscal'];
$telefono = $row_identClient['telefono1'];
$url = $row_identClient['url'];
$logo = $url . '/images/' . $row_identClient['logo'];
$nom = $row_identClient['nomenclatura'];
$porc_seg = $row_identClient['porc_seg'];
$verificacion = $row_identClient['verificacion'];
$aduana = $row_identClient['aduana'];
?>
<?php
$pagina = "home.php";

mysql_select_db($database_conex, $conex);
$query_dtaSist = "SELECT * FROM conf_datos_cliente WHERE idR='$idR'";
$dtaSist = mysql_query($query_dtaSist, $conex) or die(mysql_error());
$row_dtaSist = mysql_fetch_assoc($dtaSist);
$totalRows_dtaSist = mysql_num_rows($dtaSist);

mysql_select_db($database_conex, $conex);
$query_sessionUsuario = "SELECT * FROM seguridad_quienes WHERE sessionid = '$sessionid' ORDER BY idSession DESC";
$sessionUsuario = mysql_query($query_sessionUsuario, $conex) or die(mysql_error());
$row_sessionUsuario = mysql_fetch_assoc($sessionUsuario);
$totalRows_sessionUsuario = mysql_num_rows($sessionUsuario);
$loginUsuario2000 = $row_sessionUsuario['login'];

if (($totalRows_sessionUsuario == '') or ($totalRows_sessionUsuario == '0')) {
    header("Location: index.php");
}

mysql_select_db($database_conex, $conex);
$query_dataUsuario = "SELECT * FROM usuarios WHERE usuario = '$loginUsuario2000'";
$dataUsuario = mysql_query($query_dataUsuario, $conex) or die(mysql_error());
$row_dataUsuario = mysql_fetch_assoc($dataUsuario);
$totalRows_dataUsuario = mysql_num_rows($dataUsuario);
$idUsuarioXX = $row_dataUsuario['id'];

mysql_select_db($database_conex, $conex);
$query_paginaID = "SELECT * FROM seguridad_paginas WHERE pagina = '$pagina'";
$paginaID = mysql_query($query_paginaID, $conex) or die(mysql_error());
$row_paginaID = mysql_fetch_assoc($paginaID);
$totalRows_paginaID = mysql_num_rows($paginaID);
$idPagina = $row_paginaID['idPagina'];

mysql_select_db($database_conex, $conex);
$query_permisoUser = "SELECT * FROM seguridad_permisos WHERE idPagina = '$idPagina' and idUsuario='$idUsuarioXX'";
$permisoUser = mysql_query($query_permisoUser, $conex) or die(mysql_error());
$row_permisoUser = mysql_fetch_assoc($permisoUser);
$totalRows_permisoUser = mysql_num_rows($permisoUser);

if ($row_permisoUser['permiso'] == 'YES') {
    $permisoOtorgado = 1;
} else {
    $permisoOtorgado = 0;
    //header("Location: jc2_hija.php");
}

mysql_select_db($database_conex, $conex);
$query_dta = "SELECT * FROM wh WHERE creado = '$loginUsuario2000' AND m2='0' AND status='RECIBIDO EN WAREHOUSE' ORDER BY id DESC LIMIT 1";
$dta = mysql_query($query_dta, $conex) or die(mysql_error());
$row_dta = mysql_fetch_assoc($dta);
$totalRows_dta = mysql_num_rows($dta);

mysql_select_db($database_conex, $conex);
$query_facturas2 = "SELECT * FROM ml_facturas WHERE pagado='0' ORDER BY id DESC";
$facturas2 = mysql_query($query_facturas2, $conex) or die(mysql_error());
$row_facturas2 = mysql_fetch_assoc($facturas2);
$totalRows_facturas2 = mysql_num_rows($facturas2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title> Sistema ML - Software para Courier </title>
    <meta name="description" content="Software Courier 100% Web y a un precio realmente bajo"/>
    <meta name="keywords" content="software courier, sistema courier, courier, Software para Courier, Courier

Sistems, ML Courier, Courier Miami, Puerta a Puerta, Envio de Paqueteria"/>
    <meta name="copyright" content="Copyright Sistema ML - 2015"/>
    <meta name="author" content="Joan Bouquet / Sistema ML"/>
    <meta name="email" content="info@sistemaml.com"/>
    <meta name="Charset" content="UTF-8"/>
    <meta name="Distribution" content="Global"/>
    <meta name="Rating" content="General"/>
    <meta name="Robots" content="INDEX,FOLLOW"/>
    <meta name="Revisit-after" content="10 Days"/>
    <meta name="expires" content="never"/>


    <!-- basic styles -->

    <link href="bootstrap/assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="bootstrap/assets/css/font-awesome.min.css"/>

    <!--[if IE 7]>
    <link rel="stylesheet" href="assets/css/font-awesome-ie7.min.css"/>
    <![endif]-->

    <!-- page specific plugin styles -->

    <!-- fonts -->

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,300"/>

    <!-- ace styles -->

    <link rel="stylesheet" href="bootstrap/assets/css/ace.min.css"/>
    <link rel="stylesheet" href="bootstrap/assets/css/ace-rtl.min.css"/>
    <link rel="stylesheet" href="bootstrap/assets/css/ace-skins.min.css"/>

    <!--[if lte IE 8]>
    <link rel="stylesheet" href="assets/css/ace-ie.min.css"/>
    <![endif]-->

    <!-- inline styles related to this page -->

    <!-- ace settings handler -->

    <script src="bootstrap/assets/js/ace-extra.min.js"></script>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->

    <!--[if lt IE 9]>
    <script src="assets/js/html5shiv.js"></script>
    <script src="assets/js/respond.min.js"></script>
    <![endif]-->
</head>

<body>
<div class="navbar navbar-default" id="navbar">
    <script type="text/javascript">
        try {
            ace.settings.check('navbar', 'fixed')
        } catch (e) {
        }
    </script>

    <div class="navbar-container" id="navbar-container">
        <div class="navbar-header pull-left">
            <a href="#" class="navbar-brand">
                <small>
                    <i class="icon-barcode"></i> - ML Courier </small>
                <small>
                    <small>
                        &nbsp;&nbsp;&nbsp; ver. ML4
                    </small>
                </small>

            </a><!-- /.brand -->
        </div><!-- /.navbar-header -->

        <div class="navbar-header pull-right" role="navigation">
            <ul class="nav ace-nav">

                <?php if ($totalRows_facturas2 >= '1') { ?>
                    <li class="purple">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            <i class="icon-bell-alt icon-animated-bell"></i>
                            <span class="badge badge-important"><?php echo $totalRows_facturas2; ?></span>
                        </a>

                        <ul class="pull-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
                            <li class="dropdown-header">
                                <i class="icon-warning-sign"></i>
                                Notifications
                            </li>

                            <li>
                                <a href="jc2_ml_pagos.php" target="_blank">
                                    <div class="clearfix">
											<span class="pull-left">
												<i class="btn btn-xs no-hover btn-pink icon-legal "></i>
												Factura pendiente (<?php echo $totalRows_facturas2; ?>)
											</span>

                                    </div>
                                </a>
                            </li>

                        </ul>
                    </li>
                <?php } ?>

                <li class="light-blue">
                    <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                        <?php if ($row_dtaSist['logo'] != '') { ?>
                            <img class="nav-user-photo" src="images/<?php echo $row_dtaSist['logo']; ?>"/>
                        <?php } ?>
                        <span class="user-info">
									<small>Welcome,</small>
									<?php echo $row_dataUsuario['usuario']; ?>
								</span>

                        <i class="icon-caret-down"></i>
                    </a>

                    <ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">


                        <li>
                            <a href="#">
                                <i class="icon-user"></i>
                                Profile
                            </a>
                        </li>

                        <li class="divider"></li>

                        <li>
                            <a href="jc2_logout.php">
                                <i class="icon-off"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul><!-- /.ace-nav -->
        </div><!-- /.navbar-header -->
    </div><!-- /.container -->
</div>

<div class="main-container" id="main-container">
    <script type="text/javascript">
        try {
            ace.settings.check('main-container', 'fixed')
        } catch (e) {
        }
    </script>

    <div class="main-container-inner">
        <a class="menu-toggler" id="menu-toggler" href="#">
            <span class="menu-text"></span>
        </a>

        <div class="sidebar" id="sidebar">
            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'fixed')
                } catch (e) {
                }
            </script>

            <div class="sidebar-shortcuts" id="sidebar-shortcuts">
                <div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">


                    <a href="https://mlcourier.com/clientes/" target="_blank" class="btn btn-success" title="Pagos">
                        <i class="icon-credit-card"></i>
                    </a>

                    <a href="https://sistemaml.com" target="_blank" class="btn btn-info" title="Soporte">
                        <i class="icon-pencil"></i>
                    </a>
                    <a href="jc2_conf_seguridad.php" target="_blank" class="btn btn-warning" title="Seguridad">
                        <i class="icon-group"></i>
                    </a>
                    <a href="jc2_configuration.php" target="_blank" class="btn btn-danger" title="Configuracion">
                        <i class="icon-cogs "></i>
                    </a>


                </div>

                <div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
                    <span class="btn btn-success"></span>

                    <span class="btn btn-info"></span>

                    <span class="btn btn-warning"></span>

                    <span class="btn btn-danger"></span>
                </div>
            </div><!-- #sidebar-shortcuts -->

            <ul class="nav nav-list">

                <?php if ($row_dtaSist['agenteCompras'] == '1') { ?>
                    <li>
                        <a href="#" class="dropdown-toggle">
                            <i class="icon-edit"></i>
                            <span class="menu-text">Agente de Compras</span>

                            <b class="arrow icon-angle-down"></b>
                        </a>

                        <ul class="submenu">

                            <li>
                                <a href="jc_cotizaciones.php">
                                    <i class="icon-double-angle-right"></i>
                                    Lista de Cotizaciones
                                </a>
                            </li>

                            <li>
                                <a href="jc_novedades.php">
                                    <i class="icon-double-angle-right"></i>
                                    Tickets en Cotizaciones
                                </a>
                            </li>

                            <li>
                                <a href="jc_compras.php">
                                    <i class="icon-double-angle-right"></i>
                                    Lista de Compras
                                </a>
                            </li>

                        </ul>
                    </li>
                <?php } ?>

                <li>
                    <a href="#" class="dropdown-toggle">
                        <i class="icon-list"></i>
                        <span class="menu-text"> Warehouse </span>

                        <b class="arrow icon-angle-down"></b>
                    </a>

                    <ul class="submenu">
                        <?php if ($idR == '0') { ?>
                            <li>
                                <a href="jc_reception.php">
                                    <i class="icon-double-angle-right"></i>
                                    Reception
                                </a>
                            </li>

                        <?php } ?>
                        <?php if ($idR == '0') { ?>
                            <li>
                                <a href="jc_new_wh.php">
                                    <i class="icon-double-angle-right"></i>
                                    Add WR
                                </a>
                            </li>
                        <?php } ?>

                        <li>
                            <a href="jc_wh.php">
                                <i class="icon-double-angle-right"></i>
                                WR List
                            </a>
                        </li>
                        <li>
                            <a href="jc_consolidation.php">
                                <i class="icon-double-angle-right"></i>
                                Consolidation
                            </a>
                        </li>

                        <li>
                            <a href="jc_masterGuia.php">
                                <i class="icon-double-angle-right"></i>
                                MG
                            </a>
                        </li>
                        <li>
                            <a href="jc_reportes.php">
                                <i class="icon-double-angle-right"></i>
                                Reportes
                            </a>
                        </li>


                        <li>


                            <ul class="submenu">
                                <li>

                                </li>

                                <li>


                                    <ul class="submenu">


                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="dropdown-toggle">
                        <i class="icon-group "></i>
                        <span class="menu-text"> Customer Service </span>

                        <b class="arrow icon-angle-down"></b>
                    </a>

                    <ul class="submenu">
                        <?php if ($row_dtaSist['verificacion'] == '1') { ?>
                            <li>
                                <a href="jc_verification.php">
                                    <i class="icon-double-angle-right"></i>
                                    Package verification
                                </a>
                            </li>
                        <?php } ?>

                        <?php if ($row_dtaSist['novedades'] == '1') { ?>
                            <li>
                                <a href="jc_novedades.php">
                                    <i class="icon-double-angle-right"></i>
                                    Tickets
                                </a>
                            </li>
                        <?php } ?>
                        <?php if ($row_dtaSist['mailing'] == '1') { ?>
                            <li>
                                <a href="jc_mailing.php">
                                    <i class="icon-double-angle-right"></i>
                                    Mailing
                                </a>
                            </li>
                        <?php } ?>

                        <li>
                            <a href="jc_status.php">
                                <i class="icon-double-angle-right"></i>
                                Package status
                            </a>
                        </li>

                        <li>
                            <a href="jc2_DT_Reporte_SHAprobados.php" target="_blank">
                                <i class="icon-double-angle-right"></i>
                                SH APROBADOS (REPORTE)
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="#" class="dropdown-toggle">
                        <i class="icon-desktop "></i>
                        <span class="menu-text"> Administration </span>

                        <b class="arrow icon-angle-down"></b>
                    </a>

                    <ul class="submenu">
                        <li>
                            <a href="jc2_excel_consignee.php" target="_blank">
                                <i class="icon-double-angle-right"></i>
                                Customer excel list
                            </a>
                        </li>
                        <li>
                            <a href="jc_clientes_activos.php">
                                <i class="icon-double-angle-right"></i>
                                Clientes Activos
                            </a>
                        </li>
                        <!--<li>
                            <a href="jc2_excel_consignee3.php" target="_blank" >
                                <i class="icon-double-angle-right"></i>
                                Clientes Inactivos
                            </a>
                        </li>-->


                        <?php if ($idR == '0') { ?>
                            <li>
                                <a href="jc_mgprofit.php">
                                    <i class="icon-double-angle-right"></i>
                                    MG Profit
                                </a>
                            </li>
                        <?php } ?>

                        <li>
                            <a href="jc_payment_report.php">
                                <i class="icon-double-angle-right"></i>
                                Payment Report
                            </a>
                        </li>

                        <li>
                            <a href="jc_invoice.php">
                                <i class="icon-double-angle-right"></i>
                                Invoice
                            </a>
                        </li>
                        <?php if ($idR == '0') { ?>
                            <li>
                                <a href="jc_invoiceRD.php">
                                    <i class="icon-double-angle-right"></i>
                                    Invoice Local
                                </a>
                            </li>
                        <?php } ?>
                        <?php if ($idR == '0') { ?>
                            <?php if ($aduana == '1') { ?>
                                <li>
                                    <a href="jc_aduana.php">
                                        <i class="icon-double-angle-right"></i>
                                        Aduana
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } ?>
                        <li>
                            <a href="jc2_account_receivable.php" target="_blank">
                                <i class="icon-double-angle-right"></i>
                                Account receivable
                            </a>
                        </li>
                        <?php if ($idR == '0') { ?>
                            <?php if ($row_identClient['gastos'] == '1') { ?>
                                <li>
                                    <a href="jc_daily.php">
                                        <i class="icon-double-angle-right"></i>
                                        Revenue and expenses
                                    </a>
                                </li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </li>

                <li>
                    <a href="jc_consignee.php">
                        <i class="icon-eye-open "></i>
                        <span class="menu-text"> Consignee </span>
                    </a>
                </li>

                <?php if ($idR == '0') { ?>
                    <li>
                        <a href="jc_shipper.php">
                            <i class="icon-calendar"></i>
                            <span class="menu-text"> Shipper </span>
                        </a>
                    </li>
                <?php } ?>

                <?php if ($idR == '0') { ?>
                    <li>
                        <a href="jc_carrier.php">
                            <i class="icon-fighter-jet"></i>
                            <span class="menu-text"> Carrier </span>
                        </a>
                    </li>
                <?php } ?>

            </ul><!-- /.nav-list -->

            <div class="sidebar-collapse" id="sidebar-collapse">
                <i class="icon-double-angle-left" data-icon1="icon-double-angle-left" data-icon2="icon-double-angle-right"></i>
            </div>

            <script type="text/javascript">
                try {
                    ace.settings.check('sidebar', 'collapsed')
                } catch (e) {
                }
            </script>
        </div>

        <div class="main-content">
            <div class="breadcrumbs" id="breadcrumbs">
                <script type="text/javascript">
                    try {
                        ace.settings.check('breadcrumbs', 'fixed')
                    } catch (e) {
                    }
                </script>

                <ul class="breadcrumb">
                    <li>
                        <a href="jc2_addWH0.php" target="_blank" class="btn btn-primary"><i class="icon-plus"></i> New WR</a>
                    </li>
                </ul>
                <?php if ($row_dataUsuario['wr_new_wr'] == '1') { ?>
                    <?php if (($totalRows_dta == '0') or ($totalRows_dta == '')) { ?>
                    <?php } else { ?>
                        <h5>
                            <?php
                            $vari = $row_dta['id'];
                            if ($vari <= 9) {
                                $vari2 = "WR00000" . $vari;
                            } elseif (($vari >= 10) AND ($vari <= 99)) {
                                $vari2 = "WR0000" . $vari;
                            } elseif (($vari >= 100) AND ($vari <= 999)) {
                                $vari2 = "WR000" . $vari;
                            } elseif (($vari >= 1000) AND ($vari <= 9999)) {
                                $vari2 = "WR00" . $vari;
                            } elseif (($vari >= 10000) AND ($vari <= 99999)) {
                                $vari2 = "WR0" . $vari;
                            } elseif (($vari >= 100000) AND ($vari <= 999999)) {
                                $vari2 = "WR" . $vari;
                            } elseif (($vari >= 1000000) AND ($vari <= 9999999)) {
                                $vari2 = "WR" . $vari;
                            }
                            //echo ' &nbsp;&nbsp;'. $vari2;
                            ?>
                        </h5>
                    <?php } ?>
                <?php } ?>


                <div class="nav-search" id="nav-search">
                    <table width="100%" border="0">
                        <tr>
                            <td>
                                <form class="form-search" method="get" action="jc_buscar.php">
                              <span class="input-icon"><i class="icon-search nav-search-icon"></i>
								<input type="text" name="numero" value="<?php echo $numero; ?>" class="nav-search-input" id="numero" autocomplete="off"/>
                                <select name="tipo">
                                  <option value="WR">WR</option>
                                  <option value="SH">SH</option>
                                  <option value="TRACKING">TRACKING</option>
                                </select>
                                </span>
                                </form>
                            </td>
                        </tr>
                    </table>
                </div><!-- #nav-search -->


            </div>

            <div class="page-content"><!-- /.row -->
                <div class="row">
                    <div class="space-6"></div>

                    <?php

                    if (isset($_POST) and !empty($_POST)) {?>
                        <div class="col-sm-4 offset-4">
                            <div class="alert alert-success">Archivo CSV cargado con exito!.</div>
                            <hr>

                            <?php if ($_POST['vars']) {
                                $vars = json_decode($_POST['vars']);
                                ?>
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">RESUMEN</th>
                                    </tr>
                                    </thead>
                                    <tr>
                                        <th>Procesados:</th>
                                        <td><?php echo $vars->procesados->cantidad ?></td>
                                    </tr>
                                    <tr>
                                        <th>Existentes:</th>
                                        <td><?php echo $vars->existes->cantidad ?></td>
                                    </tr>
                                    <tr>
                                        <th>Con Errores:</th>
                                        <td><?php echo $vars->conerrores->cantidad ?></td>
                                    </tr>
                                    <?php if($vars->conerrores->cantidad>0){ ?>
                                        <tr>
                                            <td colspan="2">
                                                <table class="table">
                                                    <tr>
                                                        <td colspan="2">
                                                            <b class="text-danger">Observaciones:</b>
                                                        </td>
                                                    </tr>
                                                    <?php foreach ($vars->conerrores->wrs AS $wr){ ?>
                                                        <tr><td><?php echo $wr->cod ?></td><td><?php echo $wr->observacion ?></td></tr>
                                                    <?php } ?>
                                                </table>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </table>

                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if (isset($_GET[md5('problema')]) and $_GET[md5('problema')] != '') { ?>
                        <div class="alert alert-danger">
                            <?php echo $_GET[md5('problema')]; ?>
                        </div>
                    <?php } ?>

                    <div id="divMasivo">
                        <form action="mvc/controller/c_masivo.php" method="post" enctype="multipart/form-data" name="formMasivo" id="formMasivo">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="">Archivo plano para crear los WR:</label>
                                        <input type="file" class="form-control" name="fileMasivo" id="fileMasivo" accept=".csv" required>
                                    </div>

                                    <input type="hidden" name="origen" value="<?= md5('masivo') ?>">

                                    <button class="btn btn-primary btn-sm">Procesar Archivo y Crear WR</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if (($totalRows_dta >= '1') and ($gato == '1')) { ?>
                    <div class="widget-body">
                        <div class="widget-main">
                            <div id="fuelux-wizard" class="row-fluid" data-target="#step-container">
                                <ul class="wizard-steps">
                                    <li data-target="#step1" class="active">
                                        <span class="step">1</span>
                                        <span class="title">Tracking / Consignee</span>
                                    </li>

                                    <li data-target="#step2">
                                        <span class="step">2</span>
                                        <span class="title">Shipper / Carrier</span>
                                    </li>

                                    <li data-target="#step3">
                                        <span class="step">3</span>
                                        <span class="title">Peso, medidas, descripcion</span>
                                    </li>

                                    <li data-target="#step4">
                                        <span class="step">4</span>
                                        <span class="title">Variables <br> (Prohibido, FOB, etc)</span>
                                    </li>

                                </ul>
                            </div>

                            <hr/>
                            <div class="step-content row-fluid position-relative" id="step-container">

                                <div class="step-pane active" id="step1">
                                    <iframe src="jc2_addWH1_tracking.php" height="200" width="100%" frameborder="0"></iframe>
                                </div>

                                <div class="step-pane" id="step2">
                                    <iframe src="jc2_addWH1_shipper_carrier.php" height="200" width="100%" frameborder="0"></iframe>
                                </div>

                                <div class="step-pane" id="step3">
                                    <iframe src="jc2_addWH1_sub.php" height="250" width="100%" frameborder="0"></iframe>
                                </div>

                                <div class="step-pane" id="step4">
                                    <iframe src="jc2_addWH1_variables.php" height="200" width="100%" frameborder="0"></iframe>
                                </div>

                            </div>


                            <hr/>
                            <div class="row-fluid wizard-actions">
                                <button class="btn btn-prev">
                                    <i class="icon-arrow-left"></i>
                                    Prev
                                </button>

                                <button class="btn btn-success btn-next" data-last="Finish ">
                                    Next
                                    <i class="icon-arrow-right icon-on-right"></i>
                                </button>
                            </div>
                        </div><!-- /widget-main -->
                    </div><!-- /widget-body -->
                </div>
            </div>
        </div>
        <?php } ?>


        <!-- /.page-content -->
    </div><!-- /.main-content -->

    <div class="ace-settings-container" id="ace-settings-container">
        <div class="btn btn-app btn-xs btn-warning ace-settings-btn" id="ace-settings-btn">
            <i class="icon-cog bigger-150"></i>
        </div>

        <div class="ace-settings-box" id="ace-settings-box">
            <div>
                <div class="pull-left">
                    <select id="skin-colorpicker" class="hide">
                        <option data-skin="default" value="#438EB9">#438EB9</option>
                        <option data-skin="skin-1" value="#222A2D">#222A2D</option>
                        <option data-skin="skin-2" value="#C6487E">#C6487E</option>
                        <option data-skin="skin-3" value="#D0D0D0">#D0D0D0</option>
                    </select>
                </div>
                <span>&nbsp; Choose Skin</span>
            </div>

            <div>
                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-navbar"/>
                <label class="lbl" for="ace-settings-navbar"> Fixed Navbar</label>
            </div>

            <div>
                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-sidebar"/>
                <label class="lbl" for="ace-settings-sidebar"> Fixed Sidebar</label>
            </div>

            <div>
                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-breadcrumbs"/>
                <label class="lbl" for="ace-settings-breadcrumbs"> Fixed Breadcrumbs</label>
            </div>

            <div>
                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-rtl"/>
                <label class="lbl" for="ace-settings-rtl"> Right To Left (rtl)</label>
            </div>

            <div>
                <input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-add-container"/>
                <label class="lbl" for="ace-settings-add-container">
                    Inside
                    <b>.container</b>
                </label>
            </div>
        </div>
    </div><!-- /#ace-settings-container -->
</div><!-- /.main-container-inner -->

<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
    <i class="icon-double-angle-up icon-only bigger-110"></i>
</a>
</div><!-- /.main-container -->


<!-- basic scripts -->
<!--[if !IE]> -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<!-- <![endif]-->
<!--[if IE]>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<![endif]-->

<!--[if !IE]> -->

<script type="text/javascript">
    window.jQuery || document.write("<script src='assets/js/jquery-2.0.3.min.js'>" + "<" + "/script>");
</script>

<!-- <![endif]-->

<!--[if IE]>
<script type="text/javascript">
    window.jQuery || document.write("<script src='assets/js/jquery-1.10.2.min.js'>" + "<" + "/script>");
</script>
<![endif]-->

<script type="text/javascript">
    if ("ontouchend" in document) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
</script>
<script src="bootstrap/assets/js/bootstrap.min.js"></script>
<script src="bootstrap/assets/js/typeahead-bs2.min.js"></script>

<!-- page specific plugin scripts -->

<script src="bootstrap/assets/js/fuelux/fuelux.wizard.min.js"></script>
<script src="bootstrap/assets/js/jquery.validate.min.js"></script>
<script src="bootstrap/assets/js/additional-methods.min.js"></script>
<script src="bootstrap/assets/js/bootbox.min.js"></script>
<script src="bootstrap/assets/js/jquery.maskedinput.min.js"></script>
<script src="bootstrap/assets/js/select2.min.js"></script>

<!-- ace scripts -->

<script src="bootstrap/assets/js/ace-elements.min.js"></script>
<script src="bootstrap/assets/js/ace.min.js"></script>

<!-- inline scripts related to this page -->

<script type="text/javascript">
    jQuery(function ($) {

        $('[data-rel=tooltip]').tooltip();

        $(".select2").css('width', '200px').select2({allowClear: true})
            .on('change', function () {
                $(this).closest('form').validate().element($(this));
            });


        var $validation = false;
        $('#fuelux-wizard').ace_wizard().on('change', function (e, info) {
            if (info.step == 1 && $validation) {
                if (!$('#validation-form').valid()) return false;
            }
        }).on('finished', function (e) {
            bootbox.dialog({
                message: "Thank you! Your information was successfully saved!",
                buttons: {
                    "success": {
                        "label": "OK",
                        "className": "btn-sm btn-primary"

                    }

                }

            });
        }).on('stepclick', function (e) {
            //return false;//prevent clicking on steps
        });


        $('#skip-validation').removeAttr('checked').on('click', function () {
            $validation = this.checked;
            if (this.checked) {
                $('#sample-form').hide();
                $('#validation-form').removeClass('hide');
            } else {
                $('#validation-form').addClass('hide');
                $('#sample-form').show();
            }
        });


        //documentation : http://docs.jquery.com/Plugins/Validation/validate


        $.mask.definitions['~'] = '[+-]';
        $('#phone').mask('(999) 999-9999');

        jQuery.validator.addMethod("phone", function (value, element) {
            return this.optional(element) || /^\(\d{3}\) \d{3}\-\d{4}( x\d{1,6})?$/.test(value);
        }, "Enter a valid phone number.");

        $('#validation-form').validate({
            errorElement: 'div',
            errorClass: 'help-block',
            focusInvalid: false,
            rules: {
                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true,
                    minlength: 5
                },
                password2: {
                    required: true,
                    minlength: 5,
                    equalTo: "#password"
                },
                name: {
                    required: true
                },
                phone: {
                    required: true,
                    phone: 'required'
                },
                url: {
                    required: true,
                    url: true
                },
                comment: {
                    required: true
                },
                state: {
                    required: true
                },
                platform: {
                    required: true
                },
                subscription: {
                    required: true
                },
                gender: 'required',
                agree: 'required'
            },

            messages: {
                email: {
                    required: "Please provide a valid email.",
                    email: "Please provide a valid email."
                },
                password: {
                    required: "Please specify a password.",
                    minlength: "Please specify a secure password."
                },
                subscription: "Please choose at least one option",
                gender: "Please choose gender",
                agree: "Please accept our policy"
            },

            invalidHandler: function (event, validator) { //display error alert on form submit
                $('.alert-danger', $('.login-form')).show();
            },

            highlight: function (e) {
                $(e).closest('.form-group').removeClass('has-info').addClass('has-error');
            },

            success: function (e) {
                $(e).closest('.form-group').removeClass('has-error').addClass('has-info');
                $(e).remove();
            },

            errorPlacement: function (error, element) {
                if (element.is(':checkbox') || element.is(':radio')) {
                    var controls = element.closest('div[class*="col-"]');
                    if (controls.find(':checkbox,:radio').length > 1) controls.append(error);
                    else error.insertAfter(element.nextAll('.lbl:eq(0)').eq(0));
                } else if (element.is('.select2')) {
                    error.insertAfter(element.siblings('[class*="select2-container"]:eq(0)'));
                } else if (element.is('.chosen-select')) {
                    error.insertAfter(element.siblings('[class*="chosen-container"]:eq(0)'));
                } else error.insertAfter(element.parent());
            },

            submitHandler: function (form) {
            },
            invalidHandler: function (form) {
            }
        });


        $('#modal-wizard .modal-header').ace_wizard();
        $('#modal-wizard .wizard-actions .btn[data-dismiss=modal]').removeAttr('disabled');
    })
</script>


</body>
</html>
