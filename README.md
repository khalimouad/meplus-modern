# ⚡ ME PLUS | Modern Industrial Web Platform (2026 Edition)

![ME PLUS Banner](https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=1200&h=400&fit=crop&crop=center)

> **Next-Generation B2B Portal for Industrial Training, Safety Management & Maintenance Engineering in Morocco.**  
> Redesigned with the cutting-edge design trends of 2026, preserving ME PLUS's signature deep navy and electric blue identity while delivering fluid animations, real-time course search, an interactive Moroccan CSF/GIAC reimbursement simulator, and a mobile-first user experience.

---

## 🌟 Key Highlights & 2026 Features

### 🎨 1. 2026 Industrial Design System
- **Mesh Gradients & Ambient Glows**: Deep oceanic navy (`#070e1b`), cobalt blue (`#2563eb`), and cyan accents (`#06b6d4`).
- **Glassmorphic Bento Grid**: Multi-layered backdrop blurs (`backdrop-blur-xl`), subtle neon borders, and tactile elevations.
- **Client Logo Marquee**: Infinite smooth sliding ticker showcasing Morocco's top industrial firms (*OCP Group, SONASID, LafargeHolcim, Centrale Danone, Aïcha, Nexans, Yazaki, Suzuki, etc.*).

### 🔍 2. Interactive Course Catalog (85+ Real Modules)
- **Instant Client-Side Search**: Search in real-time across all 85 programs by keywords, equipment (Siemens, PLC, presses), or regulatory codes (NM 06.1.225).
- **Domain Filter Badges**:
  - ⚡ **Sécurité & Hygiène** (30 modules)
  - ⚙️ **Technique Industrielle** (37 modules)
  - 📊 **Management & Performance** (10 modules)
  - 🎯 **Qualité & DMO** (8 modules)
- **Dynamic Program Modal**: Click any card to pop up the complete pedagogical syllabus, target audience, and prerequisite list without refreshing the page.

### 💰 3. Interactive Moroccan CSF & GIAC Reimbursement Simulator
- Interactive sliders for **Annual Training Budget** and **Headcount**.
- Real-time calculation of estimated reimbursement under Moroccan Law (up to **70% to 80%** covered by the *Contrats Spéciaux de Formation* / OFPPT and GIAC diagnostic studies).
- Direct download triggers for official Moroccan CSF registration forms (**Formulaire F2**).

### 📑 4. Moroccan Legal Compliance Library (Bulletins Officiels)
- Direct PDF download links for official Moroccan decrees and Dahirs:
  - *Décret n° 2.14.499* (Sécurité Incendie dans les constructions)
  - *Décret n° 2.12.236* (Sécurité des machines et carterage)
  - *Appareils à vapeur et à pression de gaz* (BO n° 2207 & 2623)
  - *Appareils de levage et ponts roulants* (BO n° 2066)
  - *Code du Travail marocain (Loi 65-99 - Hygiène et Sécurité)*

### 📱 5. Mobile-First Navigation & Floating Bottom Bar
- Custom floating bottom dock for one-thumb mobile browsing (*Accueil*, *Formations*, *Simulateur*, *Lois B.O.*, *Contact*).
- Full-screen animated mobile drawer with quick direct-call triggers (`+212 6 61 45 02 48`).

---

## 📂 Project Structure

```text
meplus-modern/
├── index.html           # Main semantic HTML5 single-page application
├── app.js               # Reactive state controller, search filters, modals & simulator
├── data.js              # Consolidated JSON dataset (85 formations, 12 regulations, consultants)
├── style.css            # 2026 design tokens, mesh animations & custom range sliders
├── AUDIT_MEPLUS_FULL.md # Complete audit and extraction of the original meplus.ma site
└── README.md            # Documentation & deployment guide
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
