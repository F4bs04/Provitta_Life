<?php
session_start();
require_once '../db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Verificar autenticação
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    die('Acesso negado');
}

// Obter ID do lead
$leadId = $_GET['id'] ?? null;
if (!$leadId) {
    http_response_code(400);
    die('ID do lead não fornecido');
}

// Buscar dados do lead
$stmt = $pdo->prepare("
    SELECT l.*, 
           GROUP_CONCAT(pi.product_name || '|' || pi.usage_instruction || '|' || pi.price, ';') as products_data
    FROM leads l
    LEFT JOIN protocol_items pi ON l.id = pi.lead_id
    WHERE l.id = ?
    GROUP BY l.id
");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead) {
    http_response_code(404);
    die('Lead não encontrado');
}

// Processar produtos e buscar imagens
$protocol = [];
if (!empty($lead['products_data'])) {
    $productsArray = explode(';', $lead['products_data']);
    foreach ($productsArray as $productData) {
        if (trim($productData)) {
            $parts = explode('|', $productData);
            if (count($parts) === 3) {
                $productName = $parts[0];
                
                // Buscar imagem do produto
                $stmtImg = $pdo->prepare("SELECT image_url FROM products WHERE name = ? LIMIT 1");
                $stmtImg->execute([$productName]);
                $productImg = $stmtImg->fetch();
                
                $protocol[] = [
                    'name' => $productName,
                    'usage' => $parts[1],
                    'price' => floatval($parts[2]),
                    'image' => $productImg['image_url'] ?? null
                ];
            }
        }
    }
}

// Gerar alertas baseados na anamnese
$alerts = [];
if ($lead['pain'] === 'yes') {
    $alerts[] = 'Suporte para alívio de dores';
}
if ($lead['pressure'] === 'yes') {
    $alerts[] = 'Controle de pressão arterial';
}
if ($lead['diabetes'] === 'yes') {
    $alerts[] = 'Suporte para controle glicêmico';
}
if ($lead['sleep'] === 'bad') {
    $alerts[] = 'Melhoria da qualidade do sono';
}
if ($lead['emotional'] === 'unstable') {
    $alerts[] = 'Equilíbrio emocional';
}
if ($lead['gut'] !== 'normal') {
    $alerts[] = 'Saúde intestinal';
}

// Convert logo to base64
$logoPath = '../assets/src/provitta_logopng.png';
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
        .no-image { width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: inline-block; text-align: center; line-height: 40px; color: #9ca3af; font-size: 20px; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; color: #45A29E; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .alert { color: #d97706; font-size: 12px; margin-bottom: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .info-item { font-size: 12px; }
        .info-label { font-weight: bold; color: #666; }
    </style>
</head>
<body>

    <div class="header">
        <img src="' . $logoSrc . '" class="logo-img">
        <div class="title">Protocolo de Saúde Personalizado</div>
        <div class="client-name">Preparado para: ' . htmlspecialchars($lead['name'] ?? 'Cliente') . '</div>
        <div class="date">Gerado em: ' . date('d/m/Y H:i', strtotime($lead['created_at'])) . '</div>
    </div>

    <div class="section">
        <div class="section-title">Informações do Cliente</div>
        <div class="info-grid">
            <div class="info-item"><span class="info-label">Nome:</span> ' . htmlspecialchars($lead['name'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Email:</span> ' . htmlspecialchars($lead['email'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">CPF:</span> ' . htmlspecialchars($lead['cpf'] ?? 'N/A') . '</div>
            <div class="info-item"><span class="info-label">Data:</span> ' . date('d/m/Y', strtotime($lead['created_at'])) . '</div>
        </div>
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
        </ul>';

if (!empty($lead['observations'])) {
    $html .= '<p style="margin-top: 10px;"><span class="info-label">Observações:</span> ' . htmlspecialchars($lead['observations']) . '</p>';
}

$html .= '
    </div>

    <div class="section">
        <div class="section-title">Seu Protocolo</div>
        <table>
            <thead>
                <tr>
                    <th width="50">Imagem</th>
                    <th>Produto</th>
                    <th>Como Usar</th>
                    <th width="100">Valor</th>
                </tr>
            </thead>
            <tbody>';

foreach ($protocol as $item) {
    // Convert product image to base64 if exists
    $productImgHtml = '<div class="no-image">📦</div>';
    if (!empty($item['image']) && file_exists('../' . $item['image'])) {
        $imageData = base64_encode(file_get_contents('../' . $item['image']));
        $imageMime = mime_content_type('../' . $item['image']);
        $imageSrc = 'data:' . $imageMime . ';base64,' . $imageData;
        $productImgHtml = '<img src="' . $imageSrc . '" class="product-img">';
    }
    
    $html .= '
                <tr>
                    <td>' . $productImgHtml . '</td>
                    <td class="product-name">' . htmlspecialchars($item['name']) . '</td>
                    <td class="product-usage">' . htmlspecialchars($item['usage']) . '</td>
                    <td>R$ ' . number_format($item['price'], 2, ',', '.') . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="total">
        Investimento Total: R$ ' . number_format($lead['total_price'], 2, ',', '.') . '
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
$filename = 'Protocolo_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $lead['name'] ?? 'Cliente') . '_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, ["Attachment" => true]);
?>
