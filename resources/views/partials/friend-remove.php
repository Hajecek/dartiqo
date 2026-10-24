<?php

/** @var string $csrf */
$csrf = (string) ($csrf ?? '');
?>
<dialog class="m-auto w-[min(420px,calc(100%-2rem))] rounded-[28px] border border-white/10 bg-zinc-950 p-6 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm open:animate-reveal" id="sheet-remove-friend">
<span class="grid size-12 place-items-center rounded-2xl bg-rose-400/10 text-rose-300 ring-1 ring-rose-400/25 ring-inset"><?= dartiqo_i('trash', 'size-5') ?></span>
<h2 class="mt-4">Odebrat kamaráda?</h2>
<p class="mt-2 text-sm text-zinc-400"><strong class="font-semibold text-zinc-100" data-remove-label>Tohoto hráče</strong> zmizí ze seznamu. Společné zápasy v historii zůstanou.</p>
<form class="mt-6 flex justify-end gap-2" method="post" action="" data-remove-form>
<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
<button class="pill" type="button" data-sheet-close>Zrušit</button>
<button class="pill bg-rose-500! text-white! hover:bg-rose-400!" type="submit">Odebrat</button>
</form>
</dialog>
<script>
document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-remove-action]');
  if (!button) return;
  const dialog = document.getElementById('sheet-remove-friend');
  const form = dialog?.querySelector('[data-remove-form]');
  const label = dialog?.querySelector('[data-remove-label]');
  const name = button.dataset.removeName || 'Tohoto hráče';
  if (form) form.action = button.dataset.removeAction || '';
  if (label) label.textContent = name;
  if (dialog && typeof dialog.showModal === 'function') {
    if (!dialog.open) dialog.showModal();
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  if (window.confirm('Odebrat ' + name + ' z kamarádů?')) form?.submit();
});
</script>
