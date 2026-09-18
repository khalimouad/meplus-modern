// MEPLUS 2026 Web Application Controller
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide Icons
  if (window.lucide) {
    window.lucide.createIcons();
  }

  const { formations = [], regulations = [], consultants = [], clients = [] } = window.MEPLUS_DATA || {};

  // State
  let currentDomain = 'all';
  let searchQuery = '';
  let selectedFormation = null;

  // DOM Elements
  const formationsContainer = document.getElementById('formations-grid');
  const formationCountEl = document.getElementById('formation-count');
  const searchInput = document.getElementById('search-input');
  const domainTabs = document.querySelectorAll('.domain-tab');
  const courseModal = document.getElementById('course-modal');
  const modalBody = document.getElementById('modal-body');
  const closeModalBtn = document.getElementById('close-modal-btn');
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileDrawer = document.getElementById('mobile-drawer');
  const closeDrawerBtn = document.getElementById('close-drawer-btn');
  const toastContainer = document.getElementById('toast-container');

  // 1. Render Formations
  function renderFormations() {
    if (!formationsContainer) return;

    let filtered = formations.filter(f => {
      const matchesDomain = currentDomain === 'all' || 
        f.domain.toLowerCase().includes(currentDomain.toLowerCase()) ||
        (currentDomain === 'securite' && (f.domain.toLowerCase().includes('sécurité') || f.domain.toLowerCase().includes('securite'))) ||
        (currentDomain === 'technique' && f.domain.toLowerCase().includes('technique')) ||
        (currentDomain === 'management' && f.domain.toLowerCase().includes('management')) ||
        (currentDomain === 'qualite' && (f.domain.toLowerCase().includes('qualité') || f.domain.toLowerCase().includes('qualite') || f.domain.toLowerCase().includes('dmo')));

      const q = searchQuery.toLowerCase().trim();
      const matchesSearch = !q || 
        f.title.toLowerCase().includes(q) ||
        f.domain.toLowerCase().includes(q) ||
        (f.objectives && f.objectives.some(o => o.toLowerCase().includes(q))) ||
        (f.content && f.content.some(c => c.toLowerCase().includes(q)));

      return matchesDomain && matchesSearch;
    });

    if (formationCountEl) {
      formationCountEl.textContent = `${filtered.length} module${filtered.length > 1 ? 's' : ''} disponible${filtered.length > 1 ? 's' : ''}`;
    }

    if (filtered.length === 0) {
      formationsContainer.innerHTML = `
        <div class="col-span-full text-center py-16 px-4 glass-panel rounded-2xl border border-blue-500/20">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-400">
            <i data-lucide="search-x" class="w-8 h-8"></i>
          </div>
          <h3 class="text-xl font-bold text-white mb-2">Aucune formation trouvée</h3>
          <p class="text-slate-400 max-w-md mx-auto mb-6 text-sm">
            Aucun module ne correspond à votre recherche "${searchQuery}". Vous pouvez réinitialiser les filtres ou nous contacter pour un programme sur-mesure.
          </p>
          <button id="reset-search-btn" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm transition">
            Réinitialiser les filtres
          </button>
        </div>
      `;
      document.getElementById('reset-search-btn')?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        searchQuery = '';
        currentDomain = 'all';
        domainTabs.forEach(t => t.classList.toggle('active-tab', t.dataset.domain === 'all'));
        renderFormations();
      });
      if (window.lucide) window.lucide.createIcons();
      return;
    }

    formationsContainer.innerHTML = filtered.map(f => {
      // Badge color based on domain
      let badgeColor = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
      let icon = 'shield-alert';
      const domLower = f.domain.toLowerCase();
      if (domLower.includes('technique')) {
        badgeColor = 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20';
        icon = 'cpu';
      } else if (domLower.includes('management')) {
        badgeColor = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        icon = 'bar-chart-3';
      } else if (domLower.includes('qualité') || domLower.includes('qualite')) {
        badgeColor = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        icon = 'check-circle-2';
      }

      const objPreview = f.objectives && f.objectives.length > 0 
        ? f.objectives[0] 
        : 'Formation pratique dispensée par nos experts industriels.';

      return `
        <div class="glass-panel p-6 rounded-2xl flex flex-col justify-between group border border-slate-800 hover:border-blue-500/40 transition duration-300">
          <div>
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${badgeColor}">
                <i data-lucide="${icon}" class="w-3.5 h-3.5"></i>
                ${f.domain}
              </span>
              <span class="text-[11px] font-medium text-slate-400 bg-slate-800/80 px-2 py-0.5 rounded-md">
                Certifiant
              </span>
            </div>

            <h3 class="text-lg font-bold text-white mb-2.5 group-hover:text-blue-400 transition leading-snug">
              ${f.title}
            </h3>

            <p class="text-slate-400 text-xs line-clamp-3 mb-4 leading-relaxed">
              ${objPreview}
            </p>
          </div>

          <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between gap-3">
            <span class="text-xs text-slate-400 flex items-center gap-1">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-400"></i>
              2 à 3 jours
            </span>
            <button 
              data-id="${f.id}" 
              class="open-course-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white text-xs font-semibold transition duration-200">
              Voir le programme
              <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </button>
          </div>
        </div>
      `;
    }).join('');

    if (window.lucide) window.lucide.createIcons();

    // Attach click listeners to open modal
    document.querySelectorAll('.open-course-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.currentTarget.dataset.id;
        const formation = formations.find(item => item.id === id);
        if (formation) {
          openCourseModal(formation);
        }
      });
    });
  }

  // 2. Open Course Details Modal
  function openCourseModal(formation) {
    selectedFormation = formation;
    if (!modalBody || !courseModal) return;

    modalBody.innerHTML = `
      <div class="p-6 md:p-8">
        <div class="flex items-center justify-between gap-4 mb-4">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
            <i data-lucide="award" class="w-3.5 h-3.5"></i>
            ${formation.domain}
          </span>
          <span class="text-xs font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full">
            Éligible Remboursement GIAC / CSF
          </span>
        </div>

        <h2 class="text-2xl md:text-3xl font-extrabold text-white mb-4 leading-tight">
          ${formation.title}
        </h2>

        ${formation.breadcrumbs && formation.breadcrumbs.length > 0 ? `
          <div class="text-xs text-slate-400 mb-6 flex flex-wrap items-center gap-1.5">
            <span class="text-slate-500">Parcours :</span>
            ${formation.breadcrumbs.map(b => `<span class="bg-slate-800/80 px-2 py-0.5 rounded text-slate-300">${b}</span>`).join(' <span class="text-slate-600">/</span> ')}
          </div>
        ` : ''}

        <!-- Objectives -->
        <div class="mb-6 bg-slate-900/60 p-5 rounded-xl border border-slate-800">
          <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-3 flex items-center gap-2 text-blue-400">
            <i data-lucide="target" class="w-4 h-4"></i>
            Objectifs & Compétences Visées
          </h4>
          ${formation.objectives && formation.objectives.length > 0 ? `
            <ul class="space-y-2 text-xs md:text-sm text-slate-300">
              ${formation.objectives.map(o => `
                <li class="flex items-start gap-2.5">
                  <i data-lucide="check" class="w-4 h-4 text-cyan-400 shrink-0 mt-0.5"></i>
                  <span>${o}</span>
                </li>
              `).join('')}
            </ul>
          ` : `
            <p class="text-sm text-slate-400">Acquisition de compétences opérationnelles, maîtrise des protocoles de sécurité et conformité aux normes marocaines.</p>
          `}
        </div>

        <!-- Content Outline -->
        <div class="mb-8">
          <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-3 flex items-center gap-2 text-cyan-400">
            <i data-lucide="book-open" class="w-4 h-4"></i>
            Contenu Indicatif du Programme
          </h4>
          ${formation.content && formation.content.length > 0 ? `
            <div class="space-y-2.5">
              ${formation.content.map((c, idx) => `
                <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-800/40 border border-slate-800/80">
                  <span class="w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 text-xs font-bold flex items-center justify-center shrink-0">
                    ${idx + 1}
                  </span>
                  <p class="text-xs md:text-sm text-slate-300">${c}</p>
                </div>
              `).join('')}
            </div>
          ` : `
            <p class="text-sm text-slate-400">Module complet alternant apports théoriques, simulations sur bancs d'essais et étude de cas concrets industriels.</p>
          `}
        </div>

        <!-- Action Footer inside Modal -->
        <div class="p-4 rounded-xl bg-blue-600/10 border border-blue-500/30 flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <div class="text-xs text-blue-400 font-semibold mb-0.5">Besoin d'un devis ou du formulaire F2 ?</div>
            <div class="text-xs text-slate-400">Nos conseillers vous répondent sous 2 heures ouvrées.</div>
          </div>
          <div class="flex items-center gap-2.5 w-full md:w-auto">
            <a 
              href="#contact" 
              onclick="document.getElementById('course-modal').classList.add('hidden'); document.getElementById('contact-subject').value='Réservation : ' + '${formation.title.replace(/'/g, "\\'")}';"
              class="w-full md:w-auto px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold text-center transition">
              Réserver cette formation
            </a>
            <a 
              href="https://meplus.ma/contactez-nous/" 
              target="_blank"
              class="w-full md:w-auto px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold text-center transition">
              Formulaire F2
            </a>
          </div>
        </div>
      </div>
    `;

    courseModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (window.lucide) window.lucide.createIcons();
  }

  function closeCourseModal() {
    if (!courseModal) return;
    courseModal.classList.add('hidden');
    document.body.style.overflow = 'auto';
  }

  closeModalBtn?.addEventListener('click', closeCourseModal);
  courseModal?.addEventListener('click', (e) => {
    if (e.target === courseModal) closeCourseModal();
  });

  // 3. Search & Domain Filter handlers
  searchInput?.addEventListener('input', (e) => {
    searchQuery = e.target.value;
    renderFormations();
  });

  domainTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      domainTabs.forEach(t => t.classList.remove('active-tab', 'bg-blue-600', 'text-white'));
      tab.classList.add('active-tab', 'bg-blue-600', 'text-white');
      currentDomain = tab.dataset.domain;
      renderFormations();
    });
  });

  // 4. Render Regulations Library
  const regulationsContainer = document.getElementById('regulations-grid');
  if (regulationsContainer) {
    regulationsContainer.innerHTML = regulations.map(reg => `
      <div class="glass-panel p-6 rounded-2xl flex flex-col justify-between border border-slate-800 hover:border-cyan-500/40 transition group">
        <div>
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
              <i data-lucide="file-check-2" class="w-3 h-3"></i>
              ${reg.category}
            </span>
            <span class="text-[11px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded font-mono">
              ${reg.bo}
            </span>
          </div>

          <h3 class="text-base font-bold text-white mb-2 group-hover:text-cyan-400 transition">
            ${reg.title}
          </h3>

          <p class="text-slate-400 text-xs mb-4 leading-relaxed line-clamp-3">
            ${reg.description}
          </p>
        </div>

        <div class="pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
          <span class="text-[11px] text-slate-400">
            Mise à jour : ${reg.date}
          </span>
          <a 
            href="${reg.pdf}" 
            target="_blank" 
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-cyan-600/20 hover:bg-cyan-600 text-cyan-300 hover:text-white text-xs font-semibold transition">
            <i data-lucide="download" class="w-3.5 h-3.5"></i>
            Télécharger BO
          </a>
        </div>
      </div>
    `).join('');
  }

  // 5. Render Consultants
  const consultantsContainer = document.getElementById('consultants-grid');
  if (consultantsContainer) {
    consultantsContainer.innerHTML = consultants.map(c => `
      <div class="glass-panel p-6 rounded-2xl flex flex-col justify-between border border-slate-800 hover:border-blue-500/40 transition group">
        <div>
          <div class="flex items-center gap-4 mb-4">
            <div class="w-14 h-14 rounded-xl overflow-hidden border-2 border-blue-500/30 shrink-0 bg-slate-800">
              <img src="${c.avatar}" alt="${c.name}" class="w-full h-full object-cover group-hover:scale-110 transition duration-300" />
            </div>
            <div>
              <span class="text-[10px] font-bold tracking-wider uppercase text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-md">
                ${c.tag}
              </span>
              <h3 class="text-base font-bold text-white mt-1 group-hover:text-blue-400 transition">
                ${c.name}
              </h3>
              <div class="text-xs text-slate-400 leading-tight line-clamp-1">${c.role}</div>
            </div>
          </div>

          <p class="text-slate-400 text-xs mb-4 leading-relaxed">
            ${c.credentials}
          </p>

          <div class="space-y-1 mb-4">
            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Expertises clés :</div>
            <div class="flex flex-wrap gap-1.5">
              ${c.specialties.map(s => `
                <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800/80 text-slate-300 border border-slate-700/50">
                  ${s}
                </span>
              `).join('')}
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-blue-400 font-medium">
          <span>Cabinet ME PLUS</span>
          <span class="flex items-center gap-1 text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Disponible
          </span>
        </div>
      </div>
    `).join('');
  }

  // 6. Interactive GIAC / CSF Reimbursement Simulator
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
    const employees = employeesInput ? parseInt(employeesInput.value, 10) : 50;

    if (budgetValEl) budgetValEl.textContent = new Intl.NumberFormat('fr-MA').format(budget) + ' MAD';
    if (employeesValEl) employeesValEl.textContent = employees + ' salariés';

    // Calculation logic based on Moroccan CSF rules (70-80% reimbursement on approved CSF programs)
    const csfRate = 0.70;
    const csfRefund = Math.round(budget * csfRate);
    // GIAC covers up to 70% of diagnostic & engineering studies
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

  // 7. Toast notification helper
  function showToast(title, msg, type = 'success') {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = `p-4 rounded-xl shadow-2xl backdrop-blur-xl border flex items-start gap-3 transition-all duration-300 transform translate-y-4 opacity-0 ${
      type === 'success' ? 'bg-slate-900/95 border-emerald-500/40 text-emerald-300' : 'bg-slate-900/95 border-blue-500/40 text-blue-300'
    }`;
    toast.innerHTML = `
      <div class="shrink-0 mt-0.5">
        <i data-lucide="${type === 'success' ? 'check-circle-2' : 'info'}" class="w-5 h-5"></i>
      </div>
      <div class="text-xs">
        <div class="font-bold text-white mb-0.5">${title}</div>
        <div class="text-slate-300">${msg}</div>
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
    }, 4000);
  }

  // 8. Contact form handler
  const contactForm = document.getElementById('contact-form');
  contactForm?.addEventListener('submit', (e) => {
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
        btn.innerHTML = `<i data-lucide="check" class="w-4 h-4 inline mr-2"></i> Message Envoyé !`;
      }
      showToast('Demande Reçue avec Succès !', 'Notre équipe ME PLUS vous contactera sous 2h avec le devis et le formulaire F2.', 'success');
      contactForm.reset();
      setTimeout(() => {
        if (btn) btn.innerHTML = `Envoyer la Demande & Obtenir le Devis <i data-lucide="arrow-right" class="w-4 h-4 inline ml-2"></i>`;
        if (window.lucide) window.lucide.createIcons();
      }, 3000);
    }, 900);
  });

  // 9. Mobile menu drawer
  mobileMenuBtn?.addEventListener('click', () => {
    mobileDrawer?.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  });

  closeDrawerBtn?.addEventListener('click', () => {
    mobileDrawer?.classList.add('hidden');
    document.body.style.overflow = 'auto';
  });

  document.querySelectorAll('.drawer-link').forEach(link => {
    link.addEventListener('click', () => {
      mobileDrawer?.classList.add('hidden');
      document.body.style.overflow = 'auto';
    });
  });

  // Initial render
  renderFormations();
});
