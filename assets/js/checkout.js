// Stejné zavření a sehrávky jako Checkout v aplikaci (double / master / straight).
(function (root) {
  const label = (segment, multiplier) => segment === 25 ? (multiplier === 2 ? 'BULL' : '25') : (multiplier === 3 ? 'T' : multiplier === 2 ? 'D' : 'S') + segment;
  const scoreOf = label => label === 'BULL' ? 50 : label === '25' ? 25 : +label.slice(1) * ({S: 1, D: 2, T: 3}[label[0]] || 1);
  const segmentOf = label => label === 'BULL' || label === '25' ? 25 : +label.slice(1);
  const multOf = label => label === 'BULL' ? 2 : label === '25' ? 1 : ({S: 1, D: 2, T: 3}[label[0]] || 1);
  const preferredDoubles = [20, 16, 8, 4, 2, 18, 12, 10, 6, 14, 5, 9, 11, 13, 15, 17, 19, 7, 3, 1, 25];
  const preferredSetups = [20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10].map(s => label(s, 3))
    .concat(['BULL', '25'])
    .concat([...Array(20)].map((_, i) => label(20 - i, 1)))
    .concat([9, 8, 7, 6, 5, 4, 3, 2, 1].map(s => label(s, 3)))
    .concat([20, 16, 8, 4, 2, 18, 12, 10, 6, 14].map(s => label(s, 2)));
  const normalTrebles = [20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10];
  const favouriteFinish = new Set([20, 16, 8, 12, 10, 4, 18]);
  const favouriteLeaves = [40, 32, 50, 36, 24, 16, 20, 8, 12, 28, 18, 38, 34, 30, 26, 22, 14, 10, 6, 4, 2];
  const classicLeaves = [100, 80, 60, 110, 120, 81, 85, 90, 96, 64, 68, 76, 84, 41, 45, 61, 65, 70, 130, 140, 150];
  const setupAims = (() => {
    const seen = new Set();
    const aims = [];
    const add = (segment, multiplier) => {
      const dart = label(segment, multiplier);
      if (seen.has(dart)) return;
      seen.add(dart);
      aims.push(dart);
    };
    [20, 19, 18, 17, 16, 15].forEach(s => add(s, 3));
    for (let s = 20; s >= 1; s--) add(s, 1);
    add(25, 2); add(25, 1);
    for (let s = 14; s >= 1; s--) add(s, 3);
    [20, 16, 8, 4, 2, 18, 12, 10, 6, 14].forEach(s => add(s, 2));
    for (let s = 1; s <= 20; s++) add(s, 2);
    return aims;
  })();
  const rules = ['double', 'straight', 'master'];
  const ruleOf = rule => rules.includes(rule) ? rule : 'double';
  const finishing = rule => {
    const doubles = preferredDoubles.map(s => label(s, 2));
    if (rule === 'master') return doubles.concat([...Array(20)].map((_, i) => label(20 - i, 3)));
    if (rule === 'straight') return doubles.concat(preferredSetups);
    return doubles;
  };
  const endsByRule = Object.fromEntries(rules.map(rule => [rule, finishing(rule)]));
  const same = (a, b) => a.length === b.length && a.every((dart, i) => dart === b[i]);
  const routeCache = new Map();

  function canFinish(score, darts, rule) {
    return checkoutRoute(score, darts, rule) !== null;
  }

  function singleMissStillFinishable(dart, score, rule, dartsLeft) {
    if (dartsLeft <= 0 || multOf(dart) < 2) return true;
    const floor = rule === 'straight' ? 1 : 2;
    const segment = segmentOf(dart);
    const leave = segment === 25 || (segment >= 1 && segment <= 20) ? score - (segment === 25 ? 25 : segment) : null;
    if (leave === null) return true;
    if (leave === 0) return rule === 'straight';
    if (leave < floor) return false;
    return canFinish(leave, dartsLeft, rule);
  }

  function paired(score, ends, setups) {
    for (const end of ends) {
      const need = score - scoreOf(end);
      if (need > 0 && setups.includes(labelFromScore(need, setups))) {
        const setup = setups.find(dart => scoreOf(dart) === need);
        if (setup) return [setup, end];
      }
    }
    return null;
  }

  function labelFromScore(need, setups) {
    return setups.find(dart => scoreOf(dart) === need) || '';
  }

  function twoDart(score, ends) {
    const straight = paired(score, ends, preferredSetups);
    if (straight && multOf(straight[0]) === 2 && segmentOf(straight[0]) <= 20) {
      const normal = trebleOntoFavouriteDouble(score, ends);
      if (normal) return normal;
    }
    return straight;
  }

  function trebleOntoFavouriteDouble(score, ends) {
    for (const segment of normalTrebles) {
      const treble = label(segment, 3);
      const left = score - scoreOf(treble);
      if (left <= 0) continue;
      const end = ends.find(dart => scoreOf(dart) === left && multOf(dart) === 2 && favouriteFinish.has(segmentOf(dart)));
      if (end) return [treble, end];
    }
    return null;
  }

  function computeRoute(score, darts, rule) {
    if (score <= 0 || darts <= 0 || score > 180) return null;
    const ends = endsByRule[rule];
    for (const end of ends) if (scoreOf(end) === score) return [end];
    if (darts >= 2) {
      const two = twoDart(score, ends);
      if (two) return two;
    }
    if (darts >= 3) {
      if (score === 135 && rule === 'double') {
        const rest = twoDart(85, ends);
        if (rest) return ['BULL', ...rest];
      }
      let fallback = null;
      for (const first of preferredSetups) {
        const left = score - scoreOf(first);
        if (left <= 0) continue;
        const end = ends.find(dart => scoreOf(dart) === left);
        const rest = end ? [end] : twoDart(left, ends);
        if (!rest) continue;
        const path = [first, ...rest];
        if (!fallback) fallback = path;
        if (segmentOf(first) <= 20 && singleMissStillFinishable(first, score, rule, darts - 1)) return path;
      }
      return fallback;
    }
    return null;
  }

  function checkoutRoute(score, darts, rule) {
    rule = ruleOf(rule);
    darts = Math.max(0, darts | 0);
    score = score | 0;
    const key = rule + '|' + darts + '|' + score;
    if (routeCache.has(key)) {
      const hit = routeCache.get(key);
      return hit ? hit.slice() : null;
    }
    const found = computeRoute(score, darts, rule);
    routeCache.set(key, found);
    return found ? found.slice() : null;
  }

  function checkoutAlternatives(score, darts, rule, limit) {
    rule = ruleOf(rule);
    darts = darts | 0;
    score = score | 0;
    limit = limit || 3;
    if (limit <= 0 || !canFinish(score, darts, rule)) return [];
    const found = [];
    const add = path => {
      if (found.length >= limit || !path || path.reduce((sum, dart) => sum + scoreOf(dart), 0) !== score) return;
      if (found.some(item => same(item, path))) return;
      found.push(path.slice());
    };
    add(checkoutRoute(score, darts, rule));
    const ends = endsByRule[rule];
    if (darts >= 1) for (const end of ends) if (scoreOf(end) === score) add([end]);
    if (darts >= 2) {
      for (const end of ends) {
        const need = score - scoreOf(end);
        if (need <= 0) continue;
        for (const setup of preferredSetups) if (scoreOf(setup) === need) add([setup, end]);
      }
    }
    if (darts >= 3) {
      for (const end of ends) {
        if (found.length >= limit) break;
        const rest = score - scoreOf(end);
        if (rest <= scoreOf(end)) continue;
        for (const first of preferredSetups.slice(0, 24)) {
          if (found.length >= limit || scoreOf(first) >= rest) continue;
          const midNeed = rest - scoreOf(first);
          const second = preferredSetups.find(dart => scoreOf(dart) === midNeed);
          if (second) add([first, second, end]);
        }
      }
    }
    return found;
  }

  const leavesByRule = {};
  function checkoutableLeaves(rule) {
    if (!leavesByRule[rule]) leavesByRule[rule] = [...Array(170)].map((_, i) => i + 1).filter(score => checkoutRoute(score, 3, rule));
    return leavesByRule[rule];
  }

  function scoringPath(need, darts, cache) {
    if (need === 0) return [];
    const key = (need << 2) | darts;
    if (Object.prototype.hasOwnProperty.call(cache.found, key)) return cache.found[key];
    if (cache.absent.has(key)) return null;
    const path = findScoringPath(need, darts, cache);
    if (path) cache.found[key] = path;
    else cache.absent.add(key);
    return path;
  }

  function findScoringPath(need, darts, cache) {
    if (darts <= 0 || need <= 0 || need > 60 * darts) return null;
    const single = setupAims.find(dart => scoreOf(dart) === need);
    if (single) return [single];
    if (darts <= 1) return null;
    for (const first of setupAims) {
      const firstScore = scoreOf(first);
      if (firstScore >= need) continue;
      const rest = scoringPath(need - firstScore, darts - 1, cache);
      if (rest) return [first, ...rest];
    }
    return null;
  }

  function leaveLabel(leave, rule) {
    const dart = checkoutRoute(leave, 1, rule);
    if (!dart) return String(leave);
    if (dart[0] === 'BULL') return 'Bull';
    return dart[0];
  }

  function insurance(dart, score, floor, rule) {
    const segment = segmentOf(dart);
    if (segment > 20) return 0;
    let bonus = 0;
    const multiplier = multOf(dart);
    if (multiplier >= 2) {
      const single = score - segment;
      if (single >= floor && checkoutRoute(single, 3, rule)) bonus += 220;
    }
    if (multiplier === 3) {
      const double = score - segment * 2;
      if (double >= floor && checkoutRoute(double, 3, rule)) bonus += 80;
    }
    return bonus;
  }

  function setupQuality(path, leave, score, rule, floor) {
    let quality;
    const favourite = favouriteLeaves.indexOf(leave);
    const classic = classicLeaves.indexOf(leave);
    if (favourite >= 0) quality = favourite * 55;
    else if (classic >= 0) quality = 640 + classic * 18;
    else {
      const finish = checkoutRoute(leave, 3, rule);
      if (finish) {
        quality = 980 + finish.length * 120;
        const end = finish[finish.length - 1];
        if (multOf(end) === 2 || segmentOf(end) === 25) quality += (preferredDoubles.indexOf(segmentOf(end)) < 0 ? 24 : preferredDoubles.indexOf(segmentOf(end))) * 8;
      } else quality = 5000;
    }
    if (leave <= 4) quality += 500;
    path.forEach((dart, index) => {
      const aim = setupAims.indexOf(dart);
      quality += (aim < 0 ? 100 : aim) * (index === 0 ? 14 : 4);
    });
    quality += (path.length - 1) * 35;
    if (path[0]) quality -= insurance(path[0], score, floor, rule);
    return quality;
  }

  function checkoutSetup(score, darts, rule) {
    rule = ruleOf(rule);
    darts = darts | 0;
    score = score | 0;
    if (darts < 1 || darts > 3 || score < 2 || score > 180) return null;
    if (checkoutRoute(score, darts, rule)) return null;
    const floor = rule === 'straight' ? 1 : 2;
    if (score <= floor) return null;
    const cache = {found: {}, absent: new Set()};
    let best = null;
    for (const leave of checkoutableLeaves(rule)) {
      if (leave < floor || leave >= score) continue;
      const path = scoringPath(score - leave, darts, cache);
      if (!path || !path.length) continue;
      const quality = setupQuality(path, leave, score, rule, floor);
      if (!best || quality < best.quality) best = {path, leave, quality};
    }
    if (!best) return null;
    return {darts: best.path.slice(), leave: leaveLabel(best.leave, rule)};
  }

  root.checkoutRoute = checkoutRoute;
  root.checkoutAlternatives = checkoutAlternatives;
  root.checkoutSetup = checkoutSetup;
})(typeof globalThis !== 'undefined' ? globalThis : window);
