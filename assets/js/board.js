const DartBoard = (() => {
  const nums = [20, 1, 18, 4, 13, 6, 10, 15, 2, 17, 3, 19, 7, 16, 8, 11, 14, 9, 12, 5];
  const pct = n => n.toFixed(1).replace('.', ',') + ' %';
  const mulberry32 = a => () => {
    a |= 0;
    a = a + 0x6D2B79F5 | 0;
    let t = Math.imul(a ^ a >>> 15, 1 | a);
    t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
    return ((t ^ t >>> 14) >>> 0) / 4294967296;
  };
  const gauss = rng => {
    let u = 0, v = 0;
    while (!u) u = rng();
    while (!v) v = rng();
    return Math.sqrt(-2 * Math.log(u)) * Math.cos(Math.PI * 2 * v);
  };
  const hash = s => {
    let h = 2166136261;
    for (let i = 0; i < s.length; i++) {
      h ^= s.charCodeAt(i);
      h = Math.imul(h, 16777619);
    }
    return h >>> 0;
  };
  const heatDots = (board, seedKey) => {
    const segs = board.segments || {}, bull = board.bull || [0, 0], bag = [];
    nums.forEach(n => {
      const row = segs[n] || [0, 0, 0];
      [['single', +row[0] || 0], ['double', +row[1] || 0], ['triple', +row[2] || 0]].forEach(([ring, count]) => {
        for (let i = 0; i < count; i++) bag.push([n, ring]);
      });
    });
    for (let i = 0; i < (+bull[0] || 0); i++) bag.push([25, 'bull']);
    for (let i = 0; i < (+bull[1] || 0); i++) bag.push([50, 'bull50']);
    const cap = 140;
    const picked = bag.length <= cap ? bag : Array.from({length: cap}, (_, i) => bag[Math.min(bag.length - 1, Math.floor(i * bag.length / cap))]);
    const rng = mulberry32(hash(String(seedKey)));
    return picked.map(([n, ring]) => {
      if (ring === 'bull' || ring === 'bull50') {
        const ang = rng() * Math.PI * 2;
        const span = ring === 'bull50' ? [1.5, 6] : [7, 14.5];
        const rad = span[0] + rng() * (span[1] - span[0]);
        return [250 + rad * Math.cos(ang), 250 + rad * Math.sin(ang)];
      }
      const i = nums.indexOf(n);
      const spread = n === 20 ? 2.3 : ring === 'triple' ? 2.5 : 4.2;
      const deg = -90 + i * 18 + Math.max(-8.2, Math.min(8.2, gauss(rng) * spread));
      let r0, r1;
      if (ring === 'triple') { r0 = 121; r1 = 130; }
      else if (ring === 'double') { r0 = 191; r1 = 202; }
      else if (rng() < 0.18) { r0 = 28; r1 = 108; }
      else { r0 = 140; r1 = 184; }
      const rad = Math.max(r0, Math.min(r1, (r0 + r1) / 2 + gauss(rng) * ((r1 - r0) * 0.22)));
      const a = deg * Math.PI / 180;
      return [250 + rad * Math.cos(a), 250 + rad * Math.sin(a)];
    });
  };
  const arc = (cx, cy, r0, r1, a0, a1) => {
    const p = (r, a) => (cx + r * Math.cos(a * Math.PI / 180)).toFixed(2) + ' ' + (cy + r * Math.sin(a * Math.PI / 180)).toFixed(2);
    return `M${p(r0, a0)} L${p(r1, a0)} A${r1} ${r1} 0 0 1 ${p(r1, a1)} L${p(r0, a1)} A${r0} ${r0} 0 0 0 ${p(r0, a0)}Z`;
  };
  const densityStops = [[0, [255, 60, 0, 0]], [0.1, [255, 60, 0, 0.22]], [0.32, [255, 120, 0, 0.5]], [0.6, [255, 196, 0, 0.72]], [0.82, [250, 255, 0, 0.82]], [1, [255, 255, 230, 0.92]]];
  const densityColor = t => {
    let i = 0;
    while (i < densityStops.length - 2 && t > densityStops[i + 1][0]) i++;
    const [t0, c0] = densityStops[i], [t1, c1] = densityStops[i + 1], f = Math.min(1, Math.max(0, (t - t0) / (t1 - t0)));
    return c0.map((v, k) => v + (c1[k] - v) * f);
  };
  const densityImage = dots => {
    if (typeof document === 'undefined' || !dots.length) return '';
    const N = 250, k = 500 / N, sigma = 10 / k, reach = Math.ceil(sigma * 3), grid = new Float32Array(N * N);
    dots.forEach(([x, y]) => {
      const cx = x / k, cy = y / k;
      for (let gy = Math.max(0, Math.floor(cy - reach)); gy <= Math.min(N - 1, Math.ceil(cy + reach)); gy++) {
        for (let gx = Math.max(0, Math.floor(cx - reach)); gx <= Math.min(N - 1, Math.ceil(cx + reach)); gx++) {
          const d2 = (gx - cx) ** 2 + (gy - cy) ** 2;
          if (d2 <= reach * reach) grid[gy * N + gx] += Math.exp(-d2 / (2 * sigma * sigma));
        }
      }
    });
    let peak = 0;
    for (let i = 0; i < grid.length; i++) if (grid[i] > peak) peak = grid[i];
    if (!peak) return '';
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = N;
    const ctx = canvas.getContext('2d'), img = ctx.createImageData(N, N);
    for (let i = 0; i < grid.length; i++) {
      const t = Math.pow(grid[i] / peak, 0.5);
      if (t < 0.04) continue;
      const c = densityColor(t);
      img.data.set([c[0], c[1], c[2], Math.round(c[3] * 255)], i * 4);
    }
    ctx.putImageData(img, 0, 0);
    return canvas.toDataURL();
  };
  const paintHeat = (svg, board, seedKey, statsEl, misses = 0) => {
    if (!svg) return;
    const key = 'hb' + hash(String(svg.id || 'heat'));
    const xy = (r, a) => [250 + r * Math.cos(a * Math.PI / 180), 250 + r * Math.sin(a * Math.PI / 180)];
    const dots = heatDots(board, seedKey);
    const segs = board.segments || {};
    const sectorHits = nums.map(n => (segs[n] || [0, 0, 0]).reduce((s, v) => s + (+v || 0), 0));
    const sectorPeak = Math.max(...sectorHits, 1);
    let out = `<defs><radialGradient id="${key}-vig"><stop offset=".78" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".45"/></radialGradient><clipPath id="${key}-clip"><circle cx="250" cy="250" r="246"/></clipPath></defs>`;
    out += '<circle cx="250" cy="250" r="248" fill="#0b0b0d"/><circle cx="250" cy="250" r="247" fill="none" stroke="rgba(255,255,255,.07)"/>';
    out += '<circle cx="250" cy="250" r="204" fill="#101013"/>';
    nums.forEach((n, i) => {
      const a = -99 + i * 18, b = a + 18, odd = i % 2;
      [[16, 119, 1], [119, 132, 3], [132, 190, 1], [190, 204, 2]].forEach(([r1, r2, m]) => {
        const fill = m === 1 ? (odd ? '#1c1c20' : '#141417') : (odd ? '#183528' : '#3d1b20');
        out += `<path d="${arc(250, 250, r1, r2, a, b)}" fill="${fill}" stroke="rgba(255,255,255,.07)" stroke-width="1"/>`;
      });
    });
    out += '<circle cx="250" cy="250" r="16" fill="#183528" stroke="rgba(255,255,255,.08)"/><circle cx="250" cy="250" r="7" fill="#3d1b20" stroke="rgba(255,255,255,.1)"/>';
    out += `<circle cx="250" cy="250" r="204" fill="url(#${key}-vig)" pointer-events="none"/>`;
    const heat = densityImage(dots);
    if (heat) out += `<image href="${heat}" x="0" y="0" width="500" height="500" clip-path="url(#${key}-clip)" preserveAspectRatio="none" pointer-events="none" style="mix-blend-mode:screen"/>`;
    const bull = board.bull || [0, 0];
    const onBoard = sectorHits.reduce((s, v) => s + v, 0) + (+bull[0] || 0) + (+bull[1] || 0);
    const total = onBoard + (+misses || 0) || 1;
    const ti = sectorHits.indexOf(Math.max(...sectorHits)), target = nums[ti], hasHits = sectorHits[ti] > 0;
    const side = d => [nums[(ti + d + 20) % 20], nums[(ti + 2 * d + 20) % 20]];
    const sideHits = d => sectorHits[(ti + d + 20) % 20] + sectorHits[(ti + 2 * d + 20) % 20];
    const left = sideHits(-1), right = sideHits(1);
    const share = v => Math.round(v / total * 100);
    const badge = (deg, r, label, on) => {
      const [x, y] = xy(r, deg), w = label.length * 7.4 + 16;
      return `<g transform="translate(${x.toFixed(1)} ${y.toFixed(1)})"><rect x="${-w / 2}" y="-12" width="${w}" height="24" rx="12" fill="${on ? '#faff00' : 'rgba(9,9,11,.82)'}" stroke="${on ? '#faff00' : 'rgba(255,255,255,.28)'}"/><text fill="${on ? '#09090b' : '#f4f4f5'}" text-anchor="middle" dominant-baseline="central" font-size="12.5" font-weight="700" font-family="inherit">${label}</text></g>`;
    };
    if (hasHits) {
      const a = -99 + ti * 18;
      [[-1, a - 36], [1, a + 18]].forEach(([, start]) => {
        out += `<path d="${arc(250, 250, 16, 204, start, start + 36)}" fill="rgba(255,255,255,.035)" stroke="rgba(255,255,255,.32)" stroke-width="1.2" stroke-dasharray="4 4" pointer-events="none"/>`;
      });
      out += `<path d="${arc(250, 250, 16, 204, a, a + 18)}" fill="rgba(250,255,0,.05)" stroke="#faff00" stroke-width="2" stroke-linejoin="round" pointer-events="none"/>`;
    }
    out += `<g pointer-events="none">${dots.map(([x, y]) => `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="2.1" fill="#fff" fill-opacity=".9" stroke="#09090b" stroke-opacity=".55" stroke-width=".8"/>`).join('')}</g>`;
    if (hasHits) {
      const deg = -90 + ti * 18;
      out += `<g pointer-events="none">${badge(deg - 27, 165, share(left) + ' %')}${badge(deg + 27, 165, share(right) + ' %')}${badge(deg, 72, share(sectorHits[ti]) + ' %', true)}</g>`;
    }
    nums.forEach((n, i) => {
      const pos = xy(226, -90 + i * 18), hot = sectorHits[i] / sectorPeak;
      const fill = hasHits && i === ti ? '#faff00' : hot > 0.15 ? '#e4e4e7' : hot > 0 ? '#a1a1aa' : '#52525b';
      out += `<text x="${pos[0].toFixed(1)}" y="${pos[1].toFixed(1)}" fill="${fill}" text-anchor="middle" dominant-baseline="central" font-size="15" font-family="inherit" font-weight="700">${n}</text>`;
    });
    svg.innerHTML = out;
    if (!statsEl) return;
    if (!hasHits) {
      statsEl.innerHTML = '<p class="tile p-4 text-xs text-zinc-500">Zatím tu nejsou žádné zásahy.</p>';
      return;
    }
    const row = segs[target] || [0, 0, 0], tt = +row[2] || 0, td = +row[1] || 0;
    const ln = side(-1).join(' a '), rn = side(1).join(' a ');
    const lean = left + right ? (right - left) / (left + right) : 0;
    const diff = Math.abs(share(right) - share(left));
    const verdict = !(left + right) ? 'Kolem cíle zatím nic neuletělo.'
      : diff < 3 ? 'Do stran ti šipky uletují vyrovnaně, žádná strana nepřevažuje.'
      : `Častěji ti šipky uletí k ${lean > 0 ? rn : ln}${diff >= 8 ? ' – tady je co ladit' : ''}.`;
    const zone = sectorHits[ti] + left + right;
    const triples = nums.reduce((s, n) => s + (+(segs[n] || [])[2] || 0), 0);
    const tile = (label, value, note, tone = '') => `<div class="tile p-3"><span class="eyebrow tracking-[.1em]">${label}</span><strong class="num mt-1 block text-xl ${tone}">${value}</strong><small class="mt-0.5 block text-[11px] leading-snug text-zinc-500">${note}</small></div>`;
    statsEl.innerHTML = `<div class="tile flex items-center gap-3 p-4"><span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent font-display text-lg font-bold text-black">${target}</span><span class="min-w-0 flex-1"><small class="eyebrow tracking-[.1em]">Hlavní cíl</small><strong class="block text-sm text-zinc-100">Do ${target} letí ${share(sectorHits[ti])} % tvých šipek</strong><small class="block text-[11px] text-zinc-500">Z toho ${tt}× trojka a ${td}× double</small></span><b class="num text-2xl text-accent">${share(sectorHits[ti])} %</b></div>`
      + `<div class="tile p-4"><div class="flex items-center justify-between text-[11px] text-zinc-400"><span>← ${ln}</span><span class="eyebrow tracking-[.1em]">Kam uhýbáš</span><span>${rn} →</span></div><div class="mt-2 flex items-center gap-3"><b class="num w-10 text-sm ${lean < 0 && diff >= 3 ? 'text-accent' : 'text-zinc-300'}">${share(left)} %</b><span class="relative h-2 flex-1 overflow-hidden rounded-full bg-white/[.07]"><i class="absolute inset-y-0 rounded-full bg-accent" style="${lean < 0 ? 'right:50%' : 'left:50%'};width:${Math.round(Math.abs(lean) * 50)}%"></i><i class="absolute inset-y-[-2px] left-1/2 w-0.5 -translate-x-1/2 bg-zinc-200"></i></span><b class="num w-10 text-right text-sm ${lean > 0 && diff >= 3 ? 'text-accent' : 'text-zinc-300'}">${share(right)} %</b></div><p class="mt-2.5 text-xs text-zinc-300">${verdict}</p></div>`
      + `<div class="grid grid-cols-3 gap-2">${tile('Kolem cíle', share(zone) + ' %', `${target} a dvě čísla z každé strany`)}${tile('Trojky', share(triples) + ' %', 'všech hodů skončí v trojce')}${tile('Mimo terč', share(+misses || 0) + ' %', 'šipek netrefí žádné pole', (+misses || 0) / total > 0.1 ? 'text-rose-400' : '')}</div>`;
  };
  const paintShares = (sharesEl, fieldsEl, board, misses) => {
    const segs = board.segments || {};
    let single = 0, double = 0, triple = 0;
    nums.forEach(n => {
      const row = segs[n] || [0, 0, 0];
      single += +row[0] || 0;
      double += +row[1] || 0;
      triple += +row[2] || 0;
    });
    const bull = (board.bull || [0, 0]).reduce((s, n) => s + (+n || 0), 0);
    const rows = [['Triple', triple, '#f24747'], ['Double', double, '#4ade80'], ['Single', single, '#5b86e5'], ['Bull', bull, '#f5a524'], ['Vedle', +misses || 0, '#8e8e93']];
    const total = rows.reduce((s, r) => s + r[1], 0) || 1;
    if (sharesEl) {
      sharesEl.innerHTML = rows.map(r => {
        const rate = Math.round(r[1] / total * 100);
        return `<div class="grid grid-cols-[4.2rem_1fr_2.6rem] items-center gap-3 text-sm"><span class="text-zinc-400">${r[0]}</span><span class="block h-1.5 overflow-hidden rounded-full bg-white/[.07]"><i class="block h-full rounded-full" style="width:${rate}%;background:${r[2]}"></i></span><b class="num text-right">${rate} %</b></div>`;
      }).join('');
    }
    const fields = [];
    nums.forEach(n => {
      const row = segs[n] || [0, 0, 0];
      if (row[0]) fields.push(['S' + n, +row[0]]);
      if (row[1]) fields.push(['D' + n, +row[1]]);
      if (row[2]) fields.push(['T' + n, +row[2]]);
    });
    const bullRow = board.bull || [0, 0];
    if (bullRow[0]) fields.push(['25', +bullRow[0]]);
    if (bullRow[1]) fields.push(['BULL', +bullRow[1]]);
    if (misses) fields.push(['Vedle', +misses]);
    fields.sort((a, b) => b[1] - a[1] || String(a[0]).localeCompare(String(b[0]), 'cs'));
    if (fieldsEl) {
      const top = fields.slice(0, 6), max = top.length ? top[0][1] : 1;
      fieldsEl.innerHTML = top.length ? top.map((f, i) => `<div class="grid grid-cols-[.75rem_2.75rem_1fr_2.5rem] items-center gap-2.5 text-sm"><span class="num text-[11px] text-zinc-600">${i + 1}</span><strong class="num ${i ? 'text-zinc-100' : 'text-accent'}">${escapeHtml(String(f[0]))}</strong><span class="block h-1.5 overflow-hidden rounded-full bg-white/[.07]"><i class="block h-full rounded-full ${i ? 'bg-zinc-400/70' : 'bg-accent'}" style="width:${Math.round(f[1] / max * 100)}%"></i></span><span class="text-right text-zinc-400 tabular-nums">${f[1]}×</span></div>`).join('') : '<p class="text-xs text-zinc-500">Zatím žádné zásahy.</p>';
    }
  };
  const heatStops = [[34, 30, 30], [92, 38, 22], [214, 92, 18], [250, 200, 0], [250, 255, 0], [254, 255, 214]];
  const heatColor = t => {
    const x = Math.min(1, Math.max(0, t)) * (heatStops.length - 1), i = Math.min(heatStops.length - 2, Math.floor(x)), f = x - i;
    const c = heatStops[i].map((v, k) => Math.round(v + (heatStops[i + 1][k] - v) * f));
    return `rgb(${c[0]},${c[1]},${c[2]})`;
  };
  const paintAim = (els, board, aimPick) => {
    if (!els.chart) return aimPick;
    const segs = board.segments || {};
    const bullRow = board.bull || [0, 0];
    const bull = bullRow.reduce((s, n) => s + (+n || 0), 0);
    const parts = nums.map(n => {
      const row = segs[n] || [0, 0, 0];
      const s = +row[0] || 0, d = +row[1] || 0, t = +row[2] || 0;
      return {n, s, d, t, sum: s + d + t};
    });
    const total = parts.reduce((s, p) => s + p.sum, 0) + bull || 1;
    const ranked = [...parts].sort((a, b) => b.sum - a.sum);
    const best = ranked[0], worst = ranked[ranked.length - 1];
    if (aimPick == null) aimPick = best.n;
    if (els.min) els.min.textContent = pct(worst.sum / total * 100);
    if (els.max) els.max.textContent = pct(best.sum / total * 100);
    if (els.bull) els.bull.textContent = pct(bull / total * 100);
    const cells = [];
    parts.forEach(p => {
      cells.push(p.s * 0.82, p.s * 0.18, p.t, p.d);
    });
    cells.push(+bullRow[0] || 0, +bullRow[1] || 0);
    const peak = Math.max(...cells, 1);
    const tone = v => v > 0 ? heatColor(0.1 + 0.9 * Math.pow(v / peak, 0.45)) : 'rgb(24,24,27)';
    const ink = v => v > 0 && Math.pow(v / peak, 0.45) > 0.6 ? '#09090b' : 'rgba(255,255,255,.7)';
    const C = 200, R = [13, 99, 110, 158, 170];
    let svg = `<svg viewBox="0 0 400 400" class="mx-auto block w-full max-w-[460px]" role="group" aria-label="Heatmapa zásahů na terči"><circle cx="${C}" cy="${C}" r="196" fill="#0c0c0e" stroke="rgba(255,255,255,.06)"/>`;
    parts.forEach((p, i) => {
      const a = -99 + i * 18, b = a + 18, on = p.n === aimPick, dim = on ? 1 : 0.88;
      [[R[0], R[1], p.s * 0.18], [R[1], R[2], p.t], [R[2], R[3], p.s * 0.82], [R[3], R[4], p.d]].forEach(([r0, r1, v]) => {
        svg += `<path d="${arc(C, C, r0, r1, a, b)}" fill="${tone(v)}" opacity="${dim}" stroke="#0c0c0e" stroke-width="1.6"/>`;
      });
    });
    svg += `<circle cx="${C}" cy="${C}" r="${R[0]}" fill="${tone(+bullRow[0] || 0)}" stroke="#0c0c0e" stroke-width="1.6"/><circle cx="${C}" cy="${C}" r="6" fill="${tone(+bullRow[1] || 0)}" stroke="#0c0c0e" stroke-width="1.2"/>`;
    const picked = parts.find(p => p.n === aimPick);
    if (picked) {
      const i = nums.indexOf(picked.n), a = -99 + i * 18;
      svg += `<path d="${arc(C, C, R[0], R[4], a, a + 18)}" fill="none" stroke="#fff" stroke-width="2.2" stroke-linejoin="round" pointer-events="none"/>`;
      const [tx, ty] = [C + 140 * Math.cos((a + 9) * Math.PI / 180), C + 140 * Math.sin((a + 9) * Math.PI / 180)];
      svg += `<text x="${tx.toFixed(1)}" y="${ty.toFixed(1)}" fill="${ink(picked.s * 0.82)}" text-anchor="middle" dominant-baseline="central" font-size="10" font-weight="700" pointer-events="none">${Math.round(picked.sum / total * 100)}%</text>`;
    }
    parts.forEach((p, i) => {
      const deg = -90 + i * 18, x = C + 184 * Math.cos(deg * Math.PI / 180), y = C + 184 * Math.sin(deg * Math.PI / 180);
      const on = p.n === aimPick, top = p.n === best.n && p.sum > 0;
      svg += `<text x="${x.toFixed(1)}" y="${y.toFixed(1)}" fill="${on ? '#fff' : top ? '#faff00' : '#71717a'}" text-anchor="middle" dominant-baseline="central" font-size="${on ? 14 : 12}" font-weight="700" pointer-events="none">${p.n}</text>`;
    });
    parts.forEach((p, i) => {
      const on = p.n === aimPick, a = -99 + i * 18;
      svg += `<path data-aim="${p.n}" tabindex="0" role="button" aria-pressed="${on}" aria-label="${p.n}: ${pct(p.sum / total * 100)}, ${p.s} single, ${p.d} double, ${p.t} triple" d="${arc(C, C, R[0], 196, a, a + 18)}" fill="transparent" class="cursor-pointer outline-none"/>`;
    });
    svg += '</svg>';
    els.chart.innerHTML = svg;
    if (els.detail) {
      if (!picked) {
        els.detail.textContent = 'Klepni na číslo na terči pro počty.';
      } else {
        const max = Math.max(picked.s, picked.d, picked.t, 1);
        const bar = (label, v, color) => `<div class="grid grid-cols-[3.5rem_1fr_2.5rem] items-center gap-3"><span class="text-zinc-400">${label}</span><span class="block h-1.5 overflow-hidden rounded-full bg-white/[.07]"><i class="block h-full rounded-full" style="width:${Math.round(v / max * 100)}%;background:${color}"></i></span><b class="num text-right text-zinc-100">${v}</b></div>`;
        els.detail.innerHTML = `<div class="flex items-center justify-between gap-3"><span class="flex items-center gap-2.5"><span class="grid size-9 place-items-center rounded-xl bg-accent text-sm font-bold text-black">${picked.n}</span><span><strong class="block text-sm text-zinc-100">Číslo ${picked.n}</strong><small class="block text-[11px] text-zinc-500">${picked.sum} zásahů</small></span></span><b class="num text-xl text-accent">${pct(picked.sum / total * 100)}</b></div><div class="mt-3 flex flex-col gap-2">${bar('Single', picked.s, '#a1a1aa')}${bar('Double', picked.d, '#4ade80')}${bar('Triple', picked.t, '#f87171')}</div>`;
      }
    }
    return aimPick;
  };
  return {paintHeat, paintShares, paintAim};
})();
