<?php
// app/Controllers/ReporteController.php
// Controlador del modulo de reportes
// Genera descargas en PDF (con FPDF) y en Excel nativo (.xls sin librerias)
// El profesor solo puede reportear sus secciones, el admin cualquiera

namespace App\Controllers;

use App\Config\Database;
use App\Config\Institucion;
use App\Models\Seccion;
use App\Models\PlanEvaluacion;
use App\Models\Nota;
use App\Models\Estudiante;

// Cargamos la libreria FPDF que vive dentro del proyecto (sin Composer)
require_once __DIR__ . '/../Libraries/fpdf/fpdf.php';

class ReporteController
{
    private Seccion $modelSeccion;
    private PlanEvaluacion $modelPlan;
    private Nota $modelNota;
    private Estudiante $modelEstudiante;

    public function __construct()
    {
        $database = new Database();
        $this->modelSeccion = new Seccion($database->getConnection());
        $this->modelPlan = new PlanEvaluacion($database->getConnection());
        $this->modelNota = new Nota($database->getConnection());
        $this->modelEstudiante = new Estudiante($database->getConnection());
    }

    // Muestra la vista principal del modulo de reportes
    // El selector lista solo las secciones permitidas segun el rol
    public function index(): void
    {
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($rol === 'admin') {
            $secciones = $this->modelSeccion->obtenerTodos();
        } else {
            $secciones = $this->modelSeccion->obtenerPorProfesor($usuarioId);
        }

        // Agrupamos las secciones por periodo para mostrarlas con optgroup
        $seccionesPorPeriodo = [];
        foreach ($secciones as $seccion) {
            $seccionesPorPeriodo[$seccion['periodo_nombre']][] = $seccion;
        }

        require_once __DIR__ . '/../Views/reporte/index.php';
    }

    // Descarga el reporte de notas de una seccion en PDF
    // El ancho de la pagina se calcula segun la cantidad de actividades
    // para que los nombres de las evaluaciones siempre sean legibles
    public function notasPdf(): void
    {
        $seccion = $this->resolverSeccion(intval($_GET['id'] ?? 0));
        $materiaId = intval($seccion['materia_id']);

        $actividades = $this->modelPlan->obtenerPorMateria($materiaId);
        $reporte = $this->modelNota->obtenerReporteNotas($seccion['id'], $materiaId);

        // ============================================================
        // CALCULO DEL ANCHO DE PAGINA Y DE COLUMNAS
        // ============================================================
        $nActividades = count($actividades);

        // Anchos fijos: #, cedula, nombres, apellidos, final y estado
        $anchoNum = 7;
        $anchoCedula = 22;
        $anchoNombres = 36;
        $anchoApellidos = 36;
        $anchoFinal = 14;
        $anchoEstado = 20;
        $anchoFijos = $anchoNum + $anchoCedula + $anchoNombres + $anchoApellidos + $anchoFinal + $anchoEstado;

        // Cada actividad recibe 24 mm para que su nombre sea legible
        $anchoActividad = 24;

        // Ancho total de pagina: columnas fijas + actividades + margenes
        $anchoPagina = $anchoFijos + ($nActividades * $anchoActividad) + 16;

        // Nunca menos que una Letter horizontal (279 mm)
        if ($anchoPagina < 279) {
            $anchoPagina = 279;
            // Si sobra espacio, las actividades se reparten el excedente
            if ($nActividades > 0) {
                $anchoActividad = (279 - 16 - $anchoFijos) / $nActividades;
            }
        }

        // Documento con ancho dinamico y alto de Letter horizontal
        $pdf = new \FPDF('L', 'mm', [$anchoPagina, 216]);
        $pdf->SetMargins(8, 8, 8);
        $pdf->AddPage();
        $pdf->SetTitle('Reporte de notas');

        // Logo institucional si el archivo existe
        if (is_file(Institucion::LOGO)) {
            $pdf->Image(Institucion::LOGO, 12, 8, 24);
        }

        // Encabezado con nombre de la institucion y titulo del reporte
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->Cell(0, 10, $this->t(Institucion::NOMBRE), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, $this->t('REPORTE DE NOTAS'), 0, 1, 'C');
        $pdf->Ln(4);

        // Datos de la seccion en dos lineas
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(90, 6, $this->t('Materia: ' . $seccion['materia_codigo'] . ' - ' . $seccion['materia_nombre']), 0, 0);
        $pdf->Cell(60, 6, $this->t('Seccion: ' . $seccion['codigo_seccion']), 0, 0);
        $pdf->Cell(60, 6, $this->t('Periodo: ' . $seccion['periodo_nombre']), 0, 1);
        $pdf->Cell(90, 6, $this->t('Profesor: ' . $seccion['profesor_nombre']), 0, 0);
        $pdf->Cell(60, 6, $this->t('Aula: ' . ($seccion['aula'] ?? 'Sin asignar')), 0, 0);
        $pdf->Cell(60, 6, $this->t('Generado: ' . date('d/m/Y H:i')), 0, 1);
        $pdf->Ln(3);

        // ============================================================
        // ENCABEZADO DE LA TABLA
        // ============================================================
        $altoCabecera = 12;  // Alto total del encabezado
        $altoLinea = 6;      // Alto de cada linea del nombre de actividad

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(33, 37, 41);
        $pdf->SetTextColor(255);
        $pdf->Cell($anchoNum, $altoCabecera, '#', 1, 0, 'C', true);
        $pdf->Cell($anchoCedula, $altoCabecera, 'CEDULA', 1, 0, 'C', true);
        $pdf->Cell($anchoNombres, $altoCabecera, 'NOMBRES', 1, 0, 'C', true);
        $pdf->Cell($anchoApellidos, $altoCabecera, 'APELLIDOS', 1, 0, 'C', true);

        // Encabezados de actividades en dos lineas para que sean legibles
        // Se dibujan dos celdas apiladas controlando la posicion X e Y
        foreach ($actividades as $actividad) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $lineas = $this->dividirEnDosLineas($pdf, $actividad['nombre_actividad'], $anchoActividad);

            // Primera linea del nombre
            $pdf->SetXY($x, $y);
            $pdf->Cell($anchoActividad, $altoLinea, $lineas[0], 1, 0, 'C', true);

            // Segunda linea del nombre
            $pdf->SetXY($x, $y + $altoLinea);
            $pdf->Cell($anchoActividad, $altoLinea, $lineas[1], 1, 0, 'C', true);

            // Regresamos el cursor al inicio de la siguiente columna
            $pdf->SetXY($x + $anchoActividad, $y);
        }

        $pdf->Cell($anchoFinal, $altoCabecera, 'FINAL', 1, 0, 'C', true);
        $pdf->Cell($anchoEstado, $altoCabecera, 'ESTADO', 1, 1, 'C', true);

        // ============================================================
        // FILAS DE ESTUDIANTES
        // ============================================================
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 8);
        $i = 1;
        foreach ($reporte as $fila) {
            $pdf->Cell($anchoNum, 8, $i, 1, 0, 'C');
            $pdf->Cell($anchoCedula, 8, $this->t($fila['cedula']), 1, 0, 'C');
            $pdf->Cell($anchoNombres, 8, $this->t($fila['nombres']), 1, 0);
            $pdf->Cell($anchoApellidos, 8, $this->t($fila['apellidos']), 1, 0);
            foreach ($actividades as $actividad) {
                $valor = $fila['notas'][$actividad['id']] ?? null;
                $texto = $valor === null ? '-' : number_format(floatval($valor), 2);
                $pdf->Cell($anchoActividad, 8, $texto, 1, 0, 'C');
            }
            $textoFinal = $fila['nota_final'] === null ? '-' : number_format($fila['nota_final'], 2);
            $pdf->Cell($anchoFinal, 8, $textoFinal, 1, 0, 'C');
            $pdf->Cell($anchoEstado, 8, $this->t($this->textoEstado($fila['estado'])), 1, 1, 'C');
            $i++;
        }

        // Linea de resumen con conteo de estados
        $conteo = $this->contarEstados($reporte);
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 6, $this->t(
            'Total estudiantes: ' . count($reporte) .
            '  |  Aprobados: ' . $conteo['aprobado'] .
            '  |  Reprobados: ' . $conteo['reprobado'] .
            '  |  En curso: ' . $conteo['en_curso'] .
            '  |  Sin notas: ' . $conteo['sin_notas']
        ), 0, 1);

        // Descarga del archivo con nombre descriptivo
        $nombreArchivo = 'notas_' . $this->sanearNombre(
            $seccion['materia_codigo'] . '_Sec' . $seccion['codigo_seccion'] . '_' . $seccion['periodo_nombre']
        ) . '.pdf';
        $pdf->Output('D', $nombreArchivo);
        exit;
    }

    // Descarga el reporte de notas de una seccion en Excel nativo
    public function notasExcel(): void
    {
        $seccion = $this->resolverSeccion(intval($_GET['id'] ?? 0));
        $materiaId = intval($seccion['materia_id']);

        $actividades = $this->modelPlan->obtenerPorMateria($materiaId);
        $reporte = $this->modelNota->obtenerReporteNotas($seccion['id'], $materiaId);

        $totalColumnas = 4 + count($actividades) + 2;

        // Construimos el documento Excel como tabla HTML
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';

        // Encabezado institucional
        $html .= '<tr><th colspan="' . $totalColumnas . '" style="font-size:16px;">' . htmlspecialchars(Institucion::NOMBRE) . '</th></tr>';
        $html .= '<tr><th colspan="' . $totalColumnas . '" style="font-size:13px;">REPORTE DE NOTAS</th></tr>';
        $html .= '<tr><td colspan="' . $totalColumnas . '">' . htmlspecialchars(Institucion::LEMA) . '</td></tr>';
        $html .= '<tr><td colspan="2">Materia: ' . htmlspecialchars($seccion['materia_codigo'] . ' - ' . $seccion['materia_nombre']) . '</td>'
               . '<td colspan="2">Seccion: ' . htmlspecialchars($seccion['codigo_seccion']) . '</td>'
               . '<td colspan="2">Periodo: ' . htmlspecialchars($seccion['periodo_nombre']) . '</td></tr>';
        $html .= '<tr><td colspan="2">Profesor: ' . htmlspecialchars($seccion['profesor_nombre']) . '</td>'
               . '<td colspan="2">Aula: ' . htmlspecialchars($seccion['aula'] ?? 'Sin asignar') . '</td>'
               . '<td colspan="2">Generado: ' . date('d/m/Y H:i') . '</td></tr>';
        $html .= '<tr></tr>';

        // Encabezado de la tabla de notas
        $html .= '<tr><th>#</th><th>Cedula</th><th>Nombres</th><th>Apellidos</th>';
        foreach ($actividades as $actividad) {
            $html .= '<th>' . htmlspecialchars($actividad['nombre_actividad']) . '</th>';
        }
        $html .= '<th>Nota Final</th><th>Estado</th></tr>';

        // Filas de estudiantes
        $i = 1;
        foreach ($reporte as $fila) {
            $html .= '<tr><td>' . $i . '</td>'
                   . '<td>' . htmlspecialchars($fila['cedula']) . '</td>'
                   . '<td>' . htmlspecialchars($fila['nombres']) . '</td>'
                   . '<td>' . htmlspecialchars($fila['apellidos']) . '</td>';
            foreach ($actividades as $actividad) {
                $valor = $fila['notas'][$actividad['id']] ?? null;
                $html .= '<td>' . ($valor === null ? '-' : number_format(floatval($valor), 2)) . '</td>';
            }
            $textoFinal = $fila['nota_final'] === null ? '-' : number_format($fila['nota_final'], 2);
            $html .= '<td>' . $textoFinal . '</td>'
                   . '<td>' . $this->textoEstado($fila['estado']) . '</td></tr>';
            $i++;
        }

        $html .= '</table></body></html>';

        $nombreArchivo = 'notas_' . $this->sanearNombre(
            $seccion['materia_codigo'] . '_Sec' . $seccion['codigo_seccion'] . '_' . $seccion['periodo_nombre']
        );
        $this->enviarExcel($nombreArchivo, $html);
    }

    // Descarga el listado de estudiantes inscritos en una seccion en Excel
    public function inscritosExcel(): void
    {
        $seccion = $this->resolverSeccion(intval($_GET['id'] ?? 0));

        $inscritos = $this->modelEstudiante->obtenerInscritosPorSeccion($seccion['id']);

        // Construimos el documento Excel como tabla HTML
        $html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';

        // Encabezado institucional
        $html .= '<tr><th colspan="9" style="font-size:16px;">' . htmlspecialchars(Institucion::NOMBRE) . '</th></tr>';
        $html .= '<tr><th colspan="9" style="font-size:13px;">ESTUDIANTES INSCRITOS EN LA SECCION</th></tr>';
        $html .= '<tr><td colspan="3">Materia: ' . htmlspecialchars($seccion['materia_codigo'] . ' - ' . $seccion['materia_nombre']) . '</td>'
               . '<td colspan="3">Seccion: ' . htmlspecialchars($seccion['codigo_seccion']) . '</td>'
               . '<td colspan="3">Periodo: ' . htmlspecialchars($seccion['periodo_nombre']) . '</td></tr>';
        $html .= '<tr><td colspan="3">Profesor: ' . htmlspecialchars($seccion['profesor_nombre']) . '</td>'
               . '<td colspan="3">Aula: ' . htmlspecialchars($seccion['aula'] ?? 'Sin asignar') . '</td>'
               . '<td colspan="3">Generado: ' . date('d/m/Y H:i') . '</td></tr>';
        $html .= '<tr></tr>';

        // Encabezado de columnas
        $html .= '<tr><th>#</th><th>Cedula</th><th>Nombres</th><th>Apellidos</th><th>Edad</th>'
               . '<th>Genero</th><th>Carrera</th><th>Correo</th><th>Telefono</th></tr>';

        // Filas de inscritos
        $i = 1;
        foreach ($inscritos as $estudiante) {
            $html .= '<tr><td>' . $i . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['cedula']) . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['nombres']) . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['apellidos']) . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['edad'] ?? '') . '</td>'
                   . '<td>' . $this->textoGenero($estudiante['genero']) . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['carrera'] ?? 'Sin asignar') . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['correo'] ?? '') . '</td>'
                   . '<td>' . htmlspecialchars($estudiante['telefono'] ?? '') . '</td></tr>';
            $i++;
        }

        $html .= '</table></body></html>';

        $nombreArchivo = 'inscritos_' . $this->sanearNombre(
            $seccion['materia_codigo'] . '_Sec' . $seccion['codigo_seccion'] . '_' . $seccion['periodo_nombre']
        );
        $this->enviarExcel($nombreArchivo, $html);
    }

    // ============================================================
    // METODOS PRIVADOS DE APOYO
    // ============================================================

    // Valida que la seccion exista y que el rol pueda reportearla
    // Si algo falla, redirige al index del modulo con un mensaje
    private function resolverSeccion(int $seccionId): array
    {
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($seccionId <= 0) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Debe seleccionar una seccion valida.'
            ];
            header('Location: ' . BASE_URL . 'reporte/index');
            exit;
        }

        $seccion = $this->modelSeccion->obtenerPorId($seccionId);
        if (!$seccion) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'La seccion seleccionada no existe.'
            ];
            header('Location: ' . BASE_URL . 'reporte/index');
            exit;
        }

        // El profesor solo puede reportear sus propias secciones
        if ($rol === 'profesor' && !$this->modelSeccion->perteneceAProfesor($seccionId, $usuarioId)) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Sin permisos',
                'text' => 'Solo puedes generar reportes de las secciones que dictas.'
            ];
            header('Location: ' . BASE_URL . 'reporte/index');
            exit;
        }

        return $seccion;
    }

    // Convierte texto UTF-8 a ISO-8859-1 para que FPDF dibuje los acentos
    private function t(string $texto): string
    {
        $convertido = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
        return $convertido === false ? $texto : $convertido;
    }

    // Divide un nombre de actividad en hasta dos lineas que quepan en el ancho
    // Si una palabra es mas larga que el ancho, se recorta con puntos
    private function dividirEnDosLineas(\FPDF $pdf, string $texto, float $ancho): array
    {
        $texto = $this->t(trim($texto));
        $max = $ancho - 1.5;

        // Si el nombre completo cabe en una sola linea, se devuelve tal cual
        if ($pdf->GetStringWidth($texto) <= $max) {
            return [$texto, ''];
        }

        $palabras = explode(' ', $texto);
        $linea1 = '';
        $linea2 = '';

        foreach ($palabras as $palabra) {
            if ($linea2 === '') {
                $prueba = ($linea1 === '') ? $palabra : $linea1 . ' ' . $palabra;
                if ($pdf->GetStringWidth($prueba) <= $max) {
                    $linea1 = $prueba;
                    continue;
                }
                // Si aun no hay linea 1, es una palabra unica muy larga
                if ($linea1 === '') {
                    $linea1 = $this->recortarAlAncho($pdf, $palabra, $max);
                    continue;
                }
                // La palabra ya no cabe en la linea 1: pasa a la linea 2
                $linea2 = $palabra;
            } else {
                $linea2 .= ' ' . $palabra;
            }
        }

        // Si la linea 2 quedo demasiado larga, se recorta con puntos
        if ($linea2 !== '' && $pdf->GetStringWidth($linea2) > $max) {
            $linea2 = $this->recortarAlAncho($pdf, $linea2, $max);
        }

        return [$linea1, $linea2];
    }

    // Recorta un texto agregando puntos hasta que quepa en el ancho indicado
    private function recortarAlAncho(\FPDF $pdf, string $texto, float $max): string
    {
        if ($pdf->GetStringWidth($texto) <= $max) {
            return $texto;
        }
        while (strlen($texto) > 1 && $pdf->GetStringWidth($texto . '..') > $max) {
            $texto = substr($texto, 0, -1);
        }
        return $texto . '..';
    }

    // Devuelve el texto legible del estado de un estudiante
    private function textoEstado(string $estado): string
    {
        return match ($estado) {
            'aprobado' => 'Aprobado',
            'reprobado' => 'Reprobado',
            'en_curso' => 'En curso',
            default => 'Sin notas'
        };
    }

    // Devuelve el texto legible del genero
    private function textoGenero(string $genero): string
    {
        return match ($genero) {
            'M' => 'Masculino',
            'F' => 'Femenino',
            default => 'Otro'
        };
    }

    // Cuenta cuantos estudiantes hay en cada estado
    private function contarEstados(array $reporte): array
    {
        $conteo = ['aprobado' => 0, 'reprobado' => 0, 'en_curso' => 0, 'sin_notas' => 0];
        foreach ($reporte as $fila) {
            $conteo[$fila['estado']]++;
        }
        return $conteo;
    }

    // Limpia un texto para usarlo como nombre de archivo seguro
    private function sanearNombre(string $texto): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '_', $texto);
    }

    // Envia las cabeceras y el contenido para descargar un archivo .xls
    // El BOM inicial garantiza que Excel lea correctamente los acentos
    private function enviarExcel(string $nombreArchivo, string $html): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF" . $html;
        exit;
    }
}