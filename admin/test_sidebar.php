<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sidebar</title>
    <link href="../assets/css/style.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-background text-white">
    <h1 class="p-4">Testing Sidebar Visibility</h1>
    
    <!-- Test if sidebar shows -->
    <div class="hidden lg:flex bg-red-500 p-4 fixed left-0 top-20">
        Desktop Sidebar (should show on desktop)
    </div>
    
    <div class="lg:hidden bg-blue-500 p-4 fixed left-0 top-40">
        Mobile Header (should show on mobile)
    </div>
    
    <div class="p-20">
        <p>Window width: <span id="width"></span>px</p>
        <p>Tailwind lg breakpoint: 1024px</p>
    </div>
    
    <script>
        function updateWidth() {
            document.getElementById('width').textContent = window.innerWidth;
        }
        updateWidth();
        window.addEventListener('resize', updateWidth);
    </script>
</body>
</html>
