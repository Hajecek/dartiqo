<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body class="min-h-svh bg-[#0e1a14] font-sans antialiased" data-app>
<div id="root">
<?= $content ?>
</div>
<div id="live-banner" data-stream="<?= e(url('/app/live/stream')) ?>" data-state="<?= e(url('/app/live/state')) ?>" hidden></div>
<script src="<?= e(asset('js/board.js')) ?>"></script>
<script src="<?= e(asset('js/checkout.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
