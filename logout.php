<?php
/**
 * Sistema de Gestion de Datos HIS
 * Cerrar sesion
 */

require_once 'config.php';

session_start();
session_destroy();

header('Location: index.php');
exit;
