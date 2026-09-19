# ⚡ ME PLUS | Modern Industrial Multi-Page Platform (2026 Edition)

![ME PLUS Banner](https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=1200&h=400&fit=crop&crop=center)

> **Next-Generation B2B Multi-Page Portal for Industrial Training, Safety Management & Maintenance Engineering in Morocco.**  
> Built with modern minimalist high-precision aesthetics (inspired by floating pill capsule navigation & sculptured industrial wave layouts), **Light Mode first** by default with an instant Dark Mode toggle, preserving ME PLUS's signature deep oceanic navy and vivid electric cyan identity.

---

## 🌟 Key Features & Multi-Page Architecture

### 🧭 1. Complete Multi-Page Architecture (8 Dedicated Pages)
- **`index.html` (Accueil)** : Hero with curved arch container, Moroccan homologations (DFP, OFPPT, GIAC), trust metrics, client logo marquee (*OCP, SONASID, LafargeHolcim, Nexans, Aïcha, etc.*), 2-column "About" section with floating badge, 4-pillar bento grid, popular course preview, CSF reimbursement teaser, and verified testimonials.
- **`formations.html` (Catalogue des 85+ Formations)** : Split hero inspired by the reference design with real Moroccan industrial engineers (`assets/hero_engineers.jpg`), floating CSF 80% card, instant search + filters button, domain pills with count badges (*Toutes 85, Sécurité 30, Technique 37, Management 10, Qualité 8*), sort by relevance/name, crisp cards with `Voir le programme ↗`, and bottom pagination (`< 1 2 3 ... 10 >`).
- **`formation-detail.html` (Page Dédiée par Formation)** : Single dedicated page for every course with a prominent **"← Retour au catalogue"** button, dynamic breadcrumb, full objectives checklist, numbered modular syllabus, pedagogical methodology (70% practice, test benches), Moroccan legal conformity, CSF 80% simulation link, instant quote request pre-fill, and related courses.
- **`services.html` (Services & Audits Industriels)** : In-depth presentation of GPEC engineering, regulatory audits (*Décret 2.14.499 Incendie*, *Décret 2.12.236 Machines*), 6-step TPM maintenance accompaniment, and Moroccan electrical qualifications (*NM 06.1.225*).
- **`simulateur.html` (Simulateur Remboursement GIAC / CSF)** : Interactive financial calculator with real-time sliders for training budget and employee headcount, Moroccan legal reimbursement breakdown (up to 70-80%), and step-by-step procedure.
- **`reglementation.html` (Centre de Documentation Réglementaire)** : Downloadable official Bulletins Officiels (B.O.), Dahirs, and ministerial decrees with employer legal responsibility checklists.
- **`consultants.html` (Équipe d'Experts & Consultants Seniors)** : Profiles of senior industrial experts, former plant directors, accredited auditors, and the ME PLUS pedagogical charter.
- **`contact.html` (Contact, Devis Express & Formulaire F2)** : Interactive quote request form with automatic URL pre-fill from course cards (`?formation=...`), Casablanca headquarters details, phone/WhatsApp pro, and client FAQ.

### ☀️ 2. Light Mode First with Dark Mode Toggle & High Contrast
- **Default Light Theme** : Clean, crisp white and light slate surfaces (`#f8fafc`), high-contrast ink-black typography (`#0f172a`), refined floating pill capsule header, vivid cyan and electric blue accents.
- **Vibrant, Less Transparent Hero Images** : Re-calibrated hero headers with high saturation (+35%), enhanced contrast, and subtle text-backdrop gradient overlays ensuring 100% WCAG AAA readability.
- **Dark Mode Support** : Seamless toggle via Sun/Moon button stored in `localStorage`, transforming into deep metallic oceanic navy curves.
- **Zero FOUC** : Synchronous preloader script avoids any flash of unstyled theme on page transitions.

---

## 📂 Project Structure

```text
meplus-modern/
├── index.html              # Accueil / Home page
├── formations.html         # 85+ Course catalog with reference hero & pagination
├── formation-detail.html   # Dedicated course page with return button & full syllabus
├── services.html           # GPEC, TPM, Audits & Habilitations guide
├── simulateur.html         # Interactive Moroccan CSF & GIAC reimbursement simulator
├── reglementation.html     # Legal compliance library & Bulletins Officiels downloads
├── consultants.html        # Senior consultants and industrial experts roster
├── contact.html            # Express quote request, URL pre-fill & Casablanca HQ
├── assets/                 # High-resolution generated photography (hero_engineers.jpg, hero_plant.jpg)
├── app.js                  # Reactive multi-page state controller & theme manager
├── data.js                 # Consolidated JSON dataset (85 formations, 12 regulations, consultants)
├── style.css               # Light-mode-first tokens, floating capsule navbar & mesh curves
├── AUDIT_MEPLUS_FULL.md    # Complete audit and extraction of the original meplus.ma site
└── README.md               # Documentation & deployment guide
```

---

## 🚀 Quickstart & Local Preview

This project is built using modern native ES modules and zero build dependencies. You can run it instantly with any local web server:

### Using Python:
```bash
python3 -m http.server 8080
```
Then visit [http://localhost:8080](http://localhost:8080) in your browser.

### Using Node.js (npx):
```bash
npx serve .
```

---

## 🌐 Deploy to GitHub Pages

To host this website for free on GitHub Pages:
1. Go to your repository settings on GitHub: **Settings > Pages**.
2. Under **Build and deployment**, select **Deploy from a branch**.
3. Choose the `main` branch and `/ (root)` folder, then click **Save**.
4. Your site will be live within seconds at `https://<username>.github.io/meplus-modern/`.

---

## 🏢 About ME PLUS
- **Company**: ME PLUS (continuing ME Consult established in 2004)
- **Accreditations**: Homologué DFP, OFPPT, GIAC, AFOM, Maroc PME
- **Headquarters**: Rue Moussa Al Kadim, Imm. Le Lys N° 21, Bourgogne, Casablanca, Morocco
- **Official Contact**: `+212 6 61 45 02 48` | `formation@meplus.ma`
- **Original Portal**: [https://meplus.ma/](https://meplus.ma/)

---
*Developed with pair programming assistance from Antigravity.*
