<?php
require 'vendor/autoload.php';
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['protocol'])) {
    header('Location: index.php');
    exit;
}

$protocol = $_SESSION['protocol'];
$total = $_SESSION['total'];
$alerts = $_SESSION['alerts'] ?? [];
$leadName = $_SESSION['lead_name'] ?? 'Cliente';

// Convert logo to base64
$logoPath = 'assets/src/provitta_logopng.png';
$logoData = base64_encode(file_get_contents($logoPath));
$logoSrc = 'data:image/png;base64,' . $logoData;

// Configure Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// HTML Content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #66FCF1; padding-bottom: 20px; }
        .logo-img { height: 60px; margin-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
        .client-name { font-size: 16px; color: #45A29E; margin-bottom: 5px; }
        .date { font-size: 12px; color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #45A29E; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f9fafb; font-size: 12px; text-transform: uppercase; color: #666; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; color: #45A29E; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .alert { color: #d97706; font-size: 12px; margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <img src="' . $logoSrc . '" class="logo-img">
        <div class="title">Protocolo de Saúde Personalizado</div>
        <div class="client-name">Preparado para: ' . htmlspecialchars($leadName) . '</div>
        <div class="date">Gerado em: ' . date('d/m/Y H:i') . '</div>
    </div>

    <div class="section">
        <div class="section-title">Resumo da Anamnese</div>
        <p>Baseado nas suas respostas, identificamos a necessidade de suporte para:</p>
        <ul>';
        
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $html .= '<li class="alert">' . htmlspecialchars($alert) . '</li>';
            }
        } else {
            $html .= '<li>Otimização metabólica geral</li>';
        }

$html .= '
        </ul>
    </div>

    <div class="section">
        <div class="section-title">Seu Protocolo</div>
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Como Usar</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>';

foreach ($protocol as $item) {
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['name']) . '</td>
                    <td>' . htmlspecialchars($item['usage']) . '</td>
                    <td>R$ ' . number_format($item['price'], 2, ',', '.') . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="total">
        Investimento Total: R$ ' . number_format($total, 2, ',', '.') . '
    </div>

    <div class="footer">
        <p>Este documento é uma sugestão de suplementação baseada em algoritmo. Não substitui consulta médica.</p>
        <p>&copy; ' . date('Y') . ' Provitta Life. Todos os direitos reservados.</p>
    </div>

</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream("Protocolo_Provitta_Life.pdf", ["Attachment" => true]);
?>
