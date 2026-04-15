// my-roster-v2.js - Tabela + Quinteto Titular
const api = async (path, options = {}) => {
  const res = await fetch(`/api/${path}`, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  let body = {};
  try { body = await res.json(); } catch {}
  if (!res.ok) throw body;
  return body;
};

function getOvrColor(ovr) {
  if (ovr >= 95) return '#00ff00';
  if (ovr >= 89) return '#00dd00';
  if (ovr >= 84) return '#ffff00';
  if (ovr >= 79) return '#ffd700';
  if (ovr >= 72) return '#ff9900';
  return '#ff4444';
}

function getPlayerPhotoUrl(player) {
  let customPhoto = (player.foto_adicional || '').toString().trim();
  if (customPhoto) {
    customPhoto = customPhoto.replace(/\\/g, '/');
    if (/^data:image\//i.test(customPhoto) || /^https?:\/\//i.test(customPhoto)) {
      return customPhoto;
    }
    return `/${customPhoto.replace(/^\/+/, '')}`;
  }
  return player.nba_player_id
    ? `https://cdn.nba.com/headshots/nba/latest/1040x760/${player.nba_player_id}.png`
    : `https://ui-avatars.com/api/?name=${encodeURIComponent(player.name)}&background=121212&color=f17507&rounded=true&bold=true`;
}

function convertToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

function normalizeRoleKey(role) {
  const normalized = (role || '').toString().trim().toLowerCase();
  if (normalized === 'titular') return 'Titular';
  if (normalized === 'banco') return 'Banco';
  if (normalized === 'g-league' || normalized === 'gleague' || normalized === 'g league') return 'G-League';
  return 'Outro';
}

const roleOrder = { 'Titular': 0, 'Banco': 1, 'Outro': 2, 'G-League': 3 };
const starterPositionOrder = { PG: 0, SG: 1, SF: 2, PF: 3, C: 4 };

let allPlayers = [];
let currentSort = { field: 'role', ascending: true };
let currentSearch = '';
let currentRoleFilter = '';
let editPhotoFile = null;
let pendingWaivePlayerId = null;
let dragContext = null;

const DEFAULT_FA_LIMITS = { waiversUsed: 0, waiversMax: 3, signingsUsed: 0, signingsMax: 3 };
let currentFALimits = { ...DEFAULT_FA_LIMITS };

// --- LOGICA DA IA DE MELHORIAS ---
function generateAIAnalysis() {
  if (allPlayers.length === 0) {
    alert('Voce precisa ter jogadores no elenco para a IA analisar!');
    return;
  }

  const aiModalEl = document.getElementById('aiAnalysisModal');
  if (!aiModalEl) return;
  const aiModal = new bootstrap.Modal(aiModalEl);

  const loadingEl = document.getElementById('ai-loading');
  const resultsEl = document.getElementById('ai-results');
  if (loadingEl) loadingEl.style.display = 'block';
  if (resultsEl) resultsEl.style.display = 'none';

  aiModal.show();

  setTimeout(() => {
    const strengths = [];
    const weaknesses = [];

    const starters = allPlayers.filter(p => normalizeRoleKey(p.role) === 'Titular');

    const positionCounts = { PG: 0, SG: 0, SF: 0, PF: 0, C: 0 };
    allPlayers.forEach(p => {
      if (positionCounts[p.position] !== undefined) positionCounts[p.position]++;
    });

    const missingPositions = [];
    const overloadedPositions = [];

    Object.entries(positionCounts).forEach(([pos, count]) => {
      if (count < 2) missingPositions.push(pos);
      else if (count > 4) overloadedPositions.push(pos);
    });

    if (missingPositions.length > 0) {
      weaknesses.push(`<strong>Garrafao ou Perimetro Desfalcado:</strong> Falta profundidade nas posicoes <b>${missingPositions.join(', ')}</b> (menos de 2). Busque reforcos.`);
    }
    if (overloadedPositions.length > 0) {
      weaknesses.push(`<strong>Congestionamento:</strong> Excesso de jogadores nas posicoes <b>${overloadedPositions.join(', ')}</b>. Considere usar alguns como moeda de troca.`);
    }
    if (missingPositions.length === 0 && overloadedPositions.length === 0 && allPlayers.length >= 10) {
      strengths.push('<strong>Rotacao Equilibrada:</strong> Seu elenco tem excelente profundidade tatica nas 5 posicoes da quadra.');
    }

    const bestPlayer = [...allPlayers].sort((a, b) => Number(b.ovr) - Number(a.ovr))[0];
    if (bestPlayer && Number(bestPlayer.ovr) >= 89) {
      strengths.push(`<strong>Estrela da Franquia:</strong> ${bestPlayer.name} (${bestPlayer.ovr} OVR) e um jogador de elite para carregar a equipe.`);
    } else if (bestPlayer) {
      weaknesses.push(`<strong>Falta de um Astro:</strong> Seu melhor jogador e ${bestPlayer.name} (${bestPlayer.ovr} OVR). O time precisa de um Franchise Player (89+).`);
    }

    if (starters.length > 0) {
      const weakStarter = [...starters].sort((a, b) => Number(a.ovr) - Number(b.ovr))[0];
      if (weakStarter && Number(weakStarter.ovr) < 80) {
        weaknesses.push(`<strong>Ponto Fraco no Quinteto:</strong> A posicao ${weakStarter.position} com ${weakStarter.name} (${weakStarter.ovr} OVR) e o elo mais fraco dos titulares.`);
      } else {
        strengths.push('<strong>Quinteto Solido:</strong> Todos os seus titulares tem 80+ de OVR. A fundacao do time e muito forte!');
      }
    } else {
      weaknesses.push('<strong>Rotacao Indefinida:</strong> Voce nao definiu seus titulares corretamente.');
    }

    const agingPlayers = allPlayers.filter(p => Number(p.age) >= 33);
    const youngTalents = allPlayers.filter(p => Number(p.age) <= 23 && Number(p.ovr) >= 79);

    if (agingPlayers.length >= 3) {
      weaknesses.push(`<strong>Elenco Envelhecido:</strong> Voce tem ${agingPlayers.length} jogadores com 33+ anos. Cuidado com a queda drastica de OVR na proxima temporada.`);
    } else if (agingPlayers.length > 0) {
      const bestVet = [...agingPlayers].sort((a, b) => Number(b.ovr) - Number(a.ovr))[0];
      if (bestVet) {
        weaknesses.push(`<strong>Risco de Regressao:</strong> Fique de olho em veteranos como ${bestVet.name} (${bestVet.age} anos). Eles tendem a perder atributos.`);
      }
    }

    if (youngTalents.length > 0) {
      const topYoung = youngTalents[0];
      strengths.push(`<strong>Futuro Garantido:</strong> ${topYoung.name} (${topYoung.age} anos, ${topYoung.ovr} OVR) tem enorme potencial de evolucao.`);
    }

    const strengthsHtml = strengths.length > 0
      ? strengths.map(s => `<li class="mb-2">${s}</li>`).join('')
      : '<li>Nenhum destaque claro encontrado.</li>';
    const weaknessesHtml = weaknesses.length > 0
      ? weaknesses.map(w => `<li class="mb-2">${w}</li>`).join('')
      : '<li>Seu time esta perfeito!</li>';

    const strengthsEl = document.getElementById('ai-strengths');
    const weaknessesEl = document.getElementById('ai-weaknesses');
    if (strengthsEl) strengthsEl.innerHTML = strengthsHtml;
    if (weaknessesEl) weaknessesEl.innerHTML = weaknessesHtml;

    if (loadingEl) loadingEl.style.display = 'none';
    if (resultsEl) resultsEl.style.display = 'block';
  }, 2000);
}

async function loadFreeAgencyLimits() {
  if (!window.__TEAM_ID__) return;
  try {
    const data = await api('free-agency.php?action=limits');
    currentFALimits = {
      waiversUsed: Number.isFinite(data.waivers_used) ? data.waivers_used : 0,
      waiversMax: Number.isFinite(data.waivers_max) && data.waivers_max > 0 ? data.waivers_max : DEFAULT_FA_LIMITS.waiversMax,
      signingsUsed: Number.isFinite(data.signings_used) ? data.signings_used : 0,
      signingsMax: Number.isFinite(data.signings_max) && data.signings_max > 0 ? data.signings_max : DEFAULT_FA_LIMITS.signingsMax,
    };
  } catch (err) {
    console.warn('Não foi possível carregar limites de FA:', err);
    currentFALimits = { ...DEFAULT_FA_LIMITS };
  }
  updateFreeAgencyCounters();
}

function updateFreeAgencyCounters() {
  const waiversEl = document.getElementById('waivers-count');
  const signingsEl = document.getElementById('signings-count');
  if (waiversEl) {
    waiversEl.textContent = `${currentFALimits.waiversUsed} / ${currentFALimits.waiversMax}`;
    waiversEl.classList.toggle('text-danger', currentFALimits.waiversMax && currentFALimits.waiversUsed >= currentFALimits.waiversMax);
  }
  if (signingsEl) {
    signingsEl.textContent = `${currentFALimits.signingsUsed} / ${currentFALimits.signingsMax}`;
    signingsEl.classList.toggle('text-danger', currentFALimits.signingsMax && currentFALimits.signingsUsed >= currentFALimits.signingsMax);
  }
}

function calculateCapTop8(players) {
  return players
    .slice()
    .sort((a, b) => Number(b.ovr) - Number(a.ovr))
    .slice(0, 8)
    .reduce((sum, p) => sum + Number(p.ovr), 0);
}

function getCapAfterRemoval(playerId) {
  const remaining = allPlayers.filter((p) => String(p.id) !== String(playerId));
  return calculateCapTop8(remaining);
}

function getCapStatusText(newCap) {
  const capMin = Number(window.__CAP_MIN__);
  const capMax = Number(window.__CAP_MAX__);
  if (Number.isFinite(capMin) && Number.isFinite(capMax)) {
    if (newCap < capMin) return 'Voce vai ficar abaixo do cap.';
    if (newCap > capMax) return 'Voce vai ficar acima do cap.';
  }
  return 'Voce vai ficar dentro do cap.';
}

function openWaiveModal(player) {
  if (!player) return;
  pendingWaivePlayerId = player.id;
  const nameEl = document.getElementById('waive-player-name');
  const capEl = document.getElementById('waive-player-cap');
  const statusEl = document.getElementById('waive-cap-status');
  if (nameEl) nameEl.textContent = player.name || 'jogador';
  const newCap = getCapAfterRemoval(player.id);
  if (capEl) capEl.textContent = newCap;
  if (statusEl) statusEl.textContent = getCapStatusText(newCap);
  const modalEl = document.getElementById('waivePlayerModal');
  if (modalEl) {
    new bootstrap.Modal(modalEl).show();
  }
}

async function performWaivePlayer(playerId) {
  if (!playerId) return;
  try {
    const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId }) });
    alert(res.message || 'Jogador dispensado e enviado para a Free Agency!');
    loadPlayers();
    loadFreeAgencyLimits();
  } catch (err) {
    alert('Erro: ' + (err.error || 'Desconhecido'));
  }
}

function applyFilters(players) {
  const term = currentSearch.trim().toLowerCase();
  const roleFilter = currentRoleFilter;
  return players.filter(p => {
    const roleOk = !roleFilter || normalizeRoleKey(p.role) === normalizeRoleKey(roleFilter);
    if (!term) return roleOk;
    const hay = `${p.name} ${p.position} ${p.secondary_position || ''}`.toLowerCase();
    return roleOk && hay.includes(term);
  });
}

async function updatePlayerRole(playerId, newRole) {
  await api('players.php', {
    method: 'PUT',
    body: JSON.stringify({ id: playerId, role: newRole })
  });
}

function findPlayerById(playerId) {
  return allPlayers.find(p => String(p.id) === String(playerId));
}

async function handleRoleDrop(targetRole, targetPlayerId = null) {
  if (!dragContext || !dragContext.playerId || !dragContext.role) return;

  const fromRole = dragContext.role;
  const fromPlayerId = dragContext.playerId;

  if (targetPlayerId && String(targetPlayerId) === String(fromPlayerId)) return;

  try {
    if (targetPlayerId) {
      const targetPlayer = findPlayerById(targetPlayerId);
      if (!targetPlayer) return;
      const targetCurrentRole = normalizeRoleKey(targetPlayer.role);
      if (targetCurrentRole === fromRole) return;

      await updatePlayerRole(fromPlayerId, targetCurrentRole);
      await updatePlayerRole(targetPlayerId, fromRole);
    } else {
      if (fromRole === targetRole) return;
      await updatePlayerRole(fromPlayerId, targetRole);
    }

    await loadPlayers();
  } catch (err) {
    alert('Erro ao atualizar funcoes via drag and drop: ' + (err.error || err.message || 'Desconhecido'));
  }
}

function attachDragHandlers(el, role, playerId = null) {
  if (!el) return;

  if (playerId) {
    el.setAttribute('draggable', 'true');
    el.addEventListener('dragstart', () => {
      dragContext = { playerId, role };
      el.classList.add('dragging');
    });
    el.addEventListener('dragend', () => {
      dragContext = null;
      document.querySelectorAll('.dnd-over').forEach(node => node.classList.remove('dnd-over'));
      document.querySelectorAll('.dragging').forEach(node => node.classList.remove('dragging'));
    });
  }

  el.addEventListener('dragover', (event) => {
    if (!dragContext) return;
    if (!playerId && dragContext.role === role) return;
    event.preventDefault();
    el.classList.add('dnd-over');
  });

  el.addEventListener('dragleave', () => {
    el.classList.remove('dnd-over');
  });

  el.addEventListener('drop', async (event) => {
    if (!dragContext) return;
    event.preventDefault();
    el.classList.remove('dnd-over');
    await handleRoleDrop(role, playerId);
  });
}

function setupRosterDragAndDrop() {
  document.querySelectorAll('.starter-slot').forEach((slot) => {
    attachDragHandlers(slot, 'Titular', slot.dataset.playerId || null);
  });

  document.querySelectorAll('.bench-slot').forEach((slot) => {
    attachDragHandlers(slot, 'Banco', slot.dataset.playerId || null);
  });

  document.querySelectorAll('.starter-dropzone').forEach((zone) => {
    attachDragHandlers(zone, 'Titular', null);
  });

  document.querySelectorAll('.bench-dropzone').forEach((zone) => {
    attachDragHandlers(zone, 'Banco', null);
  });
}

function sortPlayers(field) {
  if (currentSort.field === field) {
    currentSort.ascending = !currentSort.ascending;
  } else {
    currentSort.field = field;
    currentSort.ascending = field !== 'role';
  }
  renderPlayers(allPlayers);
}

function renderPlayers(players) {
  let sorted = applyFilters([...players]);
  sorted.sort((a, b) => {
    let aVal = a[currentSort.field];
    let bVal = b[currentSort.field];

    if (currentSort.field === 'role') {
      aVal = roleOrder[aVal] ?? 999;
      bVal = roleOrder[bVal] ?? 999;
    }
    if (currentSort.field === 'trade') {
      aVal = a.available_for_trade ? 1 : 0;
      bVal = b.available_for_trade ? 1 : 0;
    }
    if (['ovr', 'age', 'seasons_in_league'].includes(currentSort.field)) {
      aVal = Number(aVal);
      bVal = Number(bVal);
    }

    if (aVal < bVal) return currentSort.ascending ? -1 : 1;
    if (aVal > bVal) return currentSort.ascending ? 1 : -1;

    // Em caso de empate por função, ordenar por posição de armador a pivô
    if (currentSort.field === 'role' && a.role === 'Titular' && b.role === 'Titular') {
      const aPos = starterPositionOrder[a.position] ?? 999;
      const bPos = starterPositionOrder[b.position] ?? 999;
      if (aPos !== bPos) {
        return currentSort.ascending ? aPos - bPos : bPos - aPos;
      }
    }
    return 0;
  });

  // Renderizar Quinteto Titular (grid) + Banco (lista lateral)
  const grid = document.getElementById('players-grid');
  if (grid) {
    grid.innerHTML = '';
    const titulares = sorted.filter(p => normalizeRoleKey(p.role) === 'Titular');
    titulares.sort((a, b) => {
      const pa = starterPositionOrder[a.position] ?? 999;
      const pb = starterPositionOrder[b.position] ?? 999;
      if (pa !== pb) return pa - pb;
      return Number(b.ovr) - Number(a.ovr);
    });
    const starters = titulares.slice(0, 5);
    const bench = sorted
      .filter(p => normalizeRoleKey(p.role) === 'Banco')
      .sort((a, b) => Number(b.ovr) - Number(a.ovr));

    const row = document.createElement('div');
    row.className = 'row g-3';

    // ── Titulares ──────────────────────────────────
    const colLeft = document.createElement('div');
    colLeft.className = 'col-12 col-lg-8';
    const startersSection = document.createElement('div');
    startersSection.className = 'roster-section';
    startersSection.innerHTML = '<div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text-2);margin-bottom:14px;">QUINTETO TITULAR</div>';

    if (starters.length === 0) {
      startersSection.innerHTML += '<div style="text-align:center;color:var(--text-3);padding:32px 0;font-size:13px;">Sem jogadores marcados como Titular.</div>';
    } else {
      const startersGrid = document.createElement('div');
      startersGrid.style.cssText = 'display:grid;grid-template-columns:repeat(5,1fr);gap:10px;';

      starters.forEach(p => {
        const ovrColor = getOvrColor(p.ovr);
        const card = document.createElement('div');
        card.style.cssText = 'background:var(--panel-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px 8px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;';
        card.innerHTML = `
          <img src="${getPlayerPhotoUrl(p)}" alt="${p.name}"
            style="width:64px;height:64px;object-fit:cover;border-radius:50%;border:2px solid var(--red);background:#1a1a1a;flex-shrink:0;"
            onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=121212&color=fc0025&rounded=true&bold=true'">
          <div style="font-size:0.8rem;font-weight:700;color:var(--text);line-height:1.25;word-break:break-word;">${p.name}</div>
          <span style="background:var(--panel-3);color:var(--text-2);padding:2px 7px;border-radius:4px;font-size:0.7rem;font-weight:600;">${p.position}${p.secondary_position ? '/' + p.secondary_position : ''}</span>
          <div style="font-size:1.7rem;font-weight:800;color:${ovrColor};line-height:1;">${p.ovr}</div>`;
        startersGrid.appendChild(card);
      });

      for (let i = starters.length; i < 5; i++) {
        const empty = document.createElement('div');
        empty.style.cssText = 'background:var(--panel-2);border:1px dashed var(--border-md);border-radius:var(--radius-sm);padding:16px 8px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:180px;';
        empty.innerHTML = '<i class="bi bi-person-fill" style="font-size:2rem;color:var(--text-3);"></i><span style="font-size:0.7rem;color:var(--text-3);">Vazio</span>';
        startersGrid.appendChild(empty);
      }

      startersSection.appendChild(startersGrid);
    }
    colLeft.appendChild(startersSection);

    // ── Banco ──────────────────────────────────────
    const colRight = document.createElement('div');
    colRight.className = 'col-12 col-lg-4';
    const benchSection = document.createElement('div');
    benchSection.className = 'roster-section';
    benchSection.innerHTML = '<div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text-2);margin-bottom:14px;">BANCO</div>';

    if (bench.length === 0) {
      benchSection.innerHTML += '<div style="text-align:center;color:var(--text-3);padding:32px 0;font-size:13px;">Sem jogadores no banco.</div>';
    } else {
      const ul = document.createElement('ul');
      ul.style.cssText = 'list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;';
      bench.forEach(p => {
        const ovrColor = getOvrColor(p.ovr);
        const li = document.createElement('li');
        li.style.cssText = 'background:var(--panel-2);border:1px solid var(--border);border-radius:var(--radius-xs);padding:10px 12px;display:flex;align-items:center;gap:12px;';
        li.innerHTML = `
          <img src="${getPlayerPhotoUrl(p)}" alt="${p.name}"
            style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:1px solid var(--border-red);background:#1a1a1a;flex-shrink:0;"
            onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=121212&color=fc0025&rounded=true&bold=true'">
          <div style="flex:1;min-width:0;">
            <div style="font-size:0.85rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name}</div>
            <div style="font-size:0.75rem;color:var(--text-2);">${p.position}${p.secondary_position ? '/' + p.secondary_position : ''}</div>
          </div>
          <div style="font-size:1.1rem;font-weight:800;color:${ovrColor};flex-shrink:0;">${p.ovr}</div>`;
        ul.appendChild(li);
      });
      benchSection.appendChild(ul);
    }
    colRight.appendChild(benchSection);

    row.appendChild(colLeft);
    row.appendChild(colRight);
    grid.appendChild(row);

    document.getElementById('players-status').style.display = 'none';
    grid.style.display = '';
  }

  renderPlayersMobileCards(sorted);

  const statusEl = document.getElementById('players-status');
  if (statusEl) {
    statusEl.style.display = 'none';
  }

  updateRosterStats();
  try {
    renderPlayersTable(sorted);
  } catch (e) {
    console.warn('Falha ao renderizar tabela:', e);
  }
}

function renderPlayersMobileCards(players) {
  const container = document.getElementById('players-mobile-cards');
  if (!container) return;
  container.innerHTML = '';
  container.style.display = '';
  if (!players || players.length === 0) {
    container.innerHTML = '<div class="text-center text-light-gray">Nenhum jogador encontrado.</div>';
    return;
  }

  players.forEach(p => {
    const canRetire = Number(p.age) >= 35;
    const photoUrl = getPlayerPhotoUrl(p);
    const card = document.createElement('div');
    card.className = 'roster-mobile-card';
    card.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="d-flex align-items-center gap-2">
          <img src="${photoUrl}" alt="${p.name}"
               style="width: 44px; height: 44px; object-fit: cover; border-radius: 50%; border: 1px solid var(--fba-orange); background: #1a1a1a;"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=121212&color=f17507&rounded=true&bold=true'">
          <div>
            <div class="text-white fw-bold">${p.name}</div>
            <div class="text-light-gray small">${p.position}${p.secondary_position ? '/' + p.secondary_position : ''} • ${normalizeRoleKey(p.role)}</div>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-bold" style="color:${getOvrColor(p.ovr)}; font-size: 1.2rem;">${p.ovr}</div>
          <small class="text-light-gray">${p.age} anos</small>
        </div>
      </div>
      <div class="mt-2">
        ${p.available_for_trade ? '<span class="badge bg-success">Disponível</span>' : '<span class="badge bg-secondary">Indisp.</span>'}
      </div>
      <div class="roster-mobile-actions mt-3">
        <button class="btn btn-outline-light btn-sm btn-edit-player" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-outline-warning btn-sm btn-waive-player" data-id="${p.id}" data-name="${p.name}" title="Dispensar"><i class="bi bi-hand-thumbs-down"></i></button>
        ${canRetire ? `<button class="btn btn-outline-danger btn-sm btn-retire-player" data-id="${p.id}" data-name="${p.name}" title="Aposentar"><i class="bi bi-box-arrow-right"></i></button>` : ''}
        <button class="btn btn-sm ${p.available_for_trade ? 'btn-outline-success' : 'btn-outline-danger'} btn-toggle-trade" data-id="${p.id}" data-trade="${p.available_for_trade}" title="Disponibilidade para Troca">
          <i class="bi ${p.available_for_trade ? 'bi-check-circle' : 'bi-x-circle'}"></i>
        </button>
      </div>
    `;
    container.appendChild(card);
  });
}

function renderPlayersTable(players) {
  const wrapper = document.getElementById('players-table-wrapper');
  const tbody = document.getElementById('players-table-body');
  if (!wrapper || !tbody) return;
  tbody.innerHTML = '';
  if (!players || players.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-light-gray">Nenhum jogador encontrado.</td></tr>';
    wrapper.style.display = '';
    return;
  }
  players.forEach(p => {
    const canRetire = Number(p.age) >= 35;
    const photoUrl = getPlayerPhotoUrl(p);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="d-flex align-items-center gap-2">
          <img src="${photoUrl}" alt="${p.name}"
               style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%; border: 1px solid var(--fba-orange); background: #1a1a1a;"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=121212&color=f17507&rounded=true&bold=true'">
          <div class="d-flex flex-column">
            <span class="fw-semibold">${p.name}</span>
            <small class="text-light-gray">${p.position}${p.secondary_position ? '/' + p.secondary_position : ''}</small>
          </div>
        </div>
      </td>
      <td>${p.position}${p.secondary_position ? '/' + p.secondary_position : ''}</td>
      <td><span style="color:${getOvrColor(p.ovr)};" class="fw-bold">${p.ovr}</span></td>
      <td>${p.age}</td>
      <td>${normalizeRoleKey(p.role)}</td>
      <td>
        ${p.available_for_trade ? '<span class="badge bg-success">Disponível</span>' : '<span class="badge bg-secondary">Indisp.</span>'}
      </td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-light btn-edit-player" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-warning btn-waive-player" data-id="${p.id}" data-name="${p.name}" title="Dispensar"><i class="bi bi-hand-thumbs-down"></i></button>
        ${canRetire ? `<button class="btn btn-sm btn-outline-danger btn-retire-player" data-id="${p.id}" data-name="${p.name}" title="Aposentar"><i class="bi bi-box-arrow-right"></i></button>` : ''}
        <button class="btn btn-sm ${p.available_for_trade ? 'btn-outline-success' : 'btn-outline-danger'} btn-toggle-trade" data-id="${p.id}" data-trade="${p.available_for_trade}" title="Disponibilidade para Troca">
          <i class="bi ${p.available_for_trade ? 'bi-check-circle' : 'bi-x-circle'}"></i>
        </button>
      </td>`;
    tbody.appendChild(tr);
  });
  wrapper.style.display = '';
}

function updateRosterStats() {
  const totalPlayers = allPlayers.length;
  const topEight = calculateCapTop8(allPlayers);
  document.getElementById('total-players').textContent = totalPlayers;
  document.getElementById('cap-top8').textContent = topEight;
}

async function loadPlayers() {
  const teamId = window.__TEAM_ID__;
  const statusEl = document.getElementById('players-status');
  const gridEl = document.getElementById('players-grid');
  const mobileCardsEl = document.getElementById('players-mobile-cards');
  if (!teamId) {
    if (statusEl) {
      statusEl.innerHTML = '<div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle me-2"></i>Você ainda não possui um time.</div>';
      statusEl.style.display = 'block';
    }
    if (gridEl) gridEl.style.display = 'none';
    if (mobileCardsEl) mobileCardsEl.style.display = 'none';
    return;
  }
  if (statusEl) {
    statusEl.innerHTML = '<div class="spinner-border text-orange" role="status"></div><p class="text-light-gray mt-2">Carregando jogadores...</p>';
    statusEl.style.display = 'block';
  }
  if (gridEl) gridEl.style.display = 'none';
  if (mobileCardsEl) mobileCardsEl.style.display = 'none';
  try {
    const data = await api(`players.php?team_id=${teamId}`);
    allPlayers = data.players || [];
    currentSort = { field: 'role', ascending: true };
    renderPlayers(allPlayers);
    window.dispatchEvent(new CustomEvent('roster:players-updated', {
      detail: { players: allPlayers }
    }));
    if (statusEl) statusEl.style.display = 'none';
  } catch (err) {
    console.error('Erro ao carregar:', err);
    if (statusEl) {
      statusEl.innerHTML = `<div class="alert alert-danger text-center"><i class="bi bi-x-circle me-2"></i>Erro ao carregar jogadores: ${err.error || 'Desconhecido'}</div>`;
      statusEl.style.display = 'block';
    }
  }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
  loadPlayers();
  loadFreeAgencyLimits();

  window.addEventListener('roster:refresh-request', () => {
    loadPlayers();
  });

  document.getElementById('btn-ai-analysis')?.addEventListener('click', generateAIAnalysis);

  document.getElementById('btn-refresh-players')?.addEventListener('click', loadPlayers);
  document.getElementById('sort-select')?.addEventListener('change', (e) => sortPlayers(e.target.value));
  document.getElementById('players-search')?.addEventListener('input', (e) => {
    currentSearch = (e.target.value || '').toLowerCase();
    renderPlayers(allPlayers);
  });
  document.getElementById('players-role-filter')?.addEventListener('change', (e) => {
    currentRoleFilter = e.target.value || '';
    renderPlayers(allPlayers);
  });
  document.querySelector('#players-table thead')?.addEventListener('click', (e) => {
    const th = e.target.closest('th.sortable');
    if (th && th.dataset.sort) sortPlayers(th.dataset.sort);
  });

  const editPhotoInput = document.getElementById('edit-foto-adicional');
  editPhotoInput?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    editPhotoFile = file;
    const preview = document.getElementById('edit-foto-preview');
    if (!preview) return;
    if (preview.dataset.objectUrl) {
      URL.revokeObjectURL(preview.dataset.objectUrl);
      delete preview.dataset.objectUrl;
    }
    if (window.URL && URL.createObjectURL) {
      const objectUrl = URL.createObjectURL(file);
      preview.src = objectUrl;
      preview.dataset.objectUrl = objectUrl;
      return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => {
      preview.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });

  const formPlayer = document.getElementById('form-player');
  const handleAddPlayer = async () => {
    const form = formPlayer;
    if (!form) return;
    const teamId = window.__TEAM_ID__;
    if (!teamId) {
      alert('Você ainda não possui um time.');
      return;
    }
    const formData = new FormData(form);
    const payload = {
      team_id: teamId,
      name: (formData.get('name') || '').toString().trim(),
      age: parseInt(formData.get('age') || '0', 10),
      position: (formData.get('position') || '').toString().trim(),
      secondary_position: (formData.get('secondary_position') || '').toString().trim() || null,
      role: (formData.get('role') || 'Titular').toString(),
      ovr: parseInt(formData.get('ovr') || '0', 10),
      available_for_trade: formData.get('available_for_trade') ? 1 : 0
    };

    if (!payload.name || !payload.age || !payload.position || !payload.ovr) {
      alert('Preencha nome, idade, posição e OVR.');
      return;
    }

    const btn = document.getElementById('btn-add-player');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
    }

    try {
      const res = await api('players.php', { method: 'POST', body: JSON.stringify(payload) });
      alert(res.message || 'Jogador adicionado.');
      form.reset();
      document.getElementById('available_for_trade').checked = true;
      loadPlayers();
    } catch (err) {
      alert('Erro ao cadastrar jogador: ' + (err.error || err.message || 'Desconhecido'));
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Cadastrar Jogador';
      }
    }
  };

  formPlayer?.addEventListener('submit', async (e) => {
    e.preventDefault();
    handleAddPlayer();
  });

  // Delegação para ações da tabela
  document.getElementById('players-table-body')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    if (btn.classList.contains('btn-toggle-trade')) {
      const playerId = btn.dataset.id;
      const currentStatus = (() => {
        const raw = String(btn.dataset.trade || '').toLowerCase();
        return raw === 'true' || raw === '1' || raw === 'yes';
      })();
      const newStatus = currentStatus ? 0 : 1;
      try {
        await api('players.php', { method: 'PUT', body: JSON.stringify({ id: playerId, available_for_trade: newStatus }) });
        loadPlayers();
      } catch (err) {
        alert('Erro ao atualizar: ' + (err.error || 'Desconhecido'));
      }
      return;
    }
    if (btn.classList.contains('btn-edit-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      if (player) {
        document.getElementById('edit-player-id').value = player.id;
        document.getElementById('edit-name').value = player.name;
        editPhotoFile = null;
        const editPhotoField = document.getElementById('edit-foto-adicional');
        if (editPhotoField) editPhotoField.value = '';
        const editPreview = document.getElementById('edit-foto-preview');
        if (editPreview) editPreview.src = getPlayerPhotoUrl(player);
        document.getElementById('edit-age').value = player.age;
        document.getElementById('edit-position').value = player.position;
        document.getElementById('edit-secondary-position').value = player.secondary_position || '';
        document.getElementById('edit-ovr').value = player.ovr;
        document.getElementById('edit-role').value = player.role;
        document.getElementById('edit-available').checked = !!player.available_for_trade;
        new bootstrap.Modal(document.getElementById('editPlayerModal')).show();
      }
      return;
    }
    if (btn.classList.contains('btn-waive-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      openWaiveModal(player);
      return;
    }
    if (btn.classList.contains('btn-retire-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Aposentar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId, retirement: true }) });
          alert(res.message || 'Jogador aposentado!');
          loadPlayers();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
    }
  });

  // Delegação para ações nos cards mobile
  document.getElementById('players-mobile-cards')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    if (btn.classList.contains('btn-toggle-trade')) {
      const playerId = btn.dataset.id;
      const currentStatus = (() => {
        const raw = String(btn.dataset.trade || '').toLowerCase();
        return raw === 'true' || raw === '1' || raw === 'yes';
      })();
      const newStatus = currentStatus ? 0 : 1;
      try {
        await api('players.php', { method: 'PUT', body: JSON.stringify({ id: playerId, available_for_trade: newStatus }) });
        loadPlayers();
      } catch (err) {
        alert('Erro ao atualizar: ' + (err.error || 'Desconhecido'));
      }
      return;
    }
    if (btn.classList.contains('btn-edit-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      if (player) {
        document.getElementById('edit-player-id').value = player.id;
        document.getElementById('edit-name').value = player.name;
        editPhotoFile = null;
        const editPhotoField = document.getElementById('edit-foto-adicional');
        if (editPhotoField) editPhotoField.value = '';
        const editPreview = document.getElementById('edit-foto-preview');
        if (editPreview) editPreview.src = getPlayerPhotoUrl(player);
        document.getElementById('edit-age').value = player.age;
        document.getElementById('edit-position').value = player.position;
        document.getElementById('edit-secondary-position').value = player.secondary_position || '';
        document.getElementById('edit-ovr').value = player.ovr;
        document.getElementById('edit-role').value = player.role;
        document.getElementById('edit-available').checked = !!player.available_for_trade;
        new bootstrap.Modal(document.getElementById('editPlayerModal')).show();
      }
      return;
    }
    if (btn.classList.contains('btn-waive-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      openWaiveModal(player);
      return;
    }
    if (btn.classList.contains('btn-retire-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Aposentar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId, retirement: true }) });
          alert(res.message || 'Jogador aposentado!');
          loadPlayers();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
    }
  });

  // Salvar edição
  document.getElementById('btn-save-edit')?.addEventListener('click', async () => {
    const data = {
      id: document.getElementById('edit-player-id').value,
      name: document.getElementById('edit-name').value,
      age: document.getElementById('edit-age').value,
      position: document.getElementById('edit-position').value,
      secondary_position: document.getElementById('edit-secondary-position').value || null,
      ovr: document.getElementById('edit-ovr').value,
      role: document.getElementById('edit-role').value,
      available_for_trade: document.getElementById('edit-available').checked ? 1 : 0
    };
    if (editPhotoFile) {
      data.foto_adicional = await convertToBase64(editPhotoFile);
    }
    try {
      await api('players.php', { method: 'PUT', body: JSON.stringify(data) });
      bootstrap.Modal.getInstance(document.getElementById('editPlayerModal')).hide();
      loadPlayers();
    } catch (err) {
      alert('Erro ao salvar: ' + (err.error || 'Desconhecido'));
    }
  });

  document.getElementById('btn-confirm-waive')?.addEventListener('click', async () => {
    const modalEl = document.getElementById('waivePlayerModal');
    const playerId = pendingWaivePlayerId;
    pendingWaivePlayerId = null;
    if (modalEl) {
      const instance = bootstrap.Modal.getInstance(modalEl);
      instance && instance.hide();
    }
    await performWaivePlayer(playerId);
  });
});
