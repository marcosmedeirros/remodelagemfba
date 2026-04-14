(function() {
  document.addEventListener('DOMContentLoaded', () => {
    const playersListEl = document.getElementById('playersList');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');
    const countBadge = document.getElementById('countBadge');
    const tradeTabBtn = document.getElementById('trade-list-tab');

    if (!playersListEl || !searchInput || !sortSelect || !countBadge) {
      return;
    }

    let currentData = [];
    let debounceTimer = null;
    let initialized = false;

    function parseSort(value) {
      const [key, dir] = value.split('_');
      let sort = key;
      if (key === 'team') sort = 'team';
      return { sort, dir };
    }

    async function loadPlayers() {
      const q = searchInput.value.trim();
      const { sort, dir } = parseSort(sortSelect.value);
      const url = `/api/trade-list.php?q=${encodeURIComponent(q)}&sort=${encodeURIComponent(sort)}&dir=${encodeURIComponent(dir)}`;

      playersListEl.innerHTML = `<div style="text-align:center;padding:32px 16px;"><div class="spinner-border" style="color:var(--red);"></div></div>`;

      try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) throw new Error('Erro ao buscar lista');
        currentData = data.players || [];
        countBadge.textContent = `${data.count || currentData.length} jogadores`;
        renderPlayers(currentData);
      } catch (e) {
        playersListEl.innerHTML = `<div style="text-align:center;padding:32px 16px;color:var(--text-3);font-size:13px;"><i class="bi bi-exclamation-circle" style="display:block;font-size:24px;margin-bottom:8px;color:var(--red);"></i>Erro ao carregar lista de trocas.</div>`;
      }
    }

    function renderPlayers(players) {
      if (!players || players.length === 0) {
        playersListEl.innerHTML = `
          <div style="text-align:center;padding:40px 16px;color:var(--text-3);">
            <i class="bi bi-person-x" style="font-size:28px;display:block;margin-bottom:10px;"></i>
            <div style="font-size:13px;">Nenhum jogador disponível para troca na sua liga.</div>
          </div>
        `;
        return;
      }

      const html = players.map(p => {
        const secPos = p.secondary_position ? ` / ${p.secondary_position}` : '';
        const teamName = p.team_name || 'Time sem nome';
        const teamBadge = teamName
          .split(' ')
          .filter(Boolean)
          .map(word => word[0]?.toUpperCase() || '')
          .join('')
          .slice(0, 3) || 'GM';
        return `
        <div class="tl-player-card">
          <div>
            <div class="tl-player-name">${p.name}</div>
            <div class="tl-player-meta">Pos: ${p.position}${secPos} &bull; Idade: ${p.age} &bull; OVR: <strong style="color:var(--text);">${p.ovr}</strong> &bull; Função: ${p.role || '—'}</div>
          </div>
          <div class="tl-team-chip">
            <span class="tl-team-badge">${teamBadge}</span>
            ${teamName}
          </div>
        </div>
        `;
      }).join('');

      playersListEl.innerHTML = html;
    }

    function debounceLoad() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(loadPlayers, 250);
    }

    function initializeTradeList() {
      if (initialized) return;
      initialized = true;
      loadPlayers();
      searchInput.addEventListener('input', debounceLoad);
      sortSelect.addEventListener('change', loadPlayers);
    }

    if (tradeTabBtn) {
      tradeTabBtn.addEventListener('shown.bs.tab', initializeTradeList);
      if (tradeTabBtn.classList.contains('active')) {
        initializeTradeList();
      }
    } else {
      initializeTradeList();
    }

    // Compatibilidade com o tab switch customizado do novo layout.
    document.addEventListener('fba:tabSwitch', (e) => {
      if (e?.detail?.tab === 'trade-list') {
        initializeTradeList();
      }
    });
  });
})();
