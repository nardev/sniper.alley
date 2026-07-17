/* Site behavior: mobile nav, list filtering, category tabs, lightbox, search. */

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initPhotographerFilter();
    initPhotographerSort();
    initVerticalSlider();
    initStoryFilter();
    initTabsWithPagination();
    initLightbox();
    initSearch();
    initEmbeds();
});

function initNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-nav-menu]');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', String(open));
    });
}

function applyGridFilter(grid, predicate, emptyEl, countEl) {
    let visible = 0;
    for (const card of grid.children) {
        const show = predicate(card);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    }
    if (emptyEl) emptyEl.classList.toggle('hidden', visible > 0);
    if (countEl) countEl.textContent = String(visible);
}

function initPhotographerFilter() {
    const input = document.querySelector('[data-filter-input]');
    const grid = document.querySelector('[data-filter-grid]');
    if (!input || !grid) return;
    const memoriamToggle = document.querySelector('[data-filter-memoriam-toggle]');
    const emptyEl = document.querySelector('[data-filter-empty]');
    const countEl = document.querySelector('[data-filter-count]');

    const run = () => {
        const query = input.value.trim().toLowerCase();
        const memoriamOnly = memoriamToggle && memoriamToggle.checked;
        applyGridFilter(grid, (card) => {
            const name = card.getAttribute('data-filter-name') || '';
            const text = card.getAttribute('data-filter-text') || '';
            const isMemoriam = card.getAttribute('data-filter-memoriam') === '1';
            if (memoriamOnly && !isMemoriam) return false;
            return !query || name.includes(query) || text.includes(query);
        }, emptyEl, countEl);
    };
    input.addEventListener('input', run);
    if (memoriamToggle) memoriamToggle.addEventListener('change', run);
}

function initPhotographerSort() {
    const select = document.querySelector('[data-sort-select]');
    const grid = document.querySelector('[data-filter-grid]');
    if (!select || !grid) return;

    const nameOf = (card) => card.getAttribute('data-filter-name') || '';
    const countOf = (card) => parseInt(card.getAttribute('data-photo-count') || '0', 10);
    const original = Array.from(grid.children);
    const sorters = {
        'name-asc': (a, b) => nameOf(a).localeCompare(nameOf(b)),
        'name-desc': (a, b) => nameOf(b).localeCompare(nameOf(a)),
        'photos-desc': (a, b) => countOf(b) - countOf(a) || nameOf(a).localeCompare(nameOf(b)),
        'photos-asc': (a, b) => countOf(a) - countOf(b) || nameOf(a).localeCompare(nameOf(b)),
    };

    select.addEventListener('change', () => {
        const sorter = sorters[select.value];
        const ordered = sorter ? [...original].sort(sorter) : original;
        // Re-append preserves each card's current display state (search/filter).
        for (const card of ordered) grid.appendChild(card);
    });
}

function initVerticalSlider() {
    const root = document.querySelector('[data-vslider-root]');
    if (!root) return;
    const track = root.querySelector('[data-vslider]');
    const up = root.querySelector('[data-vslider-up]');
    const down = root.querySelector('[data-vslider-down]');
    if (!track) return;

    const step = () => Math.max(track.clientHeight * 0.8, 160);
    const update = () => {
        const desktop = window.innerWidth >= 1024;
        const scrollable = desktop && track.scrollHeight - track.clientHeight > 4;
        const atTop = track.scrollTop <= 2;
        const atBottom = track.scrollTop + track.clientHeight >= track.scrollHeight - 2;
        if (up) up.classList.toggle('hidden', !scrollable || atTop);
        if (down) down.classList.toggle('hidden', !scrollable || atBottom);
    };

    if (up) up.addEventListener('click', () => track.scrollBy({ top: -step(), behavior: 'smooth' }));
    if (down) down.addEventListener('click', () => track.scrollBy({ top: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
}

function initStoryFilter() {
    const select = document.querySelector('[data-filter-select="photographer"]');
    const container = document.querySelector('[data-filter-grid]');
    if (!select || !container) return;
    select.addEventListener('change', () => {
        const value = select.value;
        for (const card of container.querySelectorAll('[data-photographer]')) {
            card.style.display = !value || card.getAttribute('data-photographer') === value ? '' : 'none';
        }
        // hide a season block (header + grid) when it has no visible cards
        const headers = container.querySelectorAll('[data-season-header]');
        const grids = container.querySelectorAll('[data-season-grid]');
        grids.forEach((grid, index) => {
            const anyVisible = Array.from(grid.querySelectorAll('[data-photographer]'))
                .some((card) => card.style.display !== 'none');
            grid.style.display = anyVisible ? '' : 'none';
            if (headers[index]) headers[index].style.display = anyVisible ? '' : 'none';
        });
    });
}

function initTabsWithPagination() {
    const bar = document.querySelector('[data-tabs]');
    const grid = document.querySelector('[data-filter-grid]');
    if (!bar || !grid) return;
    const emptyEl = document.querySelector('[data-filter-empty]');
    const moreButton = document.querySelector('[data-load-more]');
    const pageSize = 12;
    const activeClasses = ['border-accent', 'text-accent'];
    const inactiveClasses = ['border-transparent', 'text-mist'];
    let category = '';
    let visibleCount = pageSize;

    const render = () => {
        const cards = Array.from(grid.children);
        const matching = cards.filter((card) => !category || card.getAttribute('data-category') === category);
        for (const card of cards) card.style.display = 'none';
        matching.slice(0, visibleCount).forEach((card) => { card.style.display = ''; });
        if (emptyEl) emptyEl.classList.toggle('hidden', matching.length > 0);
        if (moreButton) moreButton.classList.toggle('hidden', matching.length <= visibleCount);
    };

    bar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-tab]');
        if (!button) return;
        for (const other of bar.querySelectorAll('[data-tab]')) {
            other.classList.remove(...activeClasses);
            other.classList.add(...inactiveClasses);
        }
        button.classList.add(...activeClasses);
        button.classList.remove(...inactiveClasses);
        category = button.getAttribute('data-tab') || '';
        visibleCount = pageSize;
        render();
    });

    if (moreButton) {
        moreButton.addEventListener('click', () => {
            visibleCount += pageSize;
            render();
        });
    }
    render();
}

function initEmbeds() {
    const loaders = [
        ['.twitter-tweet', 'https://platform.twitter.com/widgets.js'],
        ['.instagram-media', 'https://www.instagram.com/embed.js'],
        ['.fb-post, .fb-video', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v19.0'],
    ];
    for (const [selector, src] of loaders) {
        if (!document.querySelector(selector)) continue;
        if (selector.startsWith('.fb-') && !document.getElementById('fb-root')) {
            const root = document.createElement('div');
            root.id = 'fb-root';
            document.body.prepend(root);
        }
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.crossOrigin = 'anonymous';
        document.body.appendChild(script);
    }
}

function initLightbox() {
    const box = document.getElementById('lightbox');
    const items = Array.from(document.querySelectorAll('[data-gallery-item]'));
    if (!box || items.length === 0) return;
    const img = box.querySelector('img');
    const caption = box.querySelector('figcaption');
    let index = 0;

    const idOf = (item) => item.getAttribute('data-photo-id') || '';
    const setHash = (id) => {
        const hash = id ? '#photo=' + encodeURIComponent(id) : '';
        history.replaceState(null, '', location.pathname + location.search + hash);
    };

    const show = (i, syncHash = true) => {
        index = (i + items.length) % items.length;
        const item = items[index];
        img.src = item.getAttribute('data-full');
        img.alt = item.getAttribute('data-caption') || '';
        caption.textContent = item.getAttribute('data-caption') || '';
        box.classList.add('open');
        box.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (syncHash) setHash(idOf(item));
    };
    const close = () => {
        box.classList.remove('open');
        box.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setHash('');
    };

    items.forEach((item, i) => item.addEventListener('click', () => show(i)));
    box.querySelector('[data-lightbox-close]').addEventListener('click', close);
    box.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(index - 1));
    box.querySelector('[data-lightbox-next]').addEventListener('click', () => show(index + 1));
    box.addEventListener('click', (event) => {
        if (event.target === box) close();
    });
    document.addEventListener('keydown', (event) => {
        if (!box.classList.contains('open')) return;
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
    });

    // Deep link: open the photo named in the URL (#photo=<id>) so a copied link
    // reopens the gallery on that exact image.
    const openFromHash = () => {
        const match = /[#&]photo=([^&]+)/.exec(location.hash);
        if (!match) return;
        const id = decodeURIComponent(match[1]);
        const target = items.findIndex((item) => idOf(item) === id);
        if (target >= 0) show(target, false);
    };
    window.addEventListener('hashchange', openFromHash);
    openFromHash();
}

function initSearch() {
    const input = document.querySelector('[data-search-input]');
    const results = document.querySelector('[data-search-results]');
    const status = document.querySelector('[data-search-status]');
    if (!input || !results) return;

    const root = document.body.getAttribute('data-root') || '';
    let index = null;
    const typeLabels = {
        photographer: 'Photographer',
        story: 'Story Behind Photo',
        memoriam: 'In Memoriam',
        latest: 'Latest',
    };

    const load = async () => {
        if (index) return index;
        const response = await fetch(root + 'media/search.json');
        index = await response.json();
        return index;
    };

    const run = async () => {
        const query = input.value.trim().toLowerCase();
        if (query.length < 2) {
            results.innerHTML = '';
            if (status) status.textContent = 'Start typing to search the archive.';
            return;
        }
        const data = await load();
        const terms = query.split(/\s+/);
        const scored = [];
        for (const entry of data) {
            const title = entry.title.toLowerCase();
            const text = (entry.text || '').toLowerCase() + ' ' + (entry.meta || '').toLowerCase();
            let score = 0;
            for (const term of terms) {
                if (title.includes(term)) score += 10;
                if (text.includes(term)) score += 2;
            }
            if (score > 0) scored.push([score, entry]);
        }
        scored.sort((a, b) => b[0] - a[0]);
        const top = scored.slice(0, 30);
        if (status) status.textContent = top.length ? `${top.length} result${top.length === 1 ? '' : 's'}` : 'No results found.';
        results.innerHTML = top.map(([, entry]) => `
            <a href="${root}${entry.url}" class="block py-4 hover:bg-white">
                <span class="kicker">${typeLabels[entry.type] || entry.type}</span>
                <span class="mt-1 block font-display text-lg font-bold">${escapeHtml(entry.title)}</span>
                ${entry.meta ? `<span class="block text-sm text-mist">${escapeHtml(entry.meta)}</span>` : ''}
            </a>`).join('');
    };

    let timer = null;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(run, 150);
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
}
