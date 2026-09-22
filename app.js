// ME PLUS 2026 - Multi-Page Web Platform Controller
// Light-Mode-First with Dark Mode Toggle & Reactive Page Handlers

(function initTheme() {
  const savedTheme = localStorage.getItem('meplus_theme');
  if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
})();

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initialize Lucide Icons
  if (window.lucide) {
    window.lucide.createIcons();
  }

  // 2. Theme Toggle Controller (Light Mode Default)
  const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
  function updateThemeUI(isDark) {
    themeToggleBtns.forEach(btn => {
      const sunIcon = btn.querySelector('.icon-sun');
      const moonIcon = btn.querySelector('.icon-moon');
      if (sunIcon && moonIcon) {
        if (isDark) {
          sunIcon.classList.remove('hidden');
          moonIcon.classList.add('hidden');
        } else {
          sunIcon.classList.add('hidden');
          moonIcon.classList.remove('hidden');
        }
      }
    });
  }

  const isCurrentlyDark = document.documentElement.classList.contains('dark');
  updateThemeUI(isCurrentlyDark);

  themeToggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const willBeDark = !document.documentElement.classList.contains('dark');
      if (willBeDark) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('meplus_theme', 'dark');
      } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('meplus_theme', 'light');
      }
      updateThemeUI(willBeDark);
      if (window.lucide) window.lucide.createIcons();
    });
  });

  // 3. Mobile Menu Drawer
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileDrawer = document.getElementById('mobile-drawer');
  const closeDrawerBtn = document.getElementById('close-drawer-btn');
  const closeDrawerBackdrop = document.getElementById('close-drawer-backdrop');

  function openDrawer() {
    mobileDrawer?.classList.remove('hidden');
    mobileMenuBtn?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    closeDrawerBtn?.focus();
  }

  function closeDrawer() {
    mobileDrawer?.classList.add('hidden');
    mobileMenuBtn?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    mobileMenuBtn?.focus();
  }

  mobileMenuBtn?.addEventListener('click', openDrawer);
  closeDrawerBtn?.addEventListener('click', closeDrawer);
  closeDrawerBackdrop?.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (event) => {
    if (!mobileDrawer || mobileDrawer.classList.contains('hidden')) return;
    if (event.key === 'Escape') closeDrawer();
    if (event.key !== 'Tab') return;
    const controls = [...mobileDrawer.querySelectorAll('a[href], button:not([disabled])')];
    const first = controls[0];
    const last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault(); last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault(); first?.focus();
    }
  });

  document.querySelectorAll('.drawer-link').forEach(link => {
    link.addEventListener('click', closeDrawer);
  });

  // 4. Data Extraction from data.js
  const { formations = [], regulations = [], consultants = [] } = window.MEPLUS_DATA || {};

  // 5. Toast Notification Helper
  window.showToast = function(title, msg, type = 'success') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toast-container';
      toastContainer.className = 'fixed bottom-24 md:bottom-8 right-6 z-50 flex flex-col gap-3 max-w-sm pointer-events-none';
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `p-4 rounded-2xl shadow-2xl backdrop-blur-xl border flex items-start gap-3 transition-all duration-300 transform translate-y-4 opacity-0 pointer-events-auto ${
      type === 'success' 
        ? 'bg-white/95 dark:bg-slate-900/95 border-emerald-500/40 text-emerald-800 dark:text-emerald-300' 
        : 'bg-white/95 dark:bg-slate-900/95 border-blue-500/40 text-blue-800 dark:text-blue-300'
    }`;
    toast.innerHTML = `
      <div class="shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-400">
        <i data-lucide="${type === 'success' ? 'check-circle-2' : 'info'}" class="w-5 h-5"></i>
      </div>
      <div class="text-xs">
        <div class="font-bold text-slate-900 dark:text-white mb-0.5">${title}</div>
        <div class="text-slate-600 dark:text-slate-300 leading-relaxed">${msg}</div>
      </div>
    `;
    toastContainer.appendChild(toast);
    if (window.lucide) window.lucide.createIcons();

    setTimeout(() => {
      toast.classList.remove('translate-y-4', 'opacity-0');
    }, 10);

    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-x-full');
      setTimeout(() => toast.remove(), 300);
    }, 4500);
  };



  // 6. Formations Catalog Handler & Dynamic Pagination
  const formationsContainer = document.getElementById('formations-grid');
  const formationCountEl = document.getElementById('formation-count');
  const searchInput = document.getElementById('search-input');
  const sortSelect = document.getElementById('sort-select');
  const domainPillBtns = document.querySelectorAll('.domain-pill-btn');
  const paginationContainer = document.getElementById('formations-pagination');
  const courseModal = document.getElementById('course-modal');
  const modalBody = document.getElementById('modal-body');
  const closeModalBtn = document.getElementById('close-modal-btn');

  let currentDomain = 'all';
  let searchQuery = '';
  let currentSort = 'relevance';
  let currentPage = 1;
  const itemsPerPage = 9; // 3 columns x 3 rows = 9 cards per page -> 10 pages for 85 courses

  // Keep the catalogue visually rich even before an editor assigns a custom image.
  // Editors can override these defaults from Admin Studio for any individual module.
  const defaultFormationImage = domain => {
    const value = String(domain || '').toLowerCase();
    if (value.includes('technique')) return './assets/generated/tpm-maintenance.png';
    if (value.includes('management')) return './assets/generated/consultants-casablanca.png';
    if (value.includes('qualité') || value.includes('qualite') || value.includes('dmo') || value.includes('bmo')) {
      return './assets/generated/audit-safety.png';
    }
    return './assets/generated/training-safety.png';
  };

  function renderFormations(limit = null) {
    if (!formationsContainer) return;

    let filtered = formations.filter(f => {
      const matchesDomain = currentDomain === 'all' || 
        f.domain.toLowerCase().includes(currentDomain.toLowerCase()) ||
        (currentDomain === 'securite' && (f.domain.toLowerCase().includes('sécurité') || f.domain.toLowerCase().includes('securite'))) ||
        (currentDomain === 'technique' && f.domain.toLowerCase().includes('technique')) ||
        (currentDomain === 'management' && f.domain.toLowerCase().includes('management')) ||
        (currentDomain === 'qualite' && (f.domain.toLowerCase().includes('qualité') || f.domain.toLowerCase().includes('qualite') || f.domain.toLowerCase().includes('dmo') || f.domain.toLowerCase().includes('bmo')));

      const q = searchQuery.toLowerCase().trim();
      const matchesSearch = !q || 
        f.title.toLowerCase().includes(q) ||
        f.domain.toLowerCase().includes(q) ||
        (f.objectives && f.objectives.some(o => o.toLowerCase().includes(q))) ||
        (f.content && f.content.some(c => c.toLowerCase().includes(q)));

      return matchesDomain && matchesSearch;
    });

    // Apply Sorting
    if (currentSort === 'title-asc') {
      filtered.sort((a, b) => a.title.localeCompare(b.title, 'fr'));
    } else if (currentSort === 'domain') {
      filtered.sort((a, b) => a.domain.localeCompare(b.domain, 'fr'));
    }

    const totalCount = filtered.length;
    if (formationCountEl) {
      formationCountEl.textContent = `${totalCount} formation${totalCount > 1 ? 's' : ''} disponible${totalCount > 1 ? 's' : ''}`;
    }

    // Determine pagination or limit
    const isHomePreview = limit && typeof limit === 'number';
    let displayedFormations = filtered;

    if (!isHomePreview && paginationContainer) {
      const totalPages = Math.ceil(totalCount / itemsPerPage) || 1;
      if (currentPage > totalPages) currentPage = totalPages;
      const startIndex = (currentPage - 1) * itemsPerPage;
      displayedFormations = filtered.slice(startIndex, startIndex + itemsPerPage);
      renderPagination(totalPages);
    } else if (isHomePreview) {
      displayedFormations = filtered.slice(0, limit);
      if (paginationContainer) paginationContainer.innerHTML = '';
    }

    if (displayedFormations.length === 0) {
      formationsContainer.innerHTML = `
        <div class="col-span-full text-center py-16 px-4 glass-card rounded-3xl border border-blue-500/20">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
            <i data-lucide="search-x" class="w-8 h-8"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Aucun module ne correspond à votre recherche</h3>
          <p class="text-slate-600 dark:text-slate-400 max-w-md mx-auto mb-6 text-sm">
            Vous pouvez réinitialiser la recherche ou nous contacter pour concevoir un programme de formation sur-mesure pour votre usine.
          </p>
          <button id="reset-search-btn" class="px-5 py-2.5 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-md">
            Réinitialiser les filtres
          </button>
        </div>
      `;
      document.getElementById('reset-search-btn')?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        searchQuery = '';
        currentDomain = 'all';
        currentPage = 1;
        domainPillBtns.forEach(t => t.classList.toggle('is-active', t.dataset.domain === 'all'));
        renderFormations(limit);
      });
      if (window.lucide) window.lucide.createIcons();
      return;
    }

    const escapeAttribute = value => String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    // Build Cards matching user's reference screenshot
    formationsContainer.innerHTML = displayedFormations.map(f => {
      let badgeBg = 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300';
      let icon = 'shield';
      const domLower = f.domain.toLowerCase();

      if (domLower.includes('technique')) {
        badgeBg = 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300';
        icon = 'settings';
      } else if (domLower.includes('management')) {
        badgeBg = 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300';
        icon = 'users';
      } else if (domLower.includes('qualité') || domLower.includes('qualite') || domLower.includes('dmo') || domLower.includes('bmo')) {
        badgeBg = 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300';
        icon = 'target';
      }

      const objPreview = f.objectives && f.objectives.length > 0 
        ? f.objectives[0] 
        : 'Formation certifiante et pratique pour les techniciens et ingénieurs industriels.';

      const detailUrl = `formation-detail.html?id=${encodeURIComponent(f.id)}`;
      const cardImage = f.image || defaultFormationImage(f.domain);

      return `
        <div class="glass-card p-6 md:p-7 rounded-2xl flex flex-col justify-between group border border-slate-200/90 dark:border-slate-800 hover:border-blue-500/40 transition duration-300 shadow-sm hover:shadow-md">
          <div>
            <img src="${escapeAttribute(cardImage)}" alt="${escapeAttribute(f.image_alt || f.title)}" loading="lazy" class="w-full h-40 object-cover rounded-xl mb-4" />
            <!-- Header Badges: Domain Icon Pill (Left) + Certifiante Pill (Right) -->
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ${badgeBg}">
                <i data-lucide="${icon}" class="w-3.5 h-3.5"></i>
                <span>${f.domain}</span>
              </span>
              <span class="text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-full font-medium">
                Certifiante
              </span>
            </div>

            <!-- Title Linking to Dedicated Page -->
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mt-3 mb-2 group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition leading-snug">
              <a href="${detailUrl}" class="hover:underline focus:outline-none">
                ${f.title}
              </a>
            </h3>

            <!-- 2-line Description Preview -->
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-5 leading-relaxed">
              ${objPreview}
            </p>
          </div>

          <!-- Bottom Footer: Duration (Left) + Voir le programme ↗ (Right) -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-3">
            <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500"></i>
              2 à 3 jours
            </span>
            <a 
              href="${detailUrl}" 
              class="text-xs sm:text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-cyan-400 dark:hover:text-cyan-300 flex items-center gap-1 group/btn transition"
              title="Consulter le programme détaillé">
              <span>Voir le programme</span>
              <span class="text-base font-normal group-hover/btn:translate-x-0.5 group-hover/btn:-translate-y-0.5 transition inline-block">↗</span>
            </a>
          </div>
        </div>
      `;
    }).join('');

    if (window.lucide) window.lucide.createIcons();
  }

  function renderPagination(totalPages) {
    if (!paginationContainer) return;
    if (totalPages <= 1) {
      paginationContainer.innerHTML = '';
      return;
    }

    let pages = [];
    if (totalPages <= 7) {
      for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
      if (currentPage <= 3) {
        pages = [1, 2, 3, '...', totalPages];
      } else if (currentPage >= totalPages - 2) {
        pages = [1, '...', totalPages - 2, totalPages - 1, totalPages];
      } else {
        pages = [1, '...', currentPage, '...', totalPages];
      }
    }

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';

    paginationContainer.innerHTML = `
      <div class="flex items-center justify-center gap-2">
        <button id="page-prev-btn" class="page-arrow-btn" ${prevDisabled} title="Page précédente">
          <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center gap-1.5">
          ${pages.map(p => {
            if (p === '...') {
              return `<span class="px-2 text-slate-400 font-bold">...</span>`;
            }
            const isActive = p === currentPage ? 'is-active' : '';
            return `
              <button class="page-number-btn ${isActive}" data-page="${p}">
                ${p}
              </button>
            `;
          }).join('')}
        </div>

        <button id="page-next-btn" class="page-arrow-btn" ${nextDisabled} title="Page suivante">
          <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
      </div>
    `;

    if (window.lucide) window.lucide.createIcons();

    // Bind page buttons
    paginationContainer.querySelectorAll('.page-number-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const p = parseInt(btn.dataset.page, 10);
        if (p && p !== currentPage) {
          currentPage = p;
          renderFormations();
          const target = document.getElementById('formations-grid');
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      });
    });

    document.getElementById('page-prev-btn')?.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        renderFormations();
        document.getElementById('formations-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });

    document.getElementById('page-next-btn')?.addEventListener('click', () => {
      if (currentPage < totalPages) {
        currentPage++;
        renderFormations();
        document.getElementById('formations-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  // Bind Search & Filters
  searchInput?.addEventListener('input', (e) => {
    searchQuery = e.target.value;
    currentPage = 1;
    renderFormations();
  });

  sortSelect?.addEventListener('change', (e) => {
    currentSort = e.target.value;
    currentPage = 1;
    renderFormations();
  });

  domainPillBtns.forEach(tab => {
    tab.addEventListener('click', () => {
      domainPillBtns.forEach(t => t.classList.remove('is-active'));
      tab.classList.add('is-active');
      currentDomain = tab.dataset.domain;
      currentPage = 1;
      renderFormations();
    });
  });

  // Check if we are on index (has preview) or formations.html (has full grid)
  if (formationsContainer) {
    const isHomePreview = formationsContainer.dataset.preview === 'true';
    renderFormations(isHomePreview ? 6 : null);
  }

  // =========================================================================
  // 6.b Single Formation Detail Page Controller (formation-detail.html)
  // =========================================================================
  function initFormationDetailPage() {
    const detailTitleEl = document.getElementById('detail-title');
    if (!detailTitleEl) return; // Not on formation-detail.html

    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('id');

    // Find requested formation or fallback to first
    let formation = formations.find(f => f.id === courseId);
    if (!formation && courseId) {
      formation = formations.find(f => f.title.toLowerCase().includes(courseId.toLowerCase()));
    }
    if (!formation) {
      formation = formations[0] || {
        id: 'f-0',
        title: 'Audit comportemental et culture sécurité',
        domain: 'Sécurité – hygiène',
        objectives: ['Identifier et aider les opérationnels à voir les situations dangereuses', 'Bâtir la confiance avec les observés'],
        content: ['Conseils d’observation', 'Déroulement du dialogue', 'Exercice pratique']
      };
    }

    // Update document title & metadata
    document.title = `${formation.title} | Formation Certifiante ME PLUS Maroc`;
    const metaDesc = document.getElementById('page-meta-desc');
    if (metaDesc) {
      metaDesc.content = `Programme complet de la formation ${formation.title} dispensée par ME PLUS à Casablanca. Prise en charge CSF / GIAC jusqu'à 80%.`;
    }

    // Populate Hero elements
    detailTitleEl.textContent = formation.title;
    const courseImage = document.querySelector('.detail-hero-image');
    if (courseImage) {
      courseImage.src = formation.image || defaultFormationImage(formation.domain);
      courseImage.alt = formation.image_alt || formation.title;
    }

    const domainCrumb = document.getElementById('detail-domain-crumb');
    if (domainCrumb) domainCrumb.textContent = formation.domain;

    const titleCrumb = document.getElementById('detail-title-crumb');
    if (titleCrumb) titleCrumb.textContent = formation.title;

    const domainBadge = document.getElementById('detail-domain-badge');
    if (domainBadge) {
      let iconName = 'shield';
      const dLower = formation.domain.toLowerCase();
      if (dLower.includes('technique')) iconName = 'settings';
      else if (dLower.includes('management')) iconName = 'users';
      else if (dLower.includes('qualité') || dLower.includes('qualite') || dLower.includes('dmo')) iconName = 'target';

      domainBadge.innerHTML = `<i data-lucide="${iconName}" class="w-3.5 h-3.5"></i> <span>${formation.domain}</span>`;
    }

    const detailLead = document.getElementById('detail-lead');
    if (detailLead && formation.objectives && formation.objectives.length > 0) {
      detailLead.textContent = `Acquisition des compétences clés : ${formation.objectives[0]} Programme agréé par l'État marocain, éligible au remboursement CSF OFPPT (jusqu'à 80%).`;
    }

    // Populate Objectives
    const objContainer = document.getElementById('detail-objectives');
    if (objContainer) {
      if (formation.objectives && formation.objectives.length > 0) {
        objContainer.innerHTML = formation.objectives.map(obj => `
          <li class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <div class="w-5 h-5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 mt-0.5">
              <i data-lucide="check" class="w-3.5 h-3.5"></i>
            </div>
            <span class="leading-relaxed">${obj}</span>
          </li>
        `).join('');
      } else {
        objContainer.innerHTML = `
          <li class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <div class="w-5 h-5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 mt-0.5">
              <i data-lucide="check" class="w-3.5 h-3.5"></i>
            </div>
            <span>Acquisition d'une méthodologie opérationnelle complète et maîtrise des protocoles de sécurité conformes aux normes marocaines.</span>
          </li>
        `;
      }
    }

    // Populate Detailed Syllabus Content
    const contentContainer = document.getElementById('detail-content');
    if (contentContainer) {
      if (formation.content && formation.content.length > 0) {
        contentContainer.innerHTML = formation.content.map((item, index) => `
          <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <span class="w-7 h-7 rounded-xl bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-cyan-400 font-bold text-xs flex items-center justify-center shrink-0">
              ${index + 1}
            </span>
            <div>
              <h4 class="font-bold text-slate-900 dark:text-white text-sm mb-1">${item}</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                Apports théoriques structurés, analyse de cas concrets et mise en application sur le matériel ou scénario d'atelier.
              </p>
            </div>
          </div>
        `).join('');
      } else {
        contentContainer.innerHTML = `
          <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
            Programme modulaire personnalisé selon les spécificités de vos équipements et installations industrielles.
          </div>
        `;
      }
    }

    // Populate Links in Sidebar
    const bookBtn = document.getElementById('book-course-btn');
    if (bookBtn) {
      bookBtn.href = `contact.html?formation=${encodeURIComponent(formation.title)}&sujet=Devis`;
    }

    const f2Btn = document.getElementById('f2-download-btn');
    if (f2Btn) {
      f2Btn.href = formation.attached_file || `contact.html?formation=${encodeURIComponent(formation.title)}&sujet=Fiche+F2+OFPPT`;
    }

    const simBtn = document.getElementById('simulate-course-btn');
    if (simBtn) {
      simBtn.href = `simulateur.html?module=${encodeURIComponent(formation.title)}`;
    }

    // Smart Return Button: if user came from formations.html, return there seamlessly
    const backBtnTop = document.getElementById('back-to-catalog-top-btn');
    if (backBtnTop) {
      backBtnTop.addEventListener('click', (e) => {
        if (document.referrer && document.referrer.includes('formations.html')) {
          e.preventDefault();
          window.history.back();
        }
      });
    }

    // Populate Related Formations (3 modules from same or adjacent domain)
    const relatedContainer = document.getElementById('related-formations-grid');
    if (relatedContainer) {
      const related = formations
        .filter(f => f.id !== formation.id && (f.domain === formation.domain || currentDomain === 'all'))
        .slice(0, 3);

      relatedContainer.innerHTML = related.map(rf => `
        <div class="glass-card p-5 sm:p-6 rounded-2xl flex flex-col justify-between border border-slate-200/90 dark:border-slate-800 hover:border-blue-500/40 transition shadow-sm">
          <div>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-600 dark:text-cyan-400 mb-2.5">
              ${rf.domain}
            </span>
            <h4 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white mb-2 line-clamp-2">
              <a href="formation-detail.html?id=${encodeURIComponent(rf.id)}" class="hover:underline">
                ${rf.title}
              </a>
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-4">
              ${rf.objectives && rf.objectives.length > 0 ? rf.objectives[0] : 'Programme de perfectionnement professionnel.'}
            </p>
          </div>
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <span class="text-[11px] text-slate-400">2 à 3 jours</span>
            <a href="formation-detail.html?id=${encodeURIComponent(rf.id)}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
              Voir le programme ↗
            </a>
          </div>
        </div>
      `).join('');
    }

    if (window.lucide) window.lucide.createIcons();
  }

  // Initialize formation-detail.html if on that page
  initFormationDetailPage();

  // 7. Interactive GIAC / CSF Reimbursement Simulator (simulateur.html & index teaser)
  const budgetInput = document.getElementById('sim-budget');
  const budgetValEl = document.getElementById('sim-budget-val');
  const employeesInput = document.getElementById('sim-employees');
  const employeesValEl = document.getElementById('sim-employees-val');
  const csfRefundEl = document.getElementById('sim-csf-refund');
  const giacRefundEl = document.getElementById('sim-giac-refund');
  const netCostEl = document.getElementById('sim-net-cost');

  function updateSimulation() {
    if (!budgetInput) return;
    const budget = parseInt(budgetInput.value, 10);
    const employees = employeesInput ? parseInt(employeesInput.value, 10) : 75;

    if (budgetValEl) budgetValEl.textContent = new Intl.NumberFormat('fr-MA').format(budget) + ' MAD';
    if (employeesValEl) employeesValEl.textContent = employees + ' salariés';

    // Moroccan CSF legal rules: 70% reimbursement on approved training
    const csfRate = 0.70;
    const csfRefund = Math.round(budget * csfRate);
    // GIAC covers engineering / diagnostic studies (up to 70-80%, capped)
    const giacSupport = Math.round(Math.min(40000, budget * 0.15));
    const totalRefund = csfRefund + giacSupport;
    const netCost = Math.max(0, budget - totalRefund);

    if (csfRefundEl) csfRefundEl.textContent = new Intl.NumberFormat('fr-MA').format(csfRefund) + ' MAD';
    if (giacRefundEl) giacRefundEl.textContent = new Intl.NumberFormat('fr-MA').format(giacSupport) + ' MAD';
    if (netCostEl) netCostEl.textContent = new Intl.NumberFormat('fr-MA').format(netCost) + ' MAD';
  }

  budgetInput?.addEventListener('input', updateSimulation);
  employeesInput?.addEventListener('input', updateSimulation);
  updateSimulation();

  // 8. Render Regulations Library (reglementation.html)
  const regulationsContainer = document.getElementById('regulations-grid');
  if (regulationsContainer) {
    regulationsContainer.innerHTML = regulations.map(reg => `
      <div class="glass-card p-6 md:p-7 rounded-3xl flex flex-col justify-between border border-slate-200/90 dark:border-slate-800 hover:border-cyan-500/40 transition group">
        <div>
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-semibold bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border border-cyan-500/20">
              <i data-lucide="file-check-2" class="w-3 h-3"></i>
              ${reg.category}
            </span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded font-mono font-medium">
              ${reg.bo}
            </span>
          </div>

          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition leading-snug">
            ${reg.title}
          </h3>

          <p class="text-slate-600 dark:text-slate-400 text-xs mb-5 leading-relaxed line-clamp-3">
            ${reg.description}
          </p>
        </div>

        <div class="pt-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-2">
          <span class="text-[11px] text-slate-500 dark:text-slate-400">
            Publié le : ${reg.date}
          </span>
          <a 
            href="${reg.pdf}" 
            target="_blank" 
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-cyan-500/10 hover:bg-cyan-500 text-cyan-700 dark:text-cyan-300 hover:text-white dark:hover:text-slate-950 text-xs font-bold transition">
            <i data-lucide="download" class="w-3.5 h-3.5"></i>
            Télécharger BO
          </a>
        </div>
      </div>
    `).join('');
    if (window.lucide) window.lucide.createIcons();
  }

  // 9. Render Consultants (consultants.html & index preview)
  const consultantsContainer = document.getElementById('consultants-grid');
  if (consultantsContainer) {
    consultantsContainer.innerHTML = consultants.map(c => `
      <div class="glass-card p-6 md:p-7 rounded-3xl flex flex-col justify-between border border-slate-200/90 dark:border-slate-800 hover:border-blue-500/40 transition group">
        <div>
          <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden border-2 border-blue-500/30 shrink-0 bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 shadow-md p-1 flex items-center justify-center">
              <img src="${c.avatar}" alt="${c.name}" class="w-full h-full object-contain object-top group-hover:scale-105 transition duration-300" onerror="this.onerror=null; this.src='./assets/consultants/meskini.png';" />
            </div>
            <div>
              <span class="text-[10px] font-bold tracking-wider uppercase text-blue-600 dark:text-cyan-400 bg-blue-500/10 px-2.5 py-0.5 rounded-full border border-blue-500/20">
                ${c.tag}
              </span>
              <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mt-1 group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition">
                ${c.name}
              </h3>
              <div class="text-xs text-slate-500 dark:text-slate-400 leading-tight">${c.role}</div>
            </div>
          </div>

          <p class="text-slate-600 dark:text-slate-300 text-xs mb-5 leading-relaxed">
            ${c.credentials}
          </p>

          <div class="space-y-1.5 mb-4">
            <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Expertises clés :</div>
            <div class="flex flex-wrap gap-1.5">
              ${c.specialties.map(s => `
                <span class="text-[10px] px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/50">
                  ${s}
                </span>
              `).join('')}
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-xs text-blue-600 dark:text-blue-400 font-semibold">
          <a href="contact.html?sujet=Consultant%20${encodeURIComponent(c.name)}" class="hover:underline flex items-center gap-1 text-xs">
            <span>Solliciter pour un audit</span>
            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
          </a>
          <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-[11px]">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Disponible
          </span>
        </div>
      </div>
    `).join('');
    if (window.lucide) window.lucide.createIcons();
  }

  // 10. Contact Form with URL Query Parameters (contact.html)
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    const urlParams = new URLSearchParams(window.location.search);
    const formationParam = urlParams.get('formation');
    const sujetParam = urlParams.get('sujet');
    const subjectInput = document.getElementById('contact-subject');

    if (formationParam && subjectInput) {
      subjectInput.value = sujetParam 
        ? `${sujetParam} - ${formationParam}` 
        : `Réservation / Devis : ${formationParam}`;
    }

    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = contactForm.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-2"></i> Envoi en cours...`;
        if (window.lucide) window.lucide.createIcons();
      }

      setTimeout(() => {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<i data-lucide="check" class="w-4 h-4 inline mr-2"></i> Demande Envoyée !`;
        }
        window.showToast(
          'Demande Reçue avec Succès !',
          'Notre équipe ME PLUS à Casablanca vous contactera sous 2 heures avec le devis officiel et le Formulaire F2.',
          'success'
        );
        contactForm.reset();
        setTimeout(() => {
          if (btn) {
            btn.innerHTML = `Envoyer la Demande & Obtenir le Devis <i data-lucide="arrow-right" class="w-4 h-4 inline ml-2"></i>`;
            if (window.lucide) window.lucide.createIcons();
          }
        }, 3000);
      }, 800);
    });
  }

  // 11. Subtle Scroll Reveal Observer
  const revealElements = document.querySelectorAll('.reveal-on-scroll');
  if ('IntersectionObserver' in window && revealElements.length > 0) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      rootMargin: '0px 0px -50px 0px',
      threshold: 0.1
    });

    revealElements.forEach(el => revealObserver.observe(el));
  } else {
    // Fallback if IntersectionObserver not supported
    revealElements.forEach(el => el.classList.add('is-visible'));
  }
});
