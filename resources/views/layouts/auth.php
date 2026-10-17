<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body>
<div id="root">
<?= $content ?>
</div>
<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[100] max-w-[90vw] -translate-x-1/2 translate-y-[15px] rounded-xl bg-accent px-6 py-4 text-xs text-zinc-900 opacity-0 shadow-xl transition-all [&.toast-visible]:translate-y-0 [&.toast-visible]:opacity-100" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
