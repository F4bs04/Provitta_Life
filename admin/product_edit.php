<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require_once '../db.php';

$productId = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Buscar produto
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Buscar regras
$stmt = $pdo->prepare("SELECT * FROM product_rules WHERE product_id = ? ORDER BY priority DESC");
$stmt->execute([$productId]);
$rules = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $usage = trim($_POST['usage_instruction'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $isBase = isset($_POST['is_base']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl = $product['image_url'];

    if (empty($name)) {
        $error = 'Nome do produto é obrigatório';
    } elseif ($price <= 0) {
        $error = 'Preço deve ser maior que zero';
    } else {
        try {
            // Upload de nova imagem
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['product_image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    // Deletar imagem antiga
                    if (!empty($product['image_url'])) {
                        $oldImagePath = '../' . $product['image_url'];
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    
                    $extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                    $fileName = 'product_' . uniqid() . '.' . $extension;
                    $uploadPath = '../assets/uploads/products/' . $fileName;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                        $imageUrl = 'assets/uploads/products/' . $fileName;
                    }
                } else {
                    $error = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WEBP.';
                }
            }
            
            // Remover imagem se solicitado
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                if (!empty($product['image_url'])) {
                    $oldImagePath = '../' . $product['image_url'];
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
                $imageUrl = null;
            }
            
            if (!$error) {
                // Atualizar produto
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, usage_instruction = ?, price = ?, is_base = ?, is_active = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $description, $usage, $price, $isBase, $isActive, $imageUrl, $productId]);
            
                // Deletar regras antigas
                $pdo->prepare("DELETE FROM product_rules WHERE product_id = ?")->execute([$productId]);
                
                // Adicionar novas regras
                if (!empty($_POST['rules'])) {
                    $stmtRule = $pdo->prepare("INSERT INTO product_rules (product_id, condition_type, condition_value, priority) VALUES (?, ?, ?, ?)");
                    
                    foreach ($_POST['rules'] as $rule) {
                        if (!empty($rule['condition_type']) && !empty($rule['condition_value'])) {
                            $stmtRule->execute([
                                $productId,
                                $rule['condition_type'],
                                $rule['condition_value'],
                                intval($rule['priority'] ?? 0)
                            ]);
                        }
                    }
                }
                
                header('Location: products.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Erro ao atualizar produto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Provitta Life</title>
    <link rel="icon" href="../assets/src/favicon.icon" type="image/x-icon">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen">

    <div id="dot-grid" class="dot-grid"></div>
    <script src="../assets/js/background.js"></script>

    <header class="relative z-10 border-b border-white/10 bg-black/30 backdrop-blur-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="../assets/src/provitta_logopng.png" alt="Provitta Life" class="h-8 w-auto">
                <h1 class="text-2xl font-bold text-white">Editar Produto</h1>
            </div>
            <a href="products.php" class="px-4 py-2 bg-surface/80 hover:bg-surface rounded-lg transition-all text-gray-300 hover:text-white flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </header>

    <main class="relative z-10 container mx-auto px-6 py-8 max-w-4xl" x-data="productForm()">
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-xl text-red-300">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            
            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-6">Informações Básicas</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-gray-300 mb-2 font-medium">Nome do Produto *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required
                            class="w-full px-4 py-3 bg-background/50 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-300 mb-2 font-medium">Descrição do Produto</label>
                        <textarea name="description" rows="4" placeholder="Descreva os benefícios, composição e características do produto..."
                            class="w-full px-4 py-3 bg-background/50 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary transition-all resize-none"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-300 mb-2 font-medium">Instrução de Uso</label>
                        <select name="usage_instruction" required
                            class="w-full px-4 py-3 bg-background/50 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                            <option value="">Selecione uma instrução...</option>
                            <option value="Uso Diário" <?php echo $product['usage_instruction'] == 'Uso Diário' ? 'selected' : ''; ?>>Uso Diário</option>
                            <option value="Manhã" <?php echo $product['usage_instruction'] == 'Manhã' ? 'selected' : ''; ?>>Manhã</option>
                            <option value="Noite" <?php echo $product['usage_instruction'] == 'Noite' ? 'selected' : ''; ?>>Noite</option>
                            <option value="Antes de dormir" <?php echo $product['usage_instruction'] == 'Antes de dormir' ? 'selected' : ''; ?>>Antes de dormir</option>
                            <option value="Após as refeições" <?php echo $product['usage_instruction'] == 'Após as refeições' ? 'selected' : ''; ?>>Após as refeições</option>
                            <option value="Em jejum" <?php echo $product['usage_instruction'] == 'Em jejum' ? 'selected' : ''; ?>>Em jejum</option>
                            <option value="Ingestão" <?php echo $product['usage_instruction'] == 'Ingestão' ? 'selected' : ''; ?>>Ingestão</option>
                            <option value="Aplicação local" <?php echo $product['usage_instruction'] == 'Aplicação local' ? 'selected' : ''; ?>>Aplicação local</option>
                            <option value="Aplicação tópica" <?php echo $product['usage_instruction'] == 'Aplicação tópica' ? 'selected' : ''; ?>>Aplicação tópica</option>
                            <option value="2x ao dia" <?php echo $product['usage_instruction'] == '2x ao dia' ? 'selected' : ''; ?>>2x ao dia</option>
                            <option value="3x ao dia" <?php echo $product['usage_instruction'] == '3x ao dia' ? 'selected' : ''; ?>>3x ao dia</option>
                            <option value="Conforme necessário" <?php echo $product['usage_instruction'] == 'Conforme necessário' ? 'selected' : ''; ?>>Conforme necessário</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2 font-medium">Preço (R$) *</label>
                        <input type="number" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required
                            class="w-full px-4 py-3 bg-background/50 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                    </div>

                    <div class="flex flex-col gap-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_base" <?php echo $product['is_base'] ? 'checked' : ''; ?> class="w-5 h-5 rounded bg-background/50 border-white/10 text-primary focus:ring-primary">
                            <span class="text-gray-300">Produto Base (Obrigatório)</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?> class="w-5 h-5 rounded bg-background/50 border-white/10 text-primary focus:ring-primary">
                            <span class="text-gray-300">Produto Ativo</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-surface/80 backdrop-blur-xl border border-white/10 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">Regras Condicionais</h2>
                    <button type="button" @click="addRule()" class="px-4 py-2 bg-primary/20 hover:bg-primary/30 text-primary rounded-lg transition-all text-sm font-semibold">
                        + Adicionar Regra
                    </button>
                </div>

                <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                    <p class="text-sm text-blue-300">
                        <strong>ℹ️ Como funciona:</strong> As regras definem quando este produto será incluído no protocolo do cliente. 
                        Por exemplo: se você criar uma regra "Dor = Sim", o produto será adicionado automaticamente quando o cliente 
                        responder "Sim" para dores no formulário. A prioridade define a ordem (maior = primeiro).
                    </p>
                </div>

                <div class="space-y-4">
                    <template x-for="(rule, index) in rules" :key="index">
                        <div class="p-4 bg-background/30 rounded-lg border border-white/5">
                            <div class="grid grid-cols-1 gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-2 font-semibold uppercase tracking-wide">Condição (Sintoma)</label>
                                        <select :name="'rules['+index+'][condition_type]'" x-model="rule.condition_type"
                                            class="w-full px-4 py-2 bg-background/50 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                                            <option value="">Selecione o sintoma...</option>
                                            <option value="pain">🩹 Dor (Crônica/Aguda)</option>
                                            <option value="pressure">💓 Pressão Alta</option>
                                            <option value="diabetes">🩺 Diabetes</option>
                                            <option value="sleep">😴 Qualidade do Sono</option>
                                            <option value="emotional">🧠 Estado Emocional</option>
                                            <option value="gut">🦠 Saúde Intestinal</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-400 mb-2 font-semibold uppercase tracking-wide">Quando o valor for</label>
                                        <select :name="'rules['+index+'][condition_value]'" x-model="rule.condition_value"
                                            class="w-full px-4 py-2 bg-background/50 border border-white/10 rounded-lg text-white focus:outline-none focus:border-primary transition-all">
                                            <option value="">Selecione o valor...</option>
                                            <optgroup label="Sim/Não">
                                                <option value="yes">✓ Sim</option>
                                                <option value="no">✗ Não</option>
                                            </optgroup>
                                            <optgroup label="Qualidade">
                                                <option value="bad">😞 Ruim</option>
                                                <option value="good">😊 Bom</option>
                                            </optgroup>
                                            <optgroup label="Estado">
                                                <option value="unstable">⚠️ Instável</option>
                                                <option value="stable">✓ Estável</option>
                                            </optgroup>
                                            <optgroup label="Intestino">
                                                <option value="constipated">🔒 Preso (Constipado)</option>
                                                <option value="loose">💧 Solto (Diarreia)</option>
                                                <option value="normal">✓ Normal</option>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-400 mb-2 font-semibold uppercase tracking-wide">
                                            Prioridade
                                            <span class="text-gray-500 normal-case font-normal">(maior = primeiro)</span>
                                        </label>
                                        <input type="number" :name="'rules['+index+'][priority]'" x-model="rule.priority" 
                                            placeholder="Ex: 10" min="0" max="100"
                                            class="w-full px-4 py-2 bg-background/50 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-primary transition-all">
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center pt-2 border-t border-white/5">
                                    <p class="text-xs text-gray-500">
                                        <strong>Exemplo:</strong> Se "Dor = Sim" e prioridade "10", este produto será incluído quando o cliente tiver dores.
                                    </p>
                                    <button type="button" @click="removeRule(index)" class="px-3 py-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition-all text-sm font-semibold flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <p x-show="rules.length === 0" class="text-gray-500 text-center py-8 bg-background/20 rounded-lg border border-dashed border-white/10">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nenhuma regra adicionada ainda.<br>
                        <span class="text-sm">Clique em "Adicionar Regra" para definir quando este produto deve ser incluído no protocolo.</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-4 justify-end">
                <a href="products.php" class="px-6 py-3 bg-surface/80 hover:bg-surface rounded-lg transition-all text-gray-300 hover:text-white font-semibold">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-primary hover:bg-secondary text-background font-bold rounded-lg transition-all shadow-lg shadow-primary/20">
                    Atualizar Produto
                </button>
            </div>

        </form>

    </main>

    <script>
        function productForm() {
            return {
                rules: <?php echo json_encode(array_map(function($r) {
                    return [
                        'condition_type' => $r['condition_type'],
                        'condition_value' => $r['condition_value'],
                        'priority' => $r['priority']
                    ];
                }, $rules)); ?>,
                addRule() {
                    this.rules.push({ condition_type: '', condition_value: '', priority: 0 });
                },
                removeRule(index) {
                    this.rules.splice(index, 1);
                }
            }
        }
    </script>

</body>
</html>
