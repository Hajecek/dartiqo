<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body<?= ($bodyClass ?? '') !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
<div id="root">
<?php require __DIR__ . '/../partials/header.php'; ?>
<?= $content ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</div>
<div id="toast" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
