<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: user_login.php');
    exit;
}

// Atualizar última atividade da sessão
require_once 'db.php';
$session_id = session_id();
$stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = CURRENT_TIMESTAMP WHERE session_id = ?");
$stmt->execute([$session_id]);

// Carregar idioma
$lang_code = $_GET['lang'] ?? $_SESSION['lang'] ?? 'pt';
if (!in_array($lang_code, ['pt', 'en', 'es'])) $lang_code = 'pt';
$_SESSION['lang'] = $lang_code;
$lang = require "lang/$lang_code.php";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Avaliação metabólica Provitta Life - Descubra seu protocolo de suplementação ideal.">
    <meta name="author" content="Fabian Araújo">
    <title>Avaliação - Provitta Life</title>
    <link rel="icon" href="./assets/src/favicon.icon" type="image/x-icon">
    <link href="./assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .step-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="bg-background bg-brand-gradient text-text font-sans antialiased min-h-screen flex flex-col relative bg-fixed">

    <!-- Header -->
    <header class="relative z-10 border-b border-white/5 bg-black/20 backdrop-blur-md">
        <div class="container mx-auto px-4 md:px-6 py-3 md:py-4">
            <div class="flex justify-between items-center">
                <!-- Logo (clickable) -->
                <a href="index.php" class="transition-opacity hover:opacity-80" title="<?php echo $lang['back_home']; ?>">
                    <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-10 w-auto" style="max-height: 40px;">
                </a>
                
                <!-- Right side: Language + Session info + Logout -->
                <div class="flex items-center gap-3 md:gap-4">
                    <!-- Language Switcher -->
                    <div class="flex items-center gap-1 bg-white/5 rounded-lg p-1">
                        <a href="?lang=pt" class="px-2 py-1 rounded text-xs font-bold transition-colors <?php echo $lang_code === 'pt' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'; ?>">PT</a>
                        <a href="?lang=en" class="px-2 py-1 rounded text-xs font-bold transition-colors <?php echo $lang_code === 'en' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'; ?>">EN</a>
                        <a href="?lang=es" class="px-2 py-1 rounded text-xs font-bold transition-colors <?php echo $lang_code === 'es' ? 'bg-primary text-background' : 'text-gray-400 hover:text-white'; ?>">ES</a>
                    </div>

                    <div class="hidden sm:block text-xs text-gray-400 font-mono">
                        <?php 
                        if (isset($_SESSION['user_type'])) {
                            echo $_SESSION['user_type'] === 'invite' ? $lang['invite_code'] : htmlspecialchars($_SESSION['user_name'] ?? $lang['username']);
                        }
                        ?>
                    </div>
                    <a href="user_logout.php" 
                       class="px-3 py-1.5 md:px-4 md:py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors text-xs md:text-sm font-medium">
                        <?php echo $lang['logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-grow container mx-auto px-4 pt-12 pb-24 max-w-4xl flex flex-col" x-data="formWizard()">
        
        <!-- Progress Bar -->
        <div class="mb-8 w-full max-w-2xl mx-auto">
            <div class="h-1.5 w-full bg-surface rounded-full overflow-hidden shadow-inner">
                <div class="h-full bg-gradient-to-r from-primary to-secondary transition-all duration-700 ease-out" :style="'width: ' + progress + '%'"></div>
            </div>
            <div class="mt-4 flex justify-between text-[10px] text-gray-500 font-bold uppercase tracking-[0.2em]">
                <span :class="step >= 1 ? 'text-primary' : ''"><?php echo $lang['start']; ?></span>
                <span class="text-gray-400" x-text="'<?php echo str_replace('%s', "' + step + '", str_replace('%s', "' + totalSteps + '", $lang['step_x_of_y'])); ?>'"></span>
                <span :class="step === totalSteps ? 'text-primary' : ''"><?php echo $lang['end']; ?></span>
            </div>
        </div>

        <form action="process.php" method="POST" class="flex-grow flex flex-col items-center justify-start pt-4">
            
            <div class="w-full max-w-2xl relative">
                <!-- Step 1: Dores -->
                <div x-show="step === 1" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step1_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step1_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="group">
                                <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step1_q1']; ?></label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="pain" value="yes" class="peer sr-only" x-model="formData.pain">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['yes']; ?>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="pain" value="no" class="peer sr-only" x-model="formData.pain">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['no']; ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Pressão e Diabetes -->
                <div x-show="step === 2" x-cloak
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step2_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step2_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="group">
                                <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step2_q1']; ?></label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="pressure" value="yes" class="peer sr-only" x-model="formData.pressure">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['yes']; ?>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="pressure" value="no" class="peer sr-only" x-model="formData.pressure">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['no']; ?>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="group">
                                <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step2_q2']; ?></label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="diabetes" value="yes" class="peer sr-only" x-model="formData.diabetes">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['yes']; ?>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group/opt">
                                        <input type="radio" name="diabetes" value="no" class="peer sr-only" x-model="formData.diabetes">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center font-bold">
                                            <?php echo $lang['no']; ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Sono -->
                <div x-show="step === 3" x-cloak 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step3_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step3_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="group">
                                <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step3_q1']; ?></label>
                                <div class="space-y-3">
                                    <label class="cursor-pointer block group/opt">
                                        <input type="radio" name="sleep" value="good" class="peer sr-only" x-model="formData.sleep">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all flex items-center justify-between font-bold">
                                            <span><?php echo $lang['step3_opt1']; ?></span>
                                            <div class="w-5 h-5 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center">
                                                <div class="w-2 h-2 bg-background rounded-full"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer block group/opt">
                                        <input type="radio" name="sleep" value="bad" class="peer sr-only" x-model="formData.sleep">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all flex items-center justify-between font-bold">
                                            <span><?php echo $lang['step3_opt2']; ?></span>
                                            <div class="w-5 h-5 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center">
                                                <div class="w-2 h-2 bg-background rounded-full"></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Estado Emocional -->
                <div x-show="step === 4" x-cloak 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step4_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step4_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="group">
                                <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step4_q1']; ?></label>
                                <div class="space-y-3">
                                    <label class="cursor-pointer block group/opt">
                                        <input type="radio" name="emotional" value="stable" class="peer sr-only" x-model="formData.emotional">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all flex items-center justify-between font-bold">
                                            <span><?php echo $lang['step4_opt1']; ?></span>
                                            <div class="w-5 h-5 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center">
                                                <div class="w-2 h-2 bg-background rounded-full"></div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer block group/opt">
                                        <input type="radio" name="emotional" value="unstable" class="peer sr-only" x-model="formData.emotional">
                                        <div class="p-4 rounded-2xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all flex items-center justify-between font-bold">
                                            <span><?php echo $lang['step4_opt2']; ?></span>
                                            <div class="w-5 h-5 rounded-full border-2 border-white/20 peer-checked:border-primary peer-checked:bg-primary flex items-center justify-center">
                                                <div class="w-2 h-2 bg-background rounded-full"></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Intestino -->
                <div x-show="step === 5" x-cloak 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step5_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step5_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <label class="block text-gray-300 text-lg mb-4 font-medium text-center"><?php echo $lang['step5_q1']; ?></label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <label class="cursor-pointer group/opt">
                                    <input type="radio" name="gut" value="constipated" class="peer sr-only" x-model="formData.gut">
                                    <div class="p-6 rounded-3xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center h-full flex flex-col items-center justify-center gap-4">
                                        <span class="text-5xl">🐢</span>
                                        <span class="font-bold"><?php echo $lang['step5_opt1']; ?></span>
                                    </div>
                                </label>
                                <label class="cursor-pointer group/opt">
                                    <input type="radio" name="gut" value="loose" class="peer sr-only" x-model="formData.gut">
                                    <div class="p-6 rounded-3xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center h-full flex flex-col items-center justify-center gap-4">
                                        <span class="text-5xl">🐇</span>
                                        <span class="font-bold"><?php echo $lang['step5_opt2']; ?></span>
                                    </div>
                                </label>
                                <label class="cursor-pointer group/opt">
                                    <input type="radio" name="gut" value="normal" class="peer sr-only" x-model="formData.gut">
                                    <div class="p-6 rounded-3xl border border-white/5 bg-white/5 group-hover/opt:bg-white/10 peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary transition-all text-center h-full flex flex-col items-center justify-center gap-4">
                                        <span class="text-5xl">✨</span>
                                        <span class="font-bold"><?php echo $lang['step5_opt3']; ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Observações -->
                <div x-show="step === 6" x-cloak 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step6_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step6_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <label class="block text-gray-300 text-lg mb-4 font-medium"><?php echo $lang['step6_q1']; ?></label>
                            <textarea name="observations" rows="6" class="w-full bg-background/50 border border-white/10 rounded-2xl p-6 text-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all placeholder-gray-600 resize-none" placeholder="<?php echo $lang['step6_placeholder']; ?>" x-model="formData.observations"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 7: Identificação (Lead) -->
                <div x-show="step === 7" x-cloak 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     class="w-full">
                    <div class="bg-surface/90 backdrop-blur-2xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-primary/10 rounded-2xl text-primary shadow-lg shadow-primary/5">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white"><?php echo $lang['step7_title']; ?></h2>
                                <p class="text-gray-400 text-sm"><?php echo $lang['step7_subtitle']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1"><?php echo $lang['name_label']; ?></label>
                                <input type="text" name="name" required class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="<?php echo $lang['name_placeholder']; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1"><?php echo $lang['email_label']; ?></label>
                                <input type="email" name="email" required class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="<?php echo $lang['email_placeholder']; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-400 text-xs font-bold uppercase tracking-widest mb-2 ml-1"><?php echo $lang['cpf_label']; ?></label>
                                <input type="text" name="cpf" required class="w-full bg-background/50 border border-white/10 rounded-2xl p-4 text-white focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="<?php echo $lang['cpf_placeholder']; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls (Inside Card Context) -->
                <div class="mt-8 flex justify-between items-center px-2">
                    <button type="button" 
                            x-show="step > 1" 
                            @click="prevStep()"
                            class="px-6 py-3 text-gray-400 hover:text-white transition-colors font-bold flex items-center gap-2 group">
                        <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        <?php echo $lang['back']; ?>
                    </button>
                    <div x-show="step === 1"></div> <!-- Spacer -->

                    <button type="button" 
                            x-show="step < 7" 
                            @click="nextStep()"
                            class="px-10 py-4 bg-primary text-background font-black rounded-2xl hover:bg-secondary hover:shadow-[0_0_30px_rgba(102,252,241,0.4)] transition-all flex items-center gap-3 transform hover:scale-105 active:scale-95">
                        <?php echo $lang['next']; ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <button type="submit" 
                            x-show="step === 7" 
                            @click="loading = true"
                            class="px-10 py-4 bg-gradient-to-r from-primary to-secondary text-background font-black rounded-2xl hover:shadow-[0_0_40px_rgba(102,252,241,0.6)] transform hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                        <?php echo $lang['generate_protocol']; ?>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </button>
                </div>
            </div>

        </form>
    </main>

    <!-- Loader Overlay -->
    <div x-show="loading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 z-[60] bg-background/90 backdrop-blur-sm flex items-center justify-center" x-cloak>
        <div class="flex flex-col items-center gap-4">
            <img src="assets/src/provitta_logopng.png" alt="Provitta Life" class="h-12 w-auto mb-2">
            <div class="loader"></div>
            <div class="text-primary font-mono animate-pulse"><?php echo $lang['processing']; ?></div>
        </div>
    </div>

    <script>
        function formWizard() {
            return {
                step: 1,
                totalSteps: 7,
                loading: false,
                formData: {
                    pain: '',
                    pressure: '',
                    diabetes: '',
                    sleep: '',
                    emotional: '',
                    gut: '',
                    observations: ''
                },
                get progress() {
                    return ((this.step - 1) / (this.totalSteps - 1)) * 100;
                },
                nextStep() {
                    if (this.step < this.totalSteps) {
                        this.step++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                prevStep() {
                    if (this.step > 1) {
                        this.step--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }
        }
    </script>
</body>
</html>
