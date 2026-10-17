<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body class="min-h-svh bg-zinc-950 font-sans text-zinc-100 antialiased [&_h1]:text-4xl! md:[&_h1]:text-5xl! [&_h2]:text-2xl! md:[&_h2]:text-3xl! [&_h3]:text-lg! [&_p]:text-zinc-400! [&_input]:border-white/10! [&_input]:bg-zinc-800! [&_input]:text-zinc-100! [&_select]:border-white/10! [&_select]:bg-zinc-800! [&_select]:text-zinc-100!">
<script>try{if(localStorage.getItem('dartiqo-rail')==='closed')document.documentElement.dataset.rail='closed'}catch{}</script>
<div id="root" class="app-shell min-h-svh">
<?php require __DIR__ . '/../partials/app-nav.php'; ?>
<div id="app-main" class="min-w-0 px-4 pt-6 pb-28 sm:px-8 lg:px-10 lg:pt-8 lg:pb-10">
<?= $content ?>
</div>
</div>
<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[100] max-w-[90vw] -translate-x-1/2 translate-y-[15px] rounded-xl bg-accent px-6 py-4 text-xs text-zinc-900 opacity-0 shadow-xl transition-all [&.toast-visible]:translate-y-0 [&.toast-visible]:opacity-100" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/board.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
