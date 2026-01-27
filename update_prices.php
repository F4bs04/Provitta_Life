<?php
require_once 'db.php';

$products_to_update = [
    'XBOOSTER' => 139.90,
    'BORYSLIM' => 139.90,
    'NXCAP OZON' => 159.92,
    'ÔMEGA 3' => 159.90,
    'K2MK7 + D3' => 159.90,
    'DREAMBLISS' => 175.90,
    'TRI MAGNÉSIO' => 159.90,
    'VITA OZON PLUS' => 139.90,
    'VIRTUOUS CAPS' => 139.90,
    'OZON CREAT (unidade)' => 10.00,
    'LUMINOUS VITA' => 159.90,
    'SOFH D' => 119.90,
    'OZON LEV (unidade)' => 10.00,
    'MELATOZON' => 159.80,
    'ÓLEO SOFH' => 89.80,
    'SABONETE OX3' => 99.90,
    'LIFE SHII' => 159.90,
    'HIDRATANTE SPEED BLACK' => 79.90,
    'HIDRATANTE VG SEXY' => 79.90
];

echo "Iniciando atualização de preços...\n";

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("UPDATE products SET price = :price WHERE name = :name");

    foreach ($products_to_update as $name => $price) {
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            echo "Preço de '" . $name . "' atualizado para R$ " . number_format($price, 2, ',', '.') . "\n";
        } else {
            echo "AVISO: Produto '" . $name . "' não encontrado. Nenhuma alteração feita.\n";
        }
    }

    $pdo->commit();
    echo "\nAtualização de preços concluída com sucesso!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\nERRO: Ocorreu um problema durante a atualização. Nenhuma alteração foi salva.\n";
    echo "Detalhes do erro: " . $e->getMessage() . "\n";
}

?>
