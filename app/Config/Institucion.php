<?php
// app/Config/Institucion.php
// Datos de la institucion que aparecen en el encabezado de los reportes
// Edita el nombre y el lema segun tu casa de estudios

namespace App\Config;

class Institucion
{
    // Nombre que aparecera centrado en el encabezado de los reportes
    public const NOMBRE = 'Universidad Politecnica Territorial de Valencia';

    // Lema o subtitulo del sistema
    public const LEMA = 'Sistema de Control de Estudios';

    // Ruta absoluta del logo institucional (PNG o JPG)
    // Si el archivo no existe, los reportes se generan solo con texto
    public const LOGO = __DIR__ . '/../../public/assets/img/logo.jpg';
}