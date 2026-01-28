<?php
require 'vendor/autoload.php';
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['protocol'])) {
    header('Location: index.php');
    exit;
}

// Carregar idioma
$lang_code = $_SESSION['lang'] ?? 'pt';
if (!in_array($lang_code, ['pt', 'en', 'es'])) $lang_code = 'pt';
$lang = require "lang/$lang_code.php";

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
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

// HTML Content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background-color: #f9fafb; font-size: 12px; text-transform: uppercase; color: #666; }
        .product-img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; }
        .product-name { font-weight: bold; color: #1f2937; }
        .product-usage { font-size: 11px; color: #6b7280; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; color: #45A29E; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .alert { color: #d97706; font-size: 12px; margin-bottom: 5px; }
        .no-image { width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: inline-block; text-align: center; line-height: 40px; color: #9ca3af; font-size: 20px; }
    </style>
</head>
<body>

    <div class="header">
        <img src="' . $logoSrc . '" class="logo-img">
        <div class="title">' . $lang['pdf_title'] . '</div>
        <div class="client-name">' . $lang['pdf_prepared_for'] . ': ' . htmlspecialchars($leadName) . '</div>
        <div class="date">' . $lang['pdf_generated_at'] . ': ' . date($lang['date_format']) . '</div>
    </div>

    <div class="section">
        <div class="section-title">' . $lang['pdf_anamnesis_summary'] . '</div>
        <p>' . $lang['pdf_anamnesis_desc'] . '</p>
        <ul>';
        
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $html .= '<li class="alert">' . htmlspecialchars($alert) . '</li>';
            }
        } else {
            $html .= '<li>' . $lang['pdf_general_optimization'] . '</li>';
        }

$html .= '
        </ul>
    </div>

    <div class="section">
        <div class="section-title">' . $lang['pdf_protocol_title'] . '</div>
        <table>
            <thead>
                <tr>
                    <th width="50">' . $lang['pdf_column_image'] . '</th>
                    <th>' . $lang['pdf_column_product'] . '</th>
                    <th>' . $lang['pdf_column_usage'] . '</th>
                    <th width="100">' . $lang['pdf_column_value'] . '</th>
                </tr>
            </thead>
            <tbody>';

foreach ($protocol as $item) {
    // Convert product image to base64 if exists
    $productImgHtml = '<div class="no-image">📦</div>';
    if (!empty($item['image']) && file_exists($item['image'])) {
        $imageData = base64_encode(file_get_contents($item['image']));
        $imageMime = mime_content_type($item['image']);
        $imageSrc = 'data:' . $imageMime . ';base64,' . $imageData;
        $productImgHtml = '<img src="' . $imageSrc . '" class="product-img">';
    }
    
    $html .= '
                <tr>
                    <td>' . $productImgHtml . '</td>
                    <td class="product-name">' . htmlspecialchars($item['name']) . '</td>
                    <td class="product-usage">' . htmlspecialchars($item['usage']) . '</td>
                    <td>' . $lang['currency_symbol'] . ' ' . number_format($item['price'], 2, $lang['decimal_separator'], $lang['thousands_separator']) . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="total">
        ' . $lang['pdf_total_investment'] . ': ' . $lang['currency_symbol'] . ' ' . number_format($total, 2, $lang['decimal_separator'], $lang['thousands_separator']) . '
    </div>

    <div class="footer">
        <p>' . $lang['pdf_disclaimer'] . '</p>
        <p>&copy; ' . date('Y') . ' Provitta Life. ' . $lang['pdf_rights_reserved'] . '</p>
    </div>

</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generate filename with client name
$filename = 'Protocolo_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $leadName) . '_' . date('Y-m-d') . '.pdf';

// Output the generated PDF to Browser
$dompdf->stream($filename, ["Attachment" => true]);
?>
