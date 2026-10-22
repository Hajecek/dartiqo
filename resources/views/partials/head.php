<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#09090b">
<meta name="csrf-token" content="<?= e(auth()->csrfToken()) ?>">
<?php if ($currentUser = auth()->user()): ?>
<script id="current-user" type="application/json"><?= json_encode(\App\Repositories\UserRepository::toPublic($currentUser), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<title><?= e($title ?? 'Dartiqo') ?></title>
<meta name="description" content="Objev Dartiqo: šipky, trénink, kariéra a online souboje. Interaktivní frontendová ukázka.">
<link rel="icon" href="<?= e(asset('images/logo.png')) ?>">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('dartiqo-design-theme')||'dark'}catch{}</script>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
