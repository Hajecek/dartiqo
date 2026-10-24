<?php

/** @var string $query */
/** @var bool $openFind */
/** @var string|null $flash */

$query = (string) ($query ?? '');
$openFind = (bool) ($openFind ?? false);
$flash = $flash ?? null;
?>
<dialog class="m-auto w-[min(720px,calc(100%-2rem))] rounded-[28px] border border-white/10 bg-zinc-950 p-0 text-left text-zinc-100 shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm open:animate-reveal" id="sheet-find-player" <?= $openFind ? 'data-open="1"' : '' ?>>
<div class="flex max-h-[min(860px,calc(100svh-2rem))] min-h-[min(560px,calc(100svh-2rem))] flex-col p-8 sm:p-10">
<div class="flex items-start justify-between gap-4">
<div>
<h2 id="search-title">Najít hráče</h2>
<p class="mt-1 text-xs text-zinc-500" id="search-hint">Nejnovější hráči. Stačí začít psát.</p>
</div>
<button class="pill shrink-0" type="button" data-sheet-close>Zavřít</button>
</div>
<p class="mt-5 hidden rounded-2xl px-4 py-3 text-sm" id="search-status" role="status"></p>
<?php if ($openFind && is_string($flash) && $flash !== ''): ?>
<p class="mt-5 rounded-2xl bg-accent/10 px-4 py-3 text-sm text-accent" role="status"><?= e($flash) ?></p>
<?php endif; ?>
<form class="mt-5" id="friend-search-form" method="get" action="<?= e(url('/app/friends')) ?>" data-lookup="<?= e(url('/app/friends/lookup')) ?>">
<div class="relative">
<?= dartiqo_i('search', 'pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-zinc-500') ?>
<input class="h-12! rounded-full! pl-11!" id="friend-query" name="q" type="search" value="<?= e($query) ?>" placeholder="Jméno, username nebo e-mail" autocomplete="off" enterkeyhint="search" aria-controls="friend-results" autofocus>
</div>
</form>
<p class="mt-8 text-[10px] font-semibold tracking-wide text-zinc-500 uppercase" id="search-label">Návrhy</p>
<div class="mt-4 min-h-0 flex-1 overflow-y-auto" id="friend-results" aria-live="polite">
<p class="text-sm text-zinc-500">Načítám hráče…</p>
</div>
</div>
</dialog>
<script>
(() => {
  const dialog = document.getElementById('sheet-find-player');
  const form = document.getElementById('friend-search-form');
  const input = document.getElementById('friend-query');
  const box = document.getElementById('friend-results');
  const label = document.getElementById('search-label');
  const hint = document.getElementById('search-hint');
  if (!dialog || !form || !input || !box) return;
  let timer = 0;
  let seq = 0;
  let ctrl = null;
  form.addEventListener('submit', (event) => event.preventDefault());
  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(search, 200);
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-open-find]')) return;
    window.setTimeout(() => input.focus(), 0);
    search();
  });
  dialog.addEventListener('close', () => {
    const next = new URL(location.href);
    if (!next.searchParams.has('q') && !next.searchParams.has('find')) return;
    next.searchParams.delete('q');
    next.searchParams.delete('find');
    history.replaceState(null, '', next);
  });
  if (dialog.dataset.open === '1' && typeof dialog.showModal === 'function') {
    dialog.showModal();
    input.focus();
    search();
  }
  const status = document.getElementById('search-status');
  box.addEventListener('submit', async (event) => {
    const requestForm = event.target;
    if (!(requestForm instanceof HTMLFormElement)) return;
    event.preventDefault();
    const button = requestForm.querySelector('button');
    if (button) button.disabled = true;
    try {
      const res = await fetch(requestForm.action, {
        method: 'POST',
        body: new FormData(requestForm),
        headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
      });
      const data = await res.json();
      if (!data.ok) {
        showStatus(data.message || 'Žádost se nepovedla.', true);
        if (button) button.disabled = false;
        return;
      }
      const note = document.createElement('span');
      note.className = 'text-xs font-semibold text-zinc-400';
      note.textContent = data.relation === 'accepted' ? 'Kamarádi' : (data.relation === 'incoming' ? 'Čeká' : 'Odesláno');
      requestForm.replaceWith(note);
      showStatus(data.message, false);
      if (data.friendHtml) addFriend(data.friendHtml);
    } catch {
      showStatus('Žádost se nepovedla. Zkus to znovu.', true);
      if (button) button.disabled = false;
    }
  });
  function showStatus(message, failed) {
    if (!status) return;
    status.textContent = message;
    status.className = 'mt-5 rounded-2xl px-4 py-3 text-sm ' + (failed ? 'bg-rose-400/10 text-rose-300' : 'bg-accent/10 text-accent');
  }
  function addFriend(html) {
    let list = document.getElementById('friends-list');
    if (!list) {
      const empty = document.getElementById('friends-empty');
      list = document.createElement('ul');
      list.id = 'friends-list';
      list.className = 'mt-4 flex flex-col gap-2';
      if (empty) empty.replaceWith(list);
      else document.getElementById('friends-title')?.insertAdjacentElement('afterend', list);
    }
    list.insertAdjacentHTML('afterbegin', html);
  }
  async function search() {
    const q = input.value.trim();
    const token = ++seq;
    ctrl?.abort();
    ctrl = new AbortController();
    try {
      const res = await fetch(form.dataset.lookup + '?q=' + encodeURIComponent(q), {
        headers: { Accept: 'application/json' },
        signal: ctrl.signal,
      });
      const data = await res.json();
      if (token !== seq || !data.ok) return;
      if (label) label.textContent = data.label;
      if (hint) hint.textContent = data.hint;
      box.innerHTML = data.html;
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      if (hint) hint.textContent = 'Hledání teď nejde. Zkus to znovu.';
    }
  }
})();
</script>
