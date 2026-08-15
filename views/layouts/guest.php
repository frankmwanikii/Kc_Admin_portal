<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <link rel="stylesheet" href="/css/app-copyright.css">
</head>
<body class="h-full login-body antialiased">
    <div class="login-shell">
        <?= $content ?>
        <?php $copyrightVariant = 'app-copyright--login'; require __DIR__ . '/../partials/app-copyright.php'; ?>
    </div>
</body>
</html>
