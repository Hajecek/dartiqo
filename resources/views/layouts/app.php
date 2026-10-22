<!doctype html>
<html lang="cs" data-theme="dark">
<?php require __DIR__ . '/../partials/head.php'; ?>
<body class="min-h-svh bg-zinc-950 font-sans text-zinc-100 antialiased selection:bg-accent selection:text-black" data-app>
<script>try{if(localStorage.getItem('dartiqo-rail')==='closed')document.documentElement.dataset.rail='closed'}catch{}</script>
<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
<div class="absolute -top-48 right-[-10%] h-[36rem] w-[52rem] rounded-full bg-[radial-gradient(closest-side,#faff0014,transparent)] blur-2xl"></div>
<div class="absolute bottom-[-20rem] left-[20%] h-[32rem] w-[44rem] rounded-full bg-[radial-gradient(closest-side,#34d39908,transparent)] blur-2xl"></div>
<div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] light:bg-[radial-gradient(#0000000f_1px,transparent_1px)] [mask-image:linear-gradient(to_bottom,#000,transparent_70%)] bg-[size:28px_28px]"></div>
</div>
<div id="root" class="app-shell min-h-svh">
<?php require __DIR__ . '/../partials/app-nav.php'; ?>
<div id="app-main" class="min-w-0 px-4 pt-6 pb-28 sm:px-8 lg:px-10 lg:pt-8 lg:pb-12">
<?= $content ?>
</div>
</div>
<div id="toast" class="pointer-events-none fixed bottom-24 left-1/2 z-[100] max-w-[90vw] -translate-x-1/2 translate-y-[15px] rounded-2xl bg-accent px-5 py-3.5 text-xs font-medium text-zinc-900 opacity-0 shadow-[0_18px_40px_-12px_#faff0066] transition-all lg:bottom-8 [&.toast-visible]:translate-y-0 [&.toast-visible]:opacity-100" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/board.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
