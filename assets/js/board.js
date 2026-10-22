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
  const paintHeat = (svg, board, seedKey) => {
    if (!svg) return;
    const xy = (r, a) => [250 + r * Math.cos(a * Math.PI / 180), 250 + r * Math.sin(a * Math.PI / 180)];
    let out = '<circle cx="250" cy="250" r="246" fill="#181818" stroke="#555" stroke-width="3"/>';
    nums.forEach((n, i) => {
      const a = -99 + i * 18, b = a + 18;
      [[16, 119, 1], [119, 132, 3], [132, 190, 1], [190, 204, 2]].forEach(([r1, r2, m]) => {
        out += `<path d="M${xy(r1, a)} L${xy(r2, a)} A${r2},${r2} 0 0 1 ${xy(r2, b)} L${xy(r1, b)} A${r1},${r1} 0 0 0 ${xy(r1, a)}Z" fill="${m === 1 ? (i % 2 ? '#e9dfc5' : '#202020') : (i % 2 ? '#16754c' : '#ba3437')}" stroke="#a6a392" stroke-width=".8"/>`;
      });
    });
    out += '<circle cx="250" cy="250" r="16" fill="#16754c"/><circle cx="250" cy="250" r="7" fill="#ba3437"/>';
    heatDots(board, seedKey).forEach(([x, y]) => {
      out += `<g transform="translate(${x.toFixed(1)} ${y.toFixed(1)})"><circle r="5.5" fill="#09090b"/><circle r="3.2" fill="#faff00"/></g>`;
    });
    nums.forEach((n, i) => {
      const pos = xy(222, -90 + i * 18);
      out += `<text x="${pos[0].toFixed(1)}" y="${pos[1].toFixed(1)}" fill="#fafafa" text-anchor="middle" dominant-baseline="central" font-size="16" font-family="inherit" font-weight="600">${n}</text>`;
    });
    svg.innerHTML = out;
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
      fieldsEl.innerHTML = fields.slice(0, 8).map(f => `<div class="tile flex items-center justify-between px-3 py-2.5 text-sm"><strong class="num">${escapeHtml(String(f[0]))}</strong><span class="text-zinc-500 tabular-nums">${f[1]}×</span></div>`).join('');
    }
  };
  const paintAim = (els, board, aimPick) => {
    if (!els.chart) return aimPick;
    const segs = board.segments || {};
    const bull = (board.bull || [0, 0]).reduce((s, n) => s + (+n || 0), 0);
    const parts = nums.map(n => {
      const row = segs[n] || [0, 0, 0];
      const s = +row[0] || 0, d = +row[1] || 0, t = +row[2] || 0;
      return {n, s, d, t, sum: s + d + t};
    });
    const total = parts.reduce((s, p) => s + p.sum, 0) + bull || 1;
    const maxSum = Math.max(...parts.map(p => p.sum), 1);
    const ranked = [...parts].sort((a, b) => b.sum - a.sum);
    const best = ranked[0], worst = ranked[ranked.length - 1];
    if (aimPick == null) aimPick = best.n;
    if (els.min) els.min.textContent = pct(worst.sum / total * 100);
    if (els.max) els.max.textContent = pct(best.sum / total * 100);
    if (els.bull) els.bull.textContent = pct(bull / total * 100);
    const cx = 180, cy = 180, outer = 124;
    const mix = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
    const bed = depth => {
      const light = [252, 224, 107], mid = [214, 133, 31], dark = [102, 51, 13], t = Math.min(1, Math.max(0, depth));
      const rgb = t < 0.5 ? mix(light, mid, t / 0.5) : mix(mid, dark, (t - 0.5) / 0.5);
      return `rgb(${rgb[0]},${rgb[1]},${rgb[2]})`;
    };
    const polar = (r, deg) => {
      const a = (deg - 90) * Math.PI / 180;
      return [cx + r * Math.cos(a), cy + r * Math.sin(a)];
    };
    const wedge = (index, radius) => {
      const mid = index * 18, start = mid - 8.15, end = mid + 8.15, steps = 10;
      let d = `M${cx} ${cy}`;
      for (let s = 0; s <= steps; s++) {
        const [x, y] = polar(radius, start + (end - start) * s / steps);
        d += ` L${x.toFixed(2)} ${y.toFixed(2)}`;
      }
      return d + ' Z';
    };
    const ring = (index, r0, r1) => {
      const mid = index * 18, start = mid - 8.15, end = mid + 8.15, steps = 8;
      const pt = (r, deg) => polar(r, deg).map(v => v.toFixed(2)).join(' ');
      let d = `M${pt(r1, start)}`;
      for (let s = 1; s <= steps; s++) d += ` L${pt(r1, start + (end - start) * s / steps)}`;
      for (let s = steps; s >= 0; s--) d += ` L${pt(r0, start + (end - start) * s / steps)}`;
      return d + ' Z';
    };
    let svg = '<svg viewBox="0 0 360 360" class="mx-auto block w-full max-w-[440px]" role="group" aria-label="Podíl zásahů podle čísel na terči">';
    parts.forEach((p, i) => {
      const on = p.n === aimPick, fade = on || aimPick == null ? 1 : 0.38;
      const radius = p.sum <= 0 ? outer * 0.18 : outer * (0.36 + 0.64 * p.sum / maxSum);
      svg += `<path d="${wedge(i, radius)}" fill="${p.sum ? bed(0) : 'rgba(255,255,255,.16)'}" opacity="${p.sum ? 0.72 * fade : fade}"/>`;
      if (p.t > 0) {
        const thick = radius * (0.08 + 0.2 * p.t / p.sum), midR = radius * 0.54;
        svg += `<path d="${ring(i, Math.max(0, midR - thick / 2), midR + thick / 2)}" fill="${bed(1)}" opacity="${fade}"/>`;
      }
      if (p.d > 0) {
        const thick = radius * (0.08 + 0.2 * p.d / p.sum);
        svg += `<path d="${ring(i, Math.max(0, radius - thick), radius)}" fill="${bed(0.5)}" opacity="${fade}"/>`;
      }
      if (on) svg += `<path d="${wedge(i, radius)}" fill="none" stroke="#fff" stroke-width="1.6"/>`;
    });
    for (let i = 0; i < 20; i++) {
      const [x, y] = polar(outer, i * 18 - 9);
      svg += `<line x1="${cx}" y1="${cy}" x2="${x.toFixed(1)}" y2="${y.toFixed(1)}" stroke="rgba(255,255,255,.1)" pointer-events="none"/>`;
    }
    for (let step = 1; step <= 4; step++) svg += `<circle cx="${cx}" cy="${cy}" r="${(outer * step / 4).toFixed(1)}" fill="none" stroke="rgba(255,255,255,.16)" pointer-events="none"/>`;
    parts.forEach((p, i) => {
      const [x, y] = polar(outer + 26, i * 18), on = p.n === aimPick, gold = p.n === best.n && p.sum > 0;
      svg += `<text x="${x.toFixed(1)}" y="${y.toFixed(1)}" fill="${on ? '#fff' : gold ? '#e8b03d' : 'rgba(255,255,255,.58)'}" text-anchor="middle" dominant-baseline="central" font-size="12" font-weight="700" pointer-events="none">${p.n}</text>`;
    });
    parts.forEach((p, i) => {
      const on = p.n === aimPick;
      svg += `<path data-aim="${p.n}" tabindex="0" role="button" aria-pressed="${on}" aria-label="${p.n}: ${pct(p.sum / total * 100)}, ${p.s} single, ${p.d} double, ${p.t} triple" d="${wedge(i, outer + 16)}" fill="transparent" class="cursor-pointer"/>`;
    });
    svg += '</svg>';
    els.chart.innerHTML = svg;
    const picked = parts.find(p => p.n === aimPick);
    if (els.detail) els.detail.textContent = picked ? `${picked.n} · S ${picked.s} · D ${picked.d} · T ${picked.t} · ${pct(picked.sum / total * 100)}` : 'Plocha je single, tmavý pruh triple a okraj double. Klepni na výseč pro počty.';
    return aimPick;
  };
  return {paintHeat, paintShares, paintAim};
})();
