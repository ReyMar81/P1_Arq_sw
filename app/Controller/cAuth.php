<?php
// ============================================================
//  cAuth.php — Controlador de Autenticación / Identificación
//  Patrón MVC: script (NO es clase), orquesta modelo → vista
// ============================================================

// 1. Cargar modelos que necesita Auth al instanciarse
require_once __DIR__ . '/../Models/mDocente.php';
require_once __DIR__ . '/../Models/mEstudiante.php';

// --- Cerrar sesión ---
if (isset($_GET['accion']) && $_GET['accion'] === 'logout') {
    $auth = new Auth();
    $auth->logout();
    header('Location: ' . BASE_URL . '/');
    exit;
}

// --- Si ya está autenticado, redirigir al área correspondiente ---
if (Auth::estaAutenticado()) {
    $rol      = Auth::obtenerRol();
    $redirect = ($rol === 'docente') ? 'materia' : 'asistencia';
    header('Location: ' . BASE_URL . '/?page=' . $redirect);
    exit;
}

// --- Procesar identificación (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    if ($auth->identificar()) {
        $rol      = Auth::obtenerRol();
        $redirect = ($rol === 'docente') ? 'materia' : 'asistencia';
        header('Location: ' . BASE_URL . '/?page=' . $redirect);
        exit;
    }
    // El error ya fue guardado en $_SESSION['error'] por Auth::identificar()
    header('Location: ' . BASE_URL . '/?page=auth');
    exit;
}

// 2. Cargar vista (GET sin acción especial)
require_once __DIR__ . '/../Views/vAuth.phtml';
