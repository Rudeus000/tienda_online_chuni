<?php

/**
 * Plantila de FPDF para genera reporte de compras
 * Autor: Marco Robles
 * Web: https://github.com/mroblesdev
 */

require 'fpdf.php';

class PDF extends FPDF
{
    private $fechaIni;
    private $fechaFin;

    public function __construct($orientacion, $medidas, $tamanio, $datos)
    {
        parent::__construct($orientacion, $medidas, $tamanio);
        $this->fechaIni = $datos['fechaIni'];
        $this->fechaFin = $datos['fechaFin'];
    }

    // Cabecera de página
    public function Header()
    {
        // Configurar zona horaria de Perú
        date_default_timezone_set('America/Lima');
        
        // Logo
        $this->Image('../images/logo.png', 10, 5, 20);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 11);

        // Título
        $y = $this->GetY();
        $this->SetX(30);
        $this->MultiCell(130, 8, mb_convert_encoding('Reporte de Compras', 'ISO-8859-1', 'UTF-8'), 0, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetX(30);
        $this->MultiCell(130, 5, mb_convert_encoding('Del ' . $this->fechaIni . ' al '. $this->fechaFin, 'ISO-8859-1', 'UTF-8'), 0, 'C');

        $this->SetXY(160, $y);
        $fecha_actual = date('d/m/Y H:i');
        $this->Cell(40, 10, mb_convert_encoding('Fecha: ' . $fecha_actual . ' (Perú)', 'ISO-8859-1', 'UTF-8'), 0, 'L');

        // Salto de línea
        $this->Ln(15);
    }

    // Pie de página
    public function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}
