<?php

// app/Views/partials/header.php

// Este partial abre el documento HTML y carga Bootstrap y SweetAlert2.
$pageTitle = $pageTitle ?? 'Iniciar sesion | Control Estudios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Bootstrap 5 desde CDN -->
    <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sweetalert2.min.css" />
    <!-- SweetAlert2 desde CDN -->
    <script src="<?= BASE_URL ?>assets/js/sweetalert2@11.js"></script>
</head>