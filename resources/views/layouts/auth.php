<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body>
<div id="root">
<?= $content ?>
</div>
<div id="toast" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
