
const CITIES = ['Casablanca','Rabat','Marrakech','Tanger','Agadir','Fès','Meknès','Oujda','Tétouan','Autre ville'];
const STATUSES = ['Nouvelle','Confirmée','En préparation','Expédiée','Livrée'];
const FREE_SHIP = 600;

const STATUS_STYLE = {
  'Nouvelle': ['#EDF1F6', 'oklch(0.42 0.09 250)'],
  'Confirmée': ['#EDF1F6', 'oklch(0.42 0.09 250)'],
  'En préparation': ['#F4EFE4', '#8A6A22'],
  'Expédiée': ['#EAF0EA', '#3F6B45'],
  'Livrée': ['#E8EDE8', '#2F5A38'],
  'Annulée': ['#F7E9E7', '#A83A30']
};

class Component extends DCLogic {
  constructor(props) {
    super(props);
    const base = (window.SCM_PRODUCTS || []).map(p => Object.assign({}, p, { reserved: 0 }));
    this.state = {
      view: 'home', admin: false, adminPage: 'dash',
      products: base,
      cart: [], drawer: false, wish: [],
      mega: false, searchOpen: false, query: '',
      filterCat: null, filterIng: null, filterConcern: null, sort: 'featured', shopTitle: null,
      pdpId: null, gal: 0, qty: 1, tab: 'benefits', heroSlide: 0, heroLine: 0, heroTick: 0, quotePg: 0, ingName: 'Caviar', artIdx: 0,
      coupon: '', couponCode: null, ship: 'standard', pay: 'cod',
      form: { first: '', last: '', phone: '', email: '', addr: '', city: 'Casablanca', zip: '' },
      formError: '',
      orders: this.seedOrders(base),
      orderNo: null, note: '', movements: [],
      editId: null, edit: null, adminQuery: '', orderFilter: 'Toutes',
      newsletter: '', toast: '', lastOrder: null,
      reviewFilter: 'En attente',
      reviews: [
        { id: 1, pid: '152', who: 'Salma B.', date: '24/08/2026', stars: 5, verified: true, status: 'En attente', featured: false, text: 'Ma peau est visiblement plus lisse après trois semaines, sans aucune irritation. Je vais commander la version nuit.' },
        { id: 2, pid: '227', who: 'Youssef I.', date: '22/08/2026', stars: 5, verified: true, status: 'En attente', featured: false, text: 'Commandé un soir, livré le lendemain à Rabat, payé à la réception. Produit conforme et bien emballé.' },
        { id: 3, pid: '222', who: 'Imane C.', date: '19/08/2026', stars: 4, verified: true, status: 'Approuvé', featured: true, text: 'Les taches sur mes joues se sont nettement atténuées en deux mois. Il faut être patiente mais ça fonctionne.' },
        { id: 4, pid: '215', who: 'Anonyme', date: '17/08/2026', stars: 1, verified: false, status: 'En attente', featured: false, text: 'Message promotionnel sans rapport avec le produit, contient un lien externe.' },
        { id: 5, pid: '259', who: 'Sofia N.', date: '12/08/2026', stars: 5, verified: true, status: 'Approuvé', featured: false, text: 'Produit identique à celui acheté à Londres, et le conseil par WhatsApp avant l\'achat a été utile.' },
        { id: 6, pid: '230', who: 'Karim T.', date: '08/08/2026', stars: 2, verified: true, status: 'Rejeté', featured: false, text: 'Trop asséchant pour ma peau, mais le service client a bien géré le retour.' }
      ],
      promos: [
        { code: 'MAROC10', name: 'Bienvenue Maroc', type: 'Pourcentage', value: '−10%', cond: 'Sans minimum', period: '01/08 → 30/09', uses: 184, active: true },
        { code: 'LIVRAISONOFFERTE', name: 'Livraison offerte', type: 'Livraison', value: 'Frais offerts', cond: 'Dès 400 MAD', period: '15/08 → 15/09', uses: 96, active: true },
        { code: 'CAVIAR15', name: 'Gamme Caviar', type: 'Collection', value: '−15%', cond: 'Produits Caviar', period: '20/08 → 05/09', uses: 41, active: true },
        { code: 'DUO2X1', name: 'Deuxième à −50%', type: 'Buy X Get Y', value: '2e à −50%', cond: '2 articles minimum', period: '01/09 → 30/09', uses: 0, active: false },
        { code: 'PREMIERE', name: 'Première commande', type: 'Fixe', value: '−50 MAD', cond: 'Nouveaux clients', period: 'Permanent', uses: 312, active: true }
      ],
      cms: [
        { name: 'Diaporama d\'accueil', note: '3 slides · rotation automatique', on: true },
        { name: 'Best-sellers', note: 'Carrousel · 7 produits', on: true },
        { name: 'Acheter par actif', note: 'Grille · 8 actifs', on: true },
        { name: 'Campagne Caviar', note: 'Bandeau éditorial sombre', on: true },
        { name: 'Coffrets & rituels', note: '3 coffrets', on: true },
        { name: 'Avis clients', note: 'Note moyenne + 4 avis', on: true },
        { name: 'Le Lab', note: '3 derniers articles', on: true },
        { name: 'Services & garanties', note: '5 blocs', on: true }
      ],
      annonces: ['Livraison offerte dès 600 MAD', 'Paiement à la livraison partout au Maroc', 'Distributeur agréé'],
      labStatus: {},
      set: { store: 'skinChemists Maroc', email: 'contact@skinchemists.ma', phone: '+212 5 22 00 00 00', lang: 'Français', currency: 'MAD (د.م.)', freeShip: '600', ship: '35', tax: '20', low: '5' },
      langs: ['Français', 'Arabe'],
      payOn: { cod: true, card: true, transfer: false }
    };
  }

  componentDidMount() {
    this._hero = setInterval(() => {
      const st0 = this.state;
      if (st0.view !== 'home' || st0.admin || st0.searchOpen || st0.drawer) return;
      this.setState(st => st.heroLine < 2
        ? { heroLine: st.heroLine + 1, heroTick: st.heroTick + 1 }
        : { heroLine: 0, heroTick: st.heroTick + 1, heroSlide: (st.heroSlide + 1) % 3 });
    }, 2100);
  }
  componentWillUnmount() { clearInterval(this._hero); clearTimeout(this._t); }

  /* ---------- helpers ---------- */
  mad(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' MAD'; }
  eff(p) { return p.sale || p.price; }
  find(id) { return this.state.products.find(p => p.id === id); }
  flash(msg) {
    clearTimeout(this._t);
    this.setState({ toast: msg });
    this._t = setTimeout(() => this.setState({ toast: '' }), 2200);
  }
  now() {
    const d = new Date();
    return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
  }

  seedOrders(base) {
    const pick = (id) => base.find(p => p.id === id);
    const mk = (no, date, first, last, phone, city, addr, zip, its, pay, payStatus, status, ship, tl) => {
      const items = its.map(([id, qty]) => {
        const p = pick(id) || base[0];
        return { id: p.id, name: p.name, sku: p.sku, qty: qty, unit: p.sale || p.price, img: p.images[0] };
      });
      const sub = items.reduce((s, i) => s + i.unit * i.qty, 0);
      const shipCost = ship === 'express' ? 60 : (sub >= FREE_SHIP ? 0 : 35);
      return { no, date, first, last, name: first + ' ' + last, phone, email: (first + '.' + last).toLowerCase() + '@gmail.com',
        city, addr, zip, items, sub, shipCost, discount: 0, coupon: null, total: sub + shipCost,
        pay, payStatus, status, ship, tracking: status === 'Expédiée' || status === 'Livrée' ? 'AMX' + no.slice(-6) + 'MA' : '',
        timeline: tl, note: '' };
    };
    return [
      mk('SCM-1042', '30/08/2026', 'Salma', 'Benali', '06 61 22 84 10', 'Casablanca', '12 rue Ibn Batouta, Maârif', '20250', [['259', 1], ['222', 1]], 'Paiement à la livraison', 'En attente', 'Nouvelle', 'standard', [{ text: 'Commande reçue', time: '09:14', who: 'Site web', dot: '#14120F' }]),
      mk('SCM-1041', '30/08/2026', 'Youssef', 'Idrissi', '06 70 41 02 77', 'Rabat', '8 avenue Fal Ould Oumeir, Agdal', '10080', [['227', 2]], 'Carte bancaire', 'Payé', 'Confirmée', 'express', [{ text: 'Commande reçue', time: '08:02', who: 'Site web', dot: '#14120F' }, { text: 'Paiement confirmé', time: '08:03', who: 'CMI', dot: 'oklch(0.48 0.09 250)' }, { text: 'Commande confirmée par téléphone', time: '08:41', who: 'S. Alaoui', dot: 'oklch(0.48 0.09 250)' }]),
      mk('SCM-1040', '29/08/2026', 'Imane', 'Chraibi', '06 12 88 45 91', 'Marrakech', 'Résidence Al Massira, Gueliz', '40000', [['211', 1], ['257', 1], ['225', 1]], 'Paiement à la livraison', 'En attente', 'En préparation', 'standard', [{ text: 'Commande reçue', time: '17:22', who: 'Site web', dot: '#14120F' }, { text: 'Commande confirmée', time: '18:05', who: 'S. Alaoui', dot: 'oklch(0.48 0.09 250)' }, { text: 'Préparation lancée — entrepôt Casablanca', time: '09:10', who: 'M. Berrada', dot: '#8A6A22' }]),
      mk('SCM-1039', '29/08/2026', 'Nadia', 'Kettani', '06 55 30 19 06', 'Tanger', '44 rue de Belgique', '90000', [['152', 1], ['155', 1]], 'Paiement à la livraison', 'En attente', 'Expédiée', 'standard', [{ text: 'Commande reçue', time: '11:48', who: 'Site web', dot: '#14120F' }, { text: 'Commande confirmée', time: '12:30', who: 'S. Alaoui', dot: 'oklch(0.48 0.09 250)' }, { text: 'Colis remis au transporteur', time: '16:20', who: 'M. Berrada', dot: '#3F6B45' }]),
      mk('SCM-1038', '28/08/2026', 'Hamza', 'El Fassi', '06 44 71 55 23', 'Agadir', 'Lot Amsernat, n°112', '80000', [['245', 1], ['253', 1]], 'Carte bancaire', 'Payé', 'Livrée', 'express', [{ text: 'Commande reçue', time: '10:05', who: 'Site web', dot: '#14120F' }, { text: 'Paiement confirmé', time: '10:06', who: 'CMI', dot: 'oklch(0.48 0.09 250)' }, { text: 'Colis expédié', time: '15:40', who: 'M. Berrada', dot: '#3F6B45' }, { text: 'Livré et encaissé', time: '12:12', who: 'Transporteur', dot: '#2F5A38' }]),
      mk('SCM-1037', '28/08/2026', 'Sofia', 'Naciri', '06 78 09 63 44', 'Fès', '19 avenue Hassan II', '30000', [['199', 1], ['230', 1]], 'Paiement à la livraison', 'En attente', 'Livrée', 'standard', [{ text: 'Commande reçue', time: '13:31', who: 'Site web', dot: '#14120F' }, { text: 'Commande confirmée', time: '14:02', who: 'S. Alaoui', dot: 'oklch(0.48 0.09 250)' }, { text: 'Colis expédié', time: '18:15', who: 'M. Berrada', dot: '#3F6B45' }, { text: 'Livré et encaissé', time: '11:44', who: 'Transporteur', dot: '#2F5A38' }])
    ];
  }

  /* ---------- navigation ---------- */
  nav(view, extra) { this.setState(Object.assign({ view: view, admin: false, mega: false, searchOpen: false }, extra || {})); window.scrollTo(0, 0); }
  shop(opts, title) { this.nav('shop', Object.assign({ filterCat: null, filterIng: null, filterConcern: null, shopTitle: title || null }, opts)); }
  openPdp(id) { this.nav('pdp', { pdpId: id, gal: 0, qty: 1, tab: 'benefits' }); }

  /* ---------- cart ---------- */
  add(id, qty) {
    const p = this.find(id);
    if (!p || p.stock <= 0) { this.flash('Produit en rupture de stock'); return; }
    const cart = this.state.cart.slice();
    const line = cart.find(c => c.id === id);
    if (line) line.qty = Math.min(p.stock, line.qty + (qty || 1));
    else cart.push({ id: id, qty: Math.min(p.stock, qty || 1) });
    this.setState({ cart: cart, drawer: true });
  }
  setQty(id, d) {
    const p = this.find(id);
    const cart = this.state.cart.map(c => c.id === id ? { id: id, qty: Math.max(1, Math.min(p.stock, c.qty + d)) } : c);
    this.setState({ cart: cart });
  }
  remove(id) { this.setState({ cart: this.state.cart.filter(c => c.id !== id) }); }
  freeShip() { return parseInt(this.state.set.freeShip, 10) || FREE_SHIP; }
  subtotal() { return this.state.cart.reduce((s, c) => { const p = this.find(c.id); return s + (p ? this.eff(p) * c.qty : 0); }, 0); }
  discountAmt() { const sub = this.subtotal(); return this.state.couponCode === 'MAROC10' ? Math.round(sub * 0.1) : 0; }
  shipCost() { const net = this.subtotal() - this.discountAmt(); if (this.state.ship === 'express') return 60; return net >= this.freeShip() ? 0 : (parseInt(this.state.set.ship, 10) || 35); }
  total() { return this.subtotal() - this.discountAmt() + this.shipCost(); }

  submitOrder(e) {
    if (e && e.preventDefault) e.preventDefault();
    const f = this.state.form;
    if (!this.state.cart.length) { this.setState({ formError: 'Votre panier est vide.' }); return; }
    if (!f.first || !f.last || !f.phone || !f.addr) { this.setState({ formError: 'Merci de renseigner prénom, nom, téléphone et adresse.' }); return; }
    const items = this.state.cart.map(c => {
      const p = this.find(c.id);
      return { id: p.id, name: p.name, sku: p.sku, qty: c.qty, unit: this.eff(p), img: p.images[0] };
    });
    const num = 1043 + this.state.orders.filter(o => o.no.indexOf('SCM-10') === 0 && +o.no.slice(4) >= 1043).length;
    const order = {
      no: 'SCM-' + num, date: '30/08/2026', first: f.first, last: f.last, name: f.first + ' ' + f.last,
      phone: f.phone, email: f.email || '—', city: f.city, addr: f.addr, zip: f.zip, items: items,
      sub: this.subtotal(), discount: this.discountAmt(), coupon: this.state.couponCode,
      shipCost: this.shipCost(), total: this.total(),
      pay: this.state.pay === 'cod' ? 'Paiement à la livraison' : (this.state.pay === 'card' ? 'Carte bancaire' : 'Virement bancaire'),
      payStatus: this.state.pay === 'card' ? 'Payé' : 'En attente',
      status: 'Nouvelle', ship: this.state.ship, tracking: '', note: '',
      timeline: [{ text: 'Commande reçue', time: this.now(), who: 'Site web', dot: '#14120F' }]
    };
    const products = this.state.products.map(p => {
      const line = items.find(i => i.id === p.id);
      return line ? Object.assign({}, p, { stock: Math.max(0, p.stock - line.qty) }) : p;
    });
    const movements = items.map(i => {
      const before = this.find(i.id).stock;
      return { name: i.name, sku: i.sku, delta: '−' + i.qty, before: before, after: Math.max(0, before - i.qty), type: 'Vente ' + order.no, time: this.now(), who: 'Système', color: '#A83A30' };
    }).concat(this.state.movements);
    this.setState({ orders: [order].concat(this.state.orders), products: products, movements: movements, cart: [], drawer: false, view: 'confirm', lastOrder: order, formError: '', couponCode: null, coupon: '' });
    window.scrollTo(0, 0);
  }

  advance() {
    const o = this.currentOrder(); if (!o) return;
    const i = STATUSES.indexOf(o.status);
    if (i < 0 || i >= STATUSES.length - 1) return;
    const next = STATUSES[i + 1];
    const labels = { 'Confirmée': 'Commande confirmée par téléphone', 'En préparation': 'Préparation lancée — entrepôt Casablanca', 'Expédiée': 'Colis remis au transporteur', 'Livrée': 'Livré et encaissé' };
    const dots = { 'Confirmée': 'oklch(0.48 0.09 250)', 'En préparation': '#8A6A22', 'Expédiée': '#3F6B45', 'Livrée': '#2F5A38' };
    const orders = this.state.orders.map(x => x.no !== o.no ? x : Object.assign({}, x, {
      status: next,
      payStatus: next === 'Livrée' && x.pay === 'Paiement à la livraison' ? 'Payé' : x.payStatus,
      tracking: next === 'Expédiée' ? 'AMX' + x.no.slice(-6) + 'MA' : x.tracking,
      timeline: x.timeline.concat([{ text: labels[next], time: this.now(), who: 'Y. Amrani', dot: dots[next] }])
    }));
    this.setState({ orders: orders });
    this.flash('Statut mis à jour : ' + next);
  }
  cancel() {
    const o = this.currentOrder(); if (!o) return;
    const products = this.state.products.map(p => {
      const line = o.items.find(i => i.id === p.id);
      return line ? Object.assign({}, p, { stock: p.stock + line.qty }) : p;
    });
    const movements = o.items.map(i => {
      const before = this.find(i.id) ? this.find(i.id).stock : 0;
      return { name: i.name, sku: i.sku, delta: '+' + i.qty, before: before, after: before + i.qty, type: 'Annulation ' + o.no, time: this.now(), who: 'Y. Amrani', color: '#2F5A38' };
    }).concat(this.state.movements);
    const orders = this.state.orders.map(x => x.no !== o.no ? x : Object.assign({}, x, {
      status: 'Annulée', payStatus: x.payStatus === 'Payé' ? 'Remboursé' : 'Annulé',
      timeline: x.timeline.concat([{ text: 'Commande annulée — stock restitué', time: this.now(), who: 'Y. Amrani', dot: '#A83A30' }])
    }));
    this.setState({ orders: orders, products: products, movements: movements });
    this.flash('Commande annulée, stock restitué');
  }
  currentOrder() { return this.state.orders.find(o => o.no === this.state.orderNo) || this.state.orders[0]; }

  adjust(id, delta, type) {
    const p = this.find(id); if (!p) return;
    const before = p.stock, after = Math.max(0, before + delta);
    if (after === before) return;
    const products = this.state.products.map(x => x.id === id ? Object.assign({}, x, { stock: after }) : x);
    const mv = { name: p.name, sku: p.sku, delta: (after > before ? '+' : '−') + Math.abs(after - before), before: before, after: after, type: type, time: this.now(), who: 'Y. Amrani', color: after > before ? '#2F5A38' : '#A83A30' };
    this.setState({ products: products, movements: [mv].concat(this.state.movements) });
  }

  setReview(id, patch, msg) {
    this.setState({ reviews: this.state.reviews.map(r => r.id === id ? Object.assign({}, r, patch) : r) });
    this.flash(msg);
  }
  moveCms(i, d) {
    const list = this.state.cms.slice();
    const j = i + d;
    if (j < 0 || j >= list.length) return;
    const tmp = list[i]; list[i] = list[j]; list[j] = tmp;
    this.setState({ cms: list });
  }
  setAnnonce(i, v) {
    const a = this.state.annonces.slice(); a[i] = v;
    this.setState({ annonces: a });
  }
  setSet(k, v) { this.setState({ set: Object.assign({}, this.state.set, { [k]: v }) }); }

  saveProduct() {
    const e = this.state.edit; if (!e) return;
    const before = this.find(e.id).stock;
    const after = Math.max(0, parseInt(e.stock, 10) || 0);
    const products = this.state.products.map(p => p.id !== e.id ? p : Object.assign({}, p, {
      name: e.name, short: e.short,
      price: parseFloat(e.price) || p.price,
      sale: e.sale === '' || e.sale === null ? null : parseFloat(e.sale),
      stock: after, low: parseInt(e.low, 10) || 5
    }));
    let movements = this.state.movements;
    if (after !== before) {
      movements = [{ name: e.name, sku: e.sku, delta: (after > before ? '+' : '−') + Math.abs(after - before), before: before, after: after, type: 'Correction fiche produit', time: this.now(), who: 'Y. Amrani', color: after > before ? '#2F5A38' : '#A83A30' }].concat(movements);
    }
    this.setState({ products: products, movements: movements, editId: null, edit: null });
    this.flash('Produit enregistré — boutique mise à jour');
  }

  /* ---------- derived ---------- */
  card(p) {
    const hasSale = !!p.sale && p.sale < p.price;
    return {
      id: p.id, name: p.name, brand: p.brand, cat: p.cat, img: p.images[0],
      priceStr: this.mad(this.eff(p)), oldStr: this.mad(p.price), hasSale: hasSale,
      discount: hasSale ? '−' + Math.round((1 - p.sale / p.price) * 100) + '%' : '',
      rating: p.rating.toFixed(1), reviews: p.reviews,
      soldOut: p.stock === 0, lowStock: p.stock > 0 && p.stock <= p.low,
      stockLabel: p.stock === 0 ? 'Rupture' : 'Plus que ' + p.stock,
      cta: p.stock === 0 ? 'Me prévenir' : 'Ajouter',
      open: () => this.openPdp(p.id),
      add: () => p.stock === 0 ? this.flash('Vous serez prévenu du retour en stock') : this.add(p.id, 1)
    };
  }

  renderVals() {
    const s = this.state, P = s.products;
    const isStore = !s.admin, isAdmin = s.admin;
    const sub = this.subtotal(), disc = this.discountAmt();

    /* shop grid */
    let grid = P.filter(p =>
      (!s.filterCat || p.cat === s.filterCat) &&
      (!s.filterIng || p.ingredient === s.filterIng) &&
      (!s.filterConcern || p.concern === s.filterConcern));
    if (s.sort === 'asc') grid = grid.slice().sort((a, b) => this.eff(a) - this.eff(b));
    if (s.sort === 'desc') grid = grid.slice().sort((a, b) => this.eff(b) - this.eff(a));
    if (s.sort === 'rating') grid = grid.slice().sort((a, b) => b.rating - a.rating);

    const countBy = (key, val) => P.filter(p => p[key] === val).length;
    const uniq = (key) => Array.from(new Set(P.map(p => p[key])));
    const filterList = (key, active, setter) => uniq(key).map(v => ({
      name: v, count: countBy(key, v), color: active === v ? 'oklch(0.42 0.09 250)' : '#14120F',
      go: () => setter(active === v ? null : v)
    }));

    /* pdp */
    const p = this.find(s.pdpId) || P[0];
    const hasSale = !!p.sale && p.sale < p.price;
    const inCartQty = (s.cart.find(c => c.id === p.id) || {}).qty || 0;
    const dist = [[5, 78], [4, 15], [3, 5], [2, 1], [1, 1]].map(d => ({ star: d[0], pct: d[1] + '%', n: Math.round(p.reviews * d[1] / 100) }));

    /* orders */
    const styleFor = (st) => STATUS_STYLE[st] || STATUS_STYLE['Nouvelle'];
    const orderRow = (o) => ({
      no: o.no, date: o.date, name: o.name, phone: o.phone, city: o.city,
      itemCount: o.items.reduce((n, i) => n + i.qty, 0) + ' art.',
      pay: o.pay === 'Paiement à la livraison' ? 'COD' : o.pay,
      payStatus: o.payStatus, payColor: o.payStatus === 'Payé' ? '#2F5A38' : '#8A6A22',
      status: o.status, statusBg: styleFor(o.status)[0], statusColor: styleFor(o.status)[1],
      totalStr: this.mad(o.total),
      open: () => this.setState({ admin: true, adminPage: 'order', orderNo: o.no, note: o.note || '' })
    });
    const filtered = s.orderFilter === 'Toutes' ? s.orders : s.orders.filter(o => o.status === s.orderFilter);
    const cur = this.currentOrder();

    /* kpis */
    const custMap = {};
    s.orders.forEach(o => {
      const k = o.name;
      if (!custMap[k]) custMap[k] = { name: o.name, email: o.email, phone: o.phone, city: o.city, orderCount: 0, spentRaw: 0, last: o.date, pay: o.pay === 'Paiement à la livraison' ? 'COD' : 'Carte' };
      custMap[k].orderCount += 1;
      if (o.status !== 'Annulée') custMap[k].spentRaw += o.total;
    });
    const custs = Object.keys(custMap).map(k => custMap[k]).sort((a, b) => b.spentRaw - a.spentRaw);
    const revenue = s.orders.filter(o => o.status !== 'Annulée').reduce((t, o) => t + o.total, 0);
    const nOrders = s.orders.filter(o => o.status !== 'Annulée').length;
    const cityTotals = {};
    s.orders.forEach(o => { if (o.status !== 'Annulée') cityTotals[o.city] = (cityTotals[o.city] || 0) + o.total; });
    const maxCity = Math.max.apply(null, [1].concat(Object.values(cityTotals)));
    const revSeries = [3, 5, 4, 7, 6, 9, 8, 6, 10, 12, 9, 14, 11, 16];
    const maxRev = 16;
    const days = ['17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30'];

    const invValue = P.reduce((t, x) => t + this.eff(x) * x.stock, 0);
    const lowCount = P.filter(x => x.stock > 0 && x.stock <= x.low).length;
    const outCount = P.filter(x => x.stock === 0).length;

    const adminNav = [
      ['Tableau de bord', 'dash'], ['Commandes', 'orders'], ['Produits', 'products'], ['Inventaire', 'inventory'],
      ['Clients', 'customers'], ['Avis', 'reviews'], ['Promotions', 'promos'], ['Contenu', 'content'], ['Le Lab', 'lab'], ['Analytique', 'analytics'], ['Réglages', 'settings']
    ].map(n => {
      const active = s.adminPage === n[1] || (n[1] === 'orders' && s.adminPage === 'order');
      const badge = n[1] === 'orders' ? String(s.orders.filter(o => o.status === 'Nouvelle').length)
        : n[1] === 'inventory' ? String(lowCount + outCount)
        : n[1] === 'reviews' ? String(s.reviews.filter(r => r.status === 'En attente').length) : '';
      return {
        name: n[0], bg: active ? 'rgba(255,255,255,0.12)' : 'transparent', op: active ? '1' : '0.68',
        hasBadge: !!badge && badge !== '0', badge: badge,
        badgeBg: n[1] === 'inventory' ? '#A83A30' : '#FFFFFF', badgeColor: n[1] === 'inventory' ? '#FFFFFF' : '#14120F',
        go: () => n[1] ? this.setState({ admin: true, adminPage: n[1] }) : this.flash('Module ' + n[0] + ' : à cadrer dans la phase suivante')
      };
    });

    const titles = { dash: ['Tableau de bord', 'Vue Maroc · 30 derniers jours'], orders: ['Commandes', s.orders.length + ' commandes'], order: ['Commande ' + (cur ? cur.no : ''), cur ? cur.city : ''], products: ['Produits', P.length + ' références'], inventory: ['Inventaire', lowCount + ' alertes'],
      customers: ['Clients', 'Base clients Maroc'], reviews: ['Avis', s.reviews.filter(r => r.status === 'En attente').length + ' en attente de modération'],
      promos: ['Promotions', s.promos.filter(p => p.active).length + ' actives'], content: ['Contenu', 'Page d\'accueil et bandeaux'],
      lab: ['Le Lab', '6 articles'], analytics: ['Analytique', 'Performance commerciale'], settings: ['Réglages', 'Configuration de la boutique'] };
    const t = titles[s.adminPage] || titles.dash;

    const hi = s.heroSlide % 3;
    const img = (id) => { const x = this.find(id); return x ? x.images[0] : ''; };
    const heroSlides = [
      { key: 'h0', pkey: 'p0', kicker: 'Laboratoire britannique · Maroc',
        l1: 'LA COSMÉTIQUE', l2: 'FORMULÉE COMME', l3: 'UNE ORDONNANCE.',
        body: 'Sérums et crèmes à concentrations actives élevées, désormais disponibles au Maroc avec paiement à la livraison.',
        ctaLabel: 'Acheter les soins', cta: () => this.shop({}, 'Tous les soins'),
        img: img('259'), alt: 'Édition limitée Caviar' },
      { key: 'h1', pkey: 'p1', kicker: 'Édition limitée · Caviar',
        l1: 'LE PROTOCOLE', l2: 'JOUR ET NUIT', l3: 'LE PLUS CONCENTRÉ.',
        body: 'Extrait de caviar, fleur de Tiaré et huiles végétales. Deux gestes, une peau visiblement raffermie.',
        ctaLabel: 'Voir la gamme Caviar', cta: () => this.shop({ filterIng: 'Caviar' }, 'Caviar'),
        img: img('155'), alt: 'Crème Nuit Caviar' },
      { key: 'h2', pkey: 'p2', kicker: 'Écran solaire · SPF 50',
        l1: 'LE SOLEIL MAROCAIN', l2: 'NE PREND PAS', l3: 'DE VACANCES.',
        body: 'Écran SPF 50 à 1% d\'acide hyaluronique : protection quotidienne et hydratation en un seul geste, toute l\'année.',
        ctaLabel: 'Voir l\'écran SPF 50', cta: () => this.openPdp('215'),
        img: img('215'), alt: 'Écran solaire SPF 50' }
    ];
    const ING = {
      'Caviar': { intro: 'Un extrait riche en acides aminés et oligo-éléments, utilisé pour soutenir la fermeté et l\'éclat des peaux matures.', what: 'L\'extrait de caviar concentre protéines, minéraux et acides gras qui participent au maintien de la densité cutanée. C\'est l\'actif signature des formules les plus concentrées du laboratoire.', benefits: ['Aide à raffermir et lisser le grain de peau', 'Soutient l\'éclat des teints fatigués', 'Apporte une nutrition riche sans effet gras'], how: 'Matin et soir sur peau nettoyée, en massage ascendant du visage et du cou.', who: 'Peaux matures, premières rides installées, perte de fermeté.' },
      'Green Caviar': { intro: 'Une algue marine gorgée d\'eau, connue pour sa capacité à retenir l\'hydratation à la surface de la peau.', what: 'Le Green Caviar est une algue riche en polysaccharides qui forme un film hydratant léger. Elle complète l\'action des soins de nuit sans alourdir la peau.', benefits: ['Retient l\'hydratation pendant la nuit', 'Apaise les sensations d\'inconfort', 'Laisse un fini souple, non collant'], how: 'Le soir, en dernière étape de la routine, sur l\'ensemble du visage.', who: 'Peaux déshydratées, peaux normales à mixtes.' },
      'Rétinol': { intro: 'Le dérivé de vitamine A le plus étudié pour le renouvellement cellulaire et le lissage des rides.', what: 'Le rétinol accélère le renouvellement des cellules de surface et stimule les mécanismes de fermeté. Son introduction doit être progressive pour limiter les réactions.', benefits: ['Lisse les rides et ridules avec le temps', 'Affine le grain de peau', 'Aide à uniformiser le teint'], how: 'Le soir uniquement, deux à trois fois par semaine au départ, puis progressivement chaque soir. Écran solaire obligatoire le matin.', who: 'Peaux non sensibilisées, à partir de 25 ans. À éviter pendant la grossesse.' },
      'Acide Hyaluronique': { intro: 'La molécule de référence pour attirer et fixer l\'eau dans les couches supérieures de la peau.', what: 'L\'acide hyaluronique peut retenir de nombreuses fois son poids en eau. Les formules combinent plusieurs poids moléculaires pour hydrater en surface et en profondeur relative.', benefits: ['Repulpe visiblement dès les premières applications', 'Atténue les ridules de déshydratation', 'Compatible avec tous les autres actifs'], how: 'Matin et soir sur peau légèrement humide, avant la crème.', who: 'Tous les types de peau, y compris sensibles.' },
      'Vitamine C': { intro: 'Un antioxydant qui cible la perte d\'éclat et l\'irrégularité du teint.', what: 'La vitamine C limite l\'action des radicaux libres et intervient dans les mécanismes de pigmentation. Elle donne des résultats visibles sur la luminosité du teint.', benefits: ['Ravive l\'éclat des teints ternes', 'Aide à estomper les taches pigmentaires', 'Protège contre le stress oxydatif quotidien'], how: 'Le matin, avant la crème et l\'écran solaire.', who: 'Teints ternes, taches, exposition urbaine et solaire.' },
      'Collagène': { intro: 'Un soutien ciblé pour les peaux qui perdent en densité et en rebond.', what: 'Les formules au collagène associent peptides et agents hydratants pour améliorer l\'aspect de fermeté de la peau et son confort immédiat.', benefits: ['Améliore l\'aspect de fermeté', 'Renforce le confort des peaux sèches', 'Lisse les rides d\'expression'], how: 'Le soir, en couche généreuse sur le visage et le cou.', who: 'Peaux matures ou relâchées.' },
      'SYN-AKE': { intro: 'Un tripeptide inspiré du venin de serpent, utilisé pour cibler les rides d\'expression.', what: 'SYN-AKE agit sur la contraction des muscles superficiels responsables des rides d\'expression, avec une action de lissage progressive.', benefits: ['Cible les rides du front et du contour des yeux', 'Lisse les traits sans effet figé', 'S\'associe bien à l\'acide hyaluronique'], how: 'Matin et soir sur les zones concernées, en tapotements légers.', who: 'Rides d\'expression marquées, front et pattes d\'oie.' },
      'Acide Tranexamique': { intro: 'L\'actif de référence sur les taches persistantes et les marques post-inflammatoires.', what: 'L\'acide tranexamique intervient sur les mécanismes de pigmentation déclenchés par l\'inflammation et le soleil. Il est souvent associé à la niacinamide.', benefits: ['Atténue les taches et le mélasma', 'Réduit les marques laissées par les imperfections', 'Uniformise progressivement le teint'], how: 'Matin et soir, suivi d\'un écran solaire SPF 50 en journée.', who: 'Taches pigmentaires, mélasma, marques post-acné.' },
      'Niacinamide': { intro: 'Une vitamine B3 polyvalente : sébum, pores, rougeurs et barrière cutanée.', what: 'La niacinamide régule la production de sébum, renforce la barrière cutanée et limite les rougeurs. Elle est bien tolérée à des concentrations élevées.', benefits: ['Régule les brillances et resserre l\'aspect des pores', 'Apaise les rougeurs', 'Renforce la barrière cutanée'], how: 'Matin et/ou soir, avant la crème hydratante.', who: 'Peaux mixtes à grasses, peaux sujettes aux imperfections.' }
    };
    const ARTICLES = [
      { cat: 'Actifs', title: 'Rétinol : par où commencer sans irriter la peau', read: '6 min', author: 'Dr. L. Haddad', date: '26 août 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Fréquence, concentration, association avec les autres actifs : le protocole d\'introduction en quatre semaines.',
        lead: 'Le rétinol donne des résultats mesurables sur les rides et le grain de peau, à condition d\'être introduit lentement. La majorité des abandons viennent d\'une montée en fréquence trop rapide.',
        body: [
          { h: 'Semaines 1 et 2 : deux soirs par semaine', p: 'Appliquez une noisette sur peau sèche et propre, en évitant le contour immédiat des yeux et les ailes du nez. Une légère sécheresse est normale ; une rougeur qui persiste plus de 48 heures indique qu\'il faut espacer.' },
          { h: 'Semaines 3 et 4 : un soir sur deux', p: 'Si la peau tolère bien, passez à un soir sur deux et ajoutez une crème hydratante par-dessus. Cette technique amortit l\'effet sans réduire l\'efficacité de l\'actif.' },
          { h: 'Ce qu\'il ne faut pas associer le même soir', p: 'Évitez les exfoliants acides et la vitamine C pure lors des soirs de rétinol. Réservez-leur les soirs alternés pour limiter l\'inconfort.' },
          { h: 'L\'écran solaire n\'est pas optionnel', p: 'Le renouvellement cellulaire accéléré rend la peau plus réactive au soleil. Un SPF 50 chaque matin conditionne le résultat obtenu le soir.' }
        ], ids: ['227', '215'] },
      { cat: 'Protocoles', title: 'Construire une routine anti-âge en quatre gestes', read: '5 min', author: 'Équipe skinChemists Maroc', date: '19 août 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Nettoyer, traiter, hydrater, protéger : l\'ordre d\'application qui change les résultats.',
        lead: 'Une routine efficace tient en quatre étapes. Ce qui compte n\'est pas le nombre de produits mais leur ordre et leur régularité.',
        body: [
          { h: '1. Nettoyer sans décaper', p: 'Un nettoyant doux matin et soir suffit. Une peau qui tire après le nettoyage est une peau dont la barrière a été fragilisée.' },
          { h: '2. Traiter avec un sérum ciblé', p: 'Un seul actif principal par moment de la journée : vitamine C le matin, rétinol ou acide tranexamique le soir selon l\'objectif.' },
          { h: '3. Hydrater pour sceller', p: 'La crème verrouille les actifs et limite la perte en eau. Choisissez la texture selon la saison plutôt que selon le type de peau.' },
          { h: '4. Protéger, toute l\'année', p: 'Au Maroc, l\'indice UV reste élevé même en hiver. Un SPF 50 quotidien est le geste anti-âge le plus rentable.' }
        ], ids: ['225', '257', '215'] },
      { cat: 'Climat', title: 'Protéger sa peau du soleil marocain toute l\'année', read: '4 min', author: 'Dr. L. Haddad', date: '12 août 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Indice UV, réapplication, textures compatibles avec le maquillage : ce qui fonctionne au quotidien.',
        lead: 'Entre Casablanca et Marrakech, l\'indice UV dépasse fréquemment 8 de mai à septembre, et reste notable en hiver. La protection quotidienne n\'est pas saisonnière.',
        body: [
          { h: 'Quelle quantité, vraiment', p: 'Deux doigts de produit pour le visage et le cou. En dessous, la protection annoncée sur le flacon n\'est pas atteinte.' },
          { h: 'Réappliquer sans défaire le maquillage', p: 'Toutes les deux heures en cas d\'exposition directe. Les textures fluides se superposent mieux que les crèmes riches.' },
          { h: 'Hydratation et protection dans le même geste', p: 'Un écran SPF 50 enrichi en acide hyaluronique remplace la crème de jour et réduit le nombre d\'étapes le matin.' }
        ], ids: ['215', '257'] },
      { cat: 'Taches', title: 'Mélasma et taches : ce qui fonctionne réellement', read: '7 min', author: 'Dr. L. Haddad', date: '05 août 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Acide tranexamique, niacinamide, patience : les leviers utiles et ceux qui ne servent à rien.',
        lead: 'Les taches pigmentaires demandent des mois, pas des semaines. Trois actifs concentrent l\'essentiel des résultats documentés.',
        body: [
          { h: 'Traiter la cause, pas seulement la couleur', p: 'Inflammation et soleil déclenchent la pigmentation. Sans protection solaire stricte, aucun actif éclaircissant ne tient ses promesses.' },
          { h: 'Acide tranexamique et niacinamide', p: 'Cette association agit sur deux mécanismes complémentaires et se tolère bien, y compris sur les peaux réactives.' },
          { h: 'Compter en mois', p: 'Les premiers résultats visibles apparaissent après six à huit semaines, l\'amélioration nette après trois à quatre mois d\'usage régulier.' }
        ], ids: ['222', '218'] },
      { cat: 'Hydratation', title: 'Acide hyaluronique : pourquoi il assèche parfois la peau', read: '4 min', author: 'Équipe skinChemists Maroc', date: '29 juillet 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Un geste mal appliqué transforme un actif hydratant en facteur de déshydratation.',
        lead: 'L\'acide hyaluronique attire l\'eau. Sur peau sèche et dans un air sec, il peut la puiser dans la peau elle-même.',
        body: [
          { h: 'Appliquer sur peau humide', p: 'Vaporisez ou tamponnez le visage avant le sérum. L\'actif dispose alors d\'une réserve d\'eau à fixer.' },
          { h: 'Toujours sceller avec une crème', p: 'Sans couche occlusive par-dessus, l\'eau captée s\'évapore. C\'est l\'étape la plus souvent oubliée.' },
          { h: 'Adapter à la saison', p: 'En été comme en climat sec, privilégiez les formules combinant hyaluronique et agents nourrissants.' }
        ], ids: ['257', '242'] },
      { cat: 'Rituels', title: 'Jour et nuit : deux crèmes, deux fonctions', read: '5 min', author: 'Équipe skinChemists Maroc', date: '22 juillet 2026', slot: '[ visuel éditorial ]',
        excerpt: 'Pourquoi la même formule ne peut pas couvrir les besoins du matin et ceux de la nuit.',
        lead: 'Le jour, la peau se défend. La nuit, elle se répare. Les deux moments n\'appellent pas les mêmes textures ni les mêmes actifs.',
        body: [
          { h: 'Le matin : protéger et tenir', p: 'Textures légères, antioxydants et protection solaire. La crème doit rester compatible avec le maquillage.' },
          { h: 'Le soir : nourrir et renouveler', p: 'Textures plus riches, actifs de renouvellement. C\'est le moment où le rétinol et les formules concentrées travaillent.' },
          { h: 'Le duo, en pratique', p: 'Les protocoles jour et nuit d\'une même gamme sont formulés pour se compléter, ce qui simplifie le choix.' }
        ], ids: ['152', '155'] }
    ];
    const reviewTotal = P.reduce((n, x) => n + x.reviews, 0);
    const reviewAvg = (P.reduce((n, x) => n + x.rating * x.reviews, 0) / Math.max(1, reviewTotal)).toFixed(1);
    const quoteBank = [
      { quote: 'Ma peau est visiblement plus lisse après trois semaines, sans aucune irritation.', who: 'Salma B., Casablanca', date: '24 août 2026', id: '152' },
      { quote: 'Commandé un soir, livré le lendemain à Rabat, payé à la réception. Rien à redire.', who: 'Youssef I., Rabat', date: '21 août 2026', id: '227' },
      { quote: 'Les taches sur mes joues se sont nettement atténuées en deux mois d\'utilisation.', who: 'Imane C., Marrakech', date: '18 août 2026', id: '222' },
      { quote: 'La texture est légère et pénètre vite, parfait sous le maquillage le matin.', who: 'Nadia K., Tanger', date: '14 août 2026', id: '215' },
      { quote: 'Le duo jour et nuit tient ses promesses. Je rachète pour la troisième fois.', who: 'Hamza E., Agadir', date: '09 août 2026', id: '155' },
      { quote: 'Produit identique à celui acheté à Londres, et le conseil par WhatsApp est utile.', who: 'Sofia N., Fès', date: '03 août 2026', id: '259' }
    ].map(q => {
      const prod = this.find(q.id) || P[0];
      return { quote: q.quote, who: q.who, date: q.date, product: prod.name, img: prod.images[0], open: () => this.openPdp(prod.id) };
    });
    const quotePage = [0, 1, 2, 3].map(i => quoteBank[(s.quotePg * 4 + i) % quoteBank.length]);
    const best = ['259', '227', '211', '257', '245', '222', '152'].map(id => this.find(id)).filter(Boolean).map(x => this.card(x));
    const kitDefs = [
      { name: 'Rituel Caviar Jour & Nuit', tag: 'Édition limitée', ids: ['152', '155'],
        blurb: 'Le protocole complet de la gamme Caviar : la crème de jour raffermit et protège, celle de nuit nourrit et répare pendant le sommeil.' },
      { name: 'Protocole Éclat Vitamine C', tag: 'Teint terne · Taches', ids: ['225', '253', '255'],
        blurb: 'Trois étapes pour raviver un teint fatigué : nettoyage à la vitamine C, sérum concentré, crème de nuit antioxydante.' },
      { name: 'Duo Hydratation Hyaluronique', tag: 'Toutes peaux', ids: ['257', '242'],
        blurb: 'Sérum et crème de nuit à l\'acide hyaluronique, pour repulper les peaux déshydratées et lisser les ridules de surface.' }
    ];
    const bundleDefs = [
      { name: 'Rituel Caviar Jour & Nuit', ids: ['152', '155'] },
      { name: 'Protocole Éclat Vitamine C', ids: ['225', '253', '255'] },
      { name: 'Duo Hydratation Hyaluronique', ids: ['257', '242'] }
    ];

    return {
      isStore: isStore, isAdmin: isAdmin,
      isHome: isStore && s.view === 'home', isShop: isStore && s.view === 'shop',
      isPdp: isStore && s.view === 'pdp', isCheckout: isStore && s.view === 'checkout',
      isConfirm: isStore && s.view === 'confirm',
      isKits: isStore && s.view === 'kits', isLab: isStore && s.view === 'lab',
      isArticle: isStore && s.view === 'article', isIngredient: isStore && s.view === 'ingredient',
      isAccount: isStore && s.view === 'account',
      adminDash: isAdmin && s.adminPage === 'dash', adminOrders: isAdmin && s.adminPage === 'orders',
      adminOrder: isAdmin && s.adminPage === 'order', adminProducts: isAdmin && s.adminPage === 'products',
      adminInventory: isAdmin && s.adminPage === 'inventory',
      adminCustomers: isAdmin && s.adminPage === 'customers', adminReviews: isAdmin && s.adminPage === 'reviews',
      adminPromos: isAdmin && s.adminPage === 'promos', adminContent: isAdmin && s.adminPage === 'content',
      adminLab: isAdmin && s.adminPage === 'lab', adminAnalytics: isAdmin && s.adminPage === 'analytics',
      adminSettings: isAdmin && s.adminPage === 'settings',

      goHome: () => this.nav('home'), goShopAll: () => this.shop({}, 'Tous les soins'),
      goBest: () => this.shop({ sort: 'rating' }, 'Best-sellers'),
      goKits: () => this.nav('kits'), goLab: () => this.nav('lab'),
      goAccount: () => this.nav('account'),
      goCaviar: () => this.shop({ filterIng: 'Caviar' }, 'Caviar'),
      goDiagnostic: () => this.shop({ filterConcern: 'Rides & Fermeté' }, 'Rides & Fermeté'),
      goWish: () => this.nav('account'),
      goCheckout: () => this.nav('checkout', { drawer: false }),
      goAdminDash: () => this.setState({ admin: true, adminPage: 'dash', mega: false, searchOpen: false, drawer: false }),
      goAdminOrders: () => this.setState({ admin: true, adminPage: 'orders' }),
      goAdminInventory: () => this.setState({ admin: true, adminPage: 'inventory' }),
      goAdminOrder: () => this.setState({ admin: true, adminPage: 'order', orderNo: s.lastOrder ? s.lastOrder.no : null }),

      megaOpen: s.mega, megaActifs: () => this.setState({ mega: true }), megaSoins: () => this.setState({ mega: true }),
      megaClose: () => this.setState({ mega: false }),
      searchOpen: s.searchOpen, toggleSearch: () => this.setState({ searchOpen: !s.searchOpen, query: '' }),
      query: s.query, onQuery: (e) => this.setState({ query: e.target.value }),
      searchResults: !s.query ? [] : P.filter(x => (x.name + ' ' + x.ingredient).toLowerCase().indexOf(s.query.toLowerCase()) > -1).slice(0, 6).map(x => ({
        name: x.name, img: x.images[0], priceStr: this.mad(this.eff(x)), open: () => this.openPdp(x.id)
      })),

      cartCount: s.cart.reduce((n, c) => n + c.qty, 0), wishCount: s.wish.length,
      openDrawer: () => this.setState({ drawer: true }), closeDrawer: () => this.setState({ drawer: false }),
      drawerOpen: s.drawer, cartEmpty: s.cart.length === 0,
      cartItems: s.cart.map(c => {
        const x = this.find(c.id);
        return { name: x.name, img: x.images[0], qty: c.qty, lineStr: this.mad(this.eff(x) * c.qty),
          plus: () => this.setQty(c.id, 1), minus: () => this.setQty(c.id, -1), remove: () => this.remove(c.id) };
      }),
      subtotalStr: this.mad(sub),
      freeShipText: sub >= this.freeShip() ? 'Livraison offerte débloquée.' : 'Encore ' + this.mad(this.freeShip() - sub) + ' pour la livraison offerte.',
      freeShipPct: Math.min(100, Math.round(sub / this.freeShip() * 100)) + '%',

      hero: Object.assign({}, heroSlides[hi], {
        line: [heroSlides[hi].l1, heroSlides[hi].l2, heroSlides[hi].l3][s.heroLine] || heroSlides[hi].l1,
        lineAnim: s.heroTick % 2 ? 'scmLineB' : 'scmLineA',
        slideAnim: hi % 2 ? 'scmRiseB' : 'scmRiseA',
        imgAnim: hi % 2 ? 'scmHeroB' : 'scmHeroA'
      }),
      heroDots: heroSlides.map((x, i) => ({
        label: 'Slide ' + (i + 1), w: i === hi ? '34px' : '16px',
        bg: i === hi ? '#14120F' : '#D5D5D5', go: () => this.setState({ heroSlide: i, heroLine: 0, heroTick: s.heroTick + 1 })
      })),
      heroPrev: () => this.setState({ heroSlide: (hi + 2) % 3, heroLine: 0, heroTick: s.heroTick + 1 }),
      heroNext: () => this.setState({ heroSlide: (hi + 1) % 3, heroLine: 0, heroTick: s.heroTick + 1 }),
      campaignImg: (this.find('155') || P[0]).images[0],
      bestSellers: best,
      activeTiles: ['Caviar', 'Rétinol', 'Acide Hyaluronique', 'Vitamine C', 'Collagène', 'SYN-AKE', 'Acide Tranexamique', 'Green Caviar'].map(v => ({
        name: v, count: countBy('ingredient', v), go: () => this.nav('ingredient', { ingName: v })
      })),
      bundles: bundleDefs.map(b => {
        const items = b.ids.map(id => this.find(id)).filter(Boolean);
        const totalRaw = items.reduce((n, x) => n + this.eff(x), 0);
        const price = Math.round(totalRaw * 0.85 / 5) * 5;
        return { name: b.name, imgs: items.map(x => ({ src: x.images[0] })), items: items.map(x => ({ name: x.name })),
          priceStr: this.mad(price), totalStr: this.mad(totalRaw), saveStr: this.mad(totalRaw - price),
          add: () => { b.ids.forEach(id => this.add(id, 1)); this.flash('Coffret ajouté au panier'); } };
      }),
      testimonials: quotePage,
      reviewAvg: reviewAvg, reviewTotal: reviewTotal,
      reviewDist: [[5, 82], [4, 12], [3, 4], [2, 1], [1, 1]].map(d => ({ star: d[0], pct: d[1] + '%' })),
      quotePrev: () => this.setState({ quotePg: (s.quotePg + 1) % 2 }),
      quoteNext: () => this.setState({ quotePg: (s.quotePg + 1) % 2 }),
      articles: ARTICLES.slice(0, 3).map((a, i) => ({ slot: a.slot, cat: a.cat, title: a.title, open: () => this.nav('article', { artIdx: i }) })),
      benefits: [
        { title: 'Livraison Maroc', text: '24–48h Casablanca et Rabat' },
        { title: 'Paiement livraison', text: 'Réglez à la réception du colis' },
        { title: 'Authenticité', text: 'Distributeur agréé, lots traçables' },
        { title: 'Retours 14 jours', text: 'Produit non ouvert, échange simple' },
        { title: 'Conseil', text: 'Diagnostic par WhatsApp' }
      ],

      shopTitle: s.shopTitle || s.filterIng || s.filterConcern || s.filterCat || 'Tous les soins',
      shopSub: 'Formules à concentrations actives élevées, prix en dirhams, expédiées depuis Casablanca.',
      shopCount: grid.length, shopGrid: grid.map(x => this.card(x)),
      sort: s.sort, onSort: (e) => this.setState({ sort: e.target.value }),
      catFilters: filterList('cat', s.filterCat, (v) => this.setState({ filterCat: v, shopTitle: v })),
      ingFilters: filterList('ingredient', s.filterIng, (v) => this.setState({ filterIng: v, shopTitle: v })),
      concernFilters: filterList('concern', s.filterConcern, (v) => this.setState({ filterConcern: v, shopTitle: v })),
      clearFilters: () => this.setState({ filterCat: null, filterIng: null, filterConcern: null, shopTitle: 'Tous les soins' }),
      ingredientLinks: ['Caviar', 'Green Caviar', 'Rétinol', 'Acide Hyaluronique', 'Vitamine C', 'Collagène', 'SYN-AKE', 'Acide Tranexamique'].map(v => ({ name: v, go: () => this.nav('ingredient', { ingName: v }) })),
      concernLinks: ['Rides & Fermeté', 'Taches & Éclat', 'Hydratation', 'Imperfections', 'Protection'].map(v => ({ name: v, go: () => this.shop({ filterConcern: v }, v) })),

      pdp: {
        name: p.name, brand: p.brand, cat: p.cat, short: p.short,
        mainImg: p.images[Math.min(s.gal, p.images.length - 1)],
        gallery: p.images.map((src, i) => ({ src: src, border: i === Math.min(s.gal, p.images.length - 1) ? '#14120F' : '#E6E6E6', pick: () => this.setState({ gal: i }) })),
        priceStr: this.mad(this.eff(p)), oldStr: this.mad(p.price), hasSale: hasSale,
        discount: hasSale ? '−' + Math.round((1 - p.sale / p.price) * 100) + '%' : '',
        rating: p.rating.toFixed(1), reviews: p.reviews,
        bullets: p.bullets.map(b => ({ text: b })), actifs: p.actifs, dist: dist,
        stockText: p.stock === 0 ? 'Rupture de stock — soyez prévenu du réapprovisionnement'
          : p.stock <= p.low ? 'Plus que ' + p.stock + ' en stock' + (inCartQty ? ' · ' + inCartQty + ' dans votre panier' : '')
          : 'En stock · expédié sous 24h depuis Casablanca',
        stockColor: p.stock === 0 ? '#A83A30' : p.stock <= p.low ? '#8A6A22' : '#3F6B45',
        cta: p.stock === 0 ? 'Me prévenir' : 'Ajouter au panier',
        wishFill: s.wish.indexOf(p.id) > -1 ? '#14120F' : 'none',
        specs: [
          { k: 'Marque', v: p.brand }, { k: 'Référence', v: p.sku }, { k: 'Code-barres', v: p.gtin },
          { k: 'Actif principal', v: p.ingredient }, { k: 'Préoccupation', v: p.concern },
          { k: 'Catégorie', v: p.cat }, { k: 'Origine', v: 'Royaume-Uni' }
        ]
      },
      qty: s.qty, qtyPlus: () => this.setState({ qty: s.qty + 1 }), qtyMinus: () => this.setState({ qty: Math.max(1, s.qty - 1) }),
      addPdp: () => p.stock === 0 ? this.flash('Vous serez prévenu du retour en stock') : this.add(p.id, s.qty),
      toggleWish: () => this.setState({ wish: s.wish.indexOf(p.id) > -1 ? s.wish.filter(w => w !== p.id) : s.wish.concat([p.id]) }),
      pdpTabs: [['benefits', 'Bienfaits'], ['actifs', 'Actifs'], ['use', 'Application'], ['avis', 'Avis']].map(x => ({
        name: x[1], border: s.tab === x[0] ? '#14120F' : 'transparent', color: s.tab === x[0] ? '#14120F' : '#9B9B9B',
        go: () => this.setState({ tab: x[0] })
      })),
      tabIsBenefits: s.tab === 'benefits', tabIsActifs: s.tab === 'actifs', tabIsUse: s.tab === 'use', tabIsAvis: s.tab === 'avis',
      tabAvis: () => this.setState({ tab: 'avis' }),
      pdpReviews: [
        { who: 'Salma B.', date: '24/08/2026', text: 'Texture légère qui pénètre vite, aucun film gras. Je l\'utilise matin et soir depuis un mois.' },
        { who: 'Nadia K.', date: '18/08/2026', text: 'Reçu en 48h à Tanger, payé à la livraison. Le produit correspond exactement au site officiel.' },
        { who: 'Imane C.', date: '09/08/2026', text: 'Ridules au front visiblement atténuées. Je reprendrai le format duo jour et nuit.' }
      ],
      related: P.filter(x => x.ingredient === p.ingredient && x.id !== p.id).concat(P.filter(x => x.ingredient !== p.ingredient)).slice(0, 4).map(x => this.card(x)),

      form: s.form,
      setFirst: (e) => this.setState({ form: Object.assign({}, s.form, { first: e.target.value }) }),
      setLast: (e) => this.setState({ form: Object.assign({}, s.form, { last: e.target.value }) }),
      setPhone: (e) => this.setState({ form: Object.assign({}, s.form, { phone: e.target.value }) }),
      setEmail: (e) => this.setState({ form: Object.assign({}, s.form, { email: e.target.value }) }),
      setAddr: (e) => this.setState({ form: Object.assign({}, s.form, { addr: e.target.value }) }),
      setCity: (e) => this.setState({ form: Object.assign({}, s.form, { city: e.target.value }) }),
      setZip: (e) => this.setState({ form: Object.assign({}, s.form, { zip: e.target.value }) }),
      cityOptions: CITIES.map(c => ({ name: c })),
      shipOptions: [
        { key: 'standard', name: 'Livraison standard', eta: '2–4 jours ouvrés', priceStr: sub - disc >= this.freeShip() ? 'Offerte' : this.mad(parseInt(s.set.ship, 10) || 35) },
        { key: 'express', name: 'Livraison express', eta: '24h Casablanca, Rabat, Marrakech', priceStr: this.mad(60) }
      ].map(o => ({ name: o.name, eta: o.eta, priceStr: o.priceStr, border: s.ship === o.key ? '#14120F' : '#E6E6E6', pick: () => this.setState({ ship: o.key }) })),
      payOptions: [
        { key: 'cod', name: 'Paiement à la livraison', note: 'Réglez en espèces à la réception du colis', tag: 'Recommandé' },
        { key: 'card', name: 'Carte bancaire', note: 'Paiement sécurisé via passerelle marocaine', tag: 'CMI' },
        { key: 'transfer', name: 'Virement bancaire', note: 'Expédition après réception du virement', tag: '' }
      ].map(o => ({ name: o.name, note: o.note, tag: o.tag, border: s.pay === o.key ? '#14120F' : '#E6E6E6', pick: () => this.setState({ pay: o.key }) })),
      coupon: s.coupon, onCoupon: (e) => this.setState({ coupon: e.target.value }),
      applyCoupon: () => {
        if (s.coupon.trim().toUpperCase() === 'MAROC10') { this.setState({ couponCode: 'MAROC10' }); this.flash('Code MAROC10 appliqué : −10%'); }
        else { this.setState({ couponCode: null }); this.flash('Code promo invalide'); }
      },
      hasDiscount: disc > 0, discountStr: this.mad(disc), couponCode: s.couponCode || '',
      shipStr: this.shipCost() === 0 ? 'Offerte' : this.mad(this.shipCost()),
      totalStr: this.mad(this.total()),
      formError: !!s.formError, formErrorText: s.formError,
      onSubmitOrder: (e) => this.submitOrder(e),

      lastOrder: s.lastOrder ? {
        no: s.lastOrder.no, first: s.lastOrder.first,
        addrLine: s.lastOrder.addr + ', ' + s.lastOrder.city,
        payLabel: s.lastOrder.pay, shipLabel: s.lastOrder.ship === 'express' ? 'Express 24h' : 'Standard 2–4 jours',
        eta: s.lastOrder.ship === 'express' ? '31 août 2026' : '1–3 septembre 2026',
        lines: [{ label: 'Sous-total', value: this.mad(s.lastOrder.sub) }].concat(
          s.lastOrder.discount ? [{ label: 'Remise ' + s.lastOrder.coupon, value: '−' + this.mad(s.lastOrder.discount) }] : [],
          [{ label: 'Livraison', value: s.lastOrder.shipCost === 0 ? 'Offerte' : this.mad(s.lastOrder.shipCost) }]),
        totalStr: this.mad(s.lastOrder.total)
      } : { no: '', first: '', addrLine: '', payLabel: '', shipLabel: '', eta: '', lines: [], totalStr: '' },

      newsletter: s.newsletter, onNewsletter: (e) => this.setState({ newsletter: e.target.value }),
      subscribe: () => this.flash(s.newsletter ? 'Merci, vous êtes inscrit à la lettre du Lab' : 'Renseignez votre email'),

      adminNav: adminNav, adminTitle: t[0], adminSub: t[1],
      kpis: [
        { label: 'Chiffre d\'affaires', value: this.mad(revenue), delta: '+18% vs 30 j. préc.', deltaColor: '#2F5A38' },
        { label: 'Commandes', value: String(nOrders), delta: '+6 cette semaine', deltaColor: '#2F5A38' },
        { label: 'Panier moyen', value: this.mad(nOrders ? revenue / nOrders : 0), delta: '+4%', deltaColor: '#2F5A38' },
        { label: 'Taux COD', value: Math.round(s.orders.filter(o => o.pay === 'Paiement à la livraison').length / Math.max(1, s.orders.length) * 100) + '%', delta: 'Encaissement à la livraison', deltaColor: '#8A8A8A' }
      ],
      revenue14: this.mad(revenue),
      revBars: revSeries.map((v, i) => ({ h: Math.round(v / maxRev * 100) + '%', day: days[i] })),
      cityStats: Object.keys(cityTotals).sort((a, b) => cityTotals[b] - cityTotals[a]).map(c => ({
        name: c, value: this.mad(cityTotals[c]), pct: Math.round(cityTotals[c] / maxCity * 100) + '%'
      })),
      recentOrders: s.orders.slice(0, 5).map(orderRow),
      stockAlerts: P.filter(x => x.stock <= x.low).sort((a, b) => a.stock - b.stock).slice(0, 6).map(x => ({
        name: x.name, label: x.stock === 0 ? 'Rupture' : x.stock + ' restants', color: x.stock === 0 ? '#A83A30' : '#8A6A22'
      })),

      orderFilters: ['Toutes'].concat(STATUSES).map(f => ({
        name: f, count: f === 'Toutes' ? s.orders.length : s.orders.filter(o => o.status === f).length,
        bg: s.orderFilter === f ? '#14120F' : '#FFFFFF', color: s.orderFilter === f ? '#FFFFFF' : '#14120F',
        border: s.orderFilter === f ? '#14120F' : '#E6E6E6', go: () => this.setState({ orderFilter: f })
      })),
      orderRows: filtered.map(orderRow),
      order: cur ? {
        no: cur.no, date: cur.date, name: cur.name, phone: cur.phone, email: cur.email,
        addr: cur.addr, city: cur.city, zip: cur.zip,
        shipLabel: cur.ship === 'express' ? 'Express 24h' : 'Standard 2–4 jours',
        pay: cur.pay, payStatus: cur.payStatus, status: cur.status,
        statusBg: styleFor(cur.status)[0], statusColor: styleFor(cur.status)[1],
        items: cur.items.map(i => ({ name: i.name, sku: i.sku, qty: i.qty, img: i.img, lineStr: this.mad(i.unit * i.qty) })),
        totals: [{ label: 'Sous-total', value: this.mad(cur.sub) }].concat(
          cur.discount ? [{ label: 'Remise ' + cur.coupon, value: '−' + this.mad(cur.discount) }] : [],
          [{ label: 'Livraison', value: cur.shipCost === 0 ? 'Offerte' : this.mad(cur.shipCost) },
           { label: 'TVA incluse (20%)', value: this.mad(cur.total / 6) }]),
        totalStr: this.mad(cur.total),
        timeline: cur.timeline.slice().reverse(),
        canAdvance: STATUSES.indexOf(cur.status) > -1 && STATUSES.indexOf(cur.status) < STATUSES.length - 1,
        nextLabel: STATUSES.indexOf(cur.status) > -1 && STATUSES.indexOf(cur.status) < STATUSES.length - 1 ? 'Marquer ' + STATUSES[STATUSES.indexOf(cur.status) + 1].toLowerCase() : '',
        canCancel: cur.status !== 'Annulée' && cur.status !== 'Livrée',
        hasTracking: !!cur.tracking, noTracking: !cur.tracking, tracking: cur.tracking
      } : {},
      advanceOrder: () => this.advance(), cancelOrder: () => this.cancel(),
      note: s.note, onNote: (e) => this.setState({ note: e.target.value }),
      saveNote: () => {
        const orders = s.orders.map(o => o.no === (cur ? cur.no : '') ? Object.assign({}, o, { note: s.note, timeline: o.timeline.concat([{ text: 'Note interne ajoutée', time: this.now(), who: 'Y. Amrani', dot: '#8A8A8A' }]) }) : o);
        this.setState({ orders: orders }); this.flash('Note enregistrée');
      },
      noopToast: () => this.flash('Action disponible après connexion du backend'),

      adminQuery: s.adminQuery, onAdminQuery: (e) => this.setState({ adminQuery: e.target.value }),
      productRows: P.filter(x => !s.adminQuery || (x.name + ' ' + x.sku + ' ' + x.ingredient).toLowerCase().indexOf(s.adminQuery.toLowerCase()) > -1).map(x => ({
        name: x.name, sku: x.sku, cat: x.cat, ingredient: x.ingredient, img: x.images[0],
        priceStr: this.mad(x.price), saleStr: x.sale ? this.mad(x.sale) : '—', stock: x.stock,
        status: x.stock === 0 ? 'Rupture' : x.stock <= x.low ? 'Stock bas' : 'Actif',
        statusBg: x.stock === 0 ? '#F7E9E7' : x.stock <= x.low ? '#F4EFE4' : '#E8EDE8',
        statusColor: x.stock === 0 ? '#A83A30' : x.stock <= x.low ? '#8A6A22' : '#2F5A38',
        edit: () => this.setState({ editId: x.id, edit: { id: x.id, sku: x.sku, name: x.name, price: String(x.price), sale: x.sale === null ? '' : String(x.sale), stock: String(x.stock), low: String(x.low), short: x.short, img: x.images[0] } })
      })),
      editOpen: !!s.edit, edit: s.edit || { sku: '', name: '', price: '', sale: '', stock: '', low: '', short: '', img: '' },
      closeEdit: () => this.setState({ editId: null, edit: null }),
      setEditName: (e) => this.setState({ edit: Object.assign({}, s.edit, { name: e.target.value }) }),
      setEditPrice: (e) => this.setState({ edit: Object.assign({}, s.edit, { price: e.target.value }) }),
      setEditSale: (e) => this.setState({ edit: Object.assign({}, s.edit, { sale: e.target.value }) }),
      setEditStock: (e) => this.setState({ edit: Object.assign({}, s.edit, { stock: e.target.value }) }),
      setEditLow: (e) => this.setState({ edit: Object.assign({}, s.edit, { low: e.target.value }) }),
      setEditShort: (e) => this.setState({ edit: Object.assign({}, s.edit, { short: e.target.value }) }),
      saveEdit: () => this.saveProduct(),

      invKpis: [
        { label: 'Références', value: String(P.length), color: '#14120F' },
        { label: 'Unités en stock', value: String(P.reduce((n, x) => n + x.stock, 0)), color: '#14120F' },
        { label: 'Valeur du stock', value: this.mad(invValue), color: '#14120F' },
        { label: 'Stock bas', value: String(lowCount), color: '#8A6A22' },
        { label: 'Ruptures', value: String(outCount), color: '#A83A30' }
      ],
      invRows: P.slice().sort((a, b) => a.stock - b.stock).map(x => {
        const reserved = s.orders.filter(o => o.status === 'Nouvelle' || o.status === 'Confirmée').reduce((n, o) => n + o.items.filter(i => i.id === x.id).reduce((m, i) => m + i.qty, 0), 0);
        return { name: x.name, sku: x.sku, stock: x.stock, reserved: reserved, available: Math.max(0, x.stock - reserved),
          status: x.stock === 0 ? 'Rupture' : x.stock <= x.low ? 'Stock bas' : 'OK',
          statusBg: x.stock === 0 ? '#F7E9E7' : x.stock <= x.low ? '#F4EFE4' : '#E8EDE8',
          statusColor: x.stock === 0 ? '#A83A30' : x.stock <= x.low ? '#8A6A22' : '#2F5A38',
          minus: () => this.adjust(x.id, -1, 'Ajustement manuel'),
          plus: () => this.adjust(x.id, 1, 'Ajustement manuel'),
          restock: () => this.adjust(x.id, 20, 'Réception fournisseur') };
      }),
      movements: s.movements.slice(0, 40), noMovements: s.movements.length === 0,

      custKpis: [
        { label: 'Clients', value: String(custs.length) },
        { label: 'Nouveaux ce mois', value: String(Math.max(1, Math.round(custs.length * 0.4))) },
        { label: 'Panier moyen', value: this.mad(custs.reduce((n, c) => n + c.spentRaw, 0) / Math.max(1, custs.reduce((n, c) => n + c.orderCount, 0))) },
        { label: 'Taux de réachat', value: Math.round(custs.filter(c => c.orderCount > 1).length / Math.max(1, custs.length) * 100) + '%' }
      ],
      customerRows: custs.map(c => ({
        name: c.name, email: c.email, phone: c.phone, city: c.city,
        orders: c.orderCount, spent: this.mad(c.spentRaw), last: c.last, pay: c.pay,
        tag: c.orderCount > 1 ? 'Fidèle' : 'Nouveau',
        tagBg: c.orderCount > 1 ? '#E8EDE8' : '#EDF1F6', tagColor: c.orderCount > 1 ? '#2F5A38' : 'oklch(0.42 0.09 250)'
      })),

      reviewFilters: ['En attente', 'Approuvé', 'Rejeté', 'Tous'].map(x => ({
        name: x, count: x === 'Tous' ? s.reviews.length : s.reviews.filter(r => r.status === x).length,
        bg: s.reviewFilter === x ? '#14120F' : '#FFFFFF', color: s.reviewFilter === x ? '#FFFFFF' : '#14120F',
        border: s.reviewFilter === x ? '#14120F' : '#E6E6E6', go: () => this.setState({ reviewFilter: x })
      })),
      reviewRows: s.reviews.filter(r => s.reviewFilter === 'Tous' || r.status === s.reviewFilter).map(r => {
        const prod = this.find(r.pid) || P[0];
        const st = { 'En attente': ['#F4EFE4', '#8A6A22'], 'Approuvé': ['#E8EDE8', '#2F5A38'], 'Rejeté': ['#F7E9E7', '#A83A30'] }[r.status];
        return { stars: '★★★★★'.slice(0, r.stars) + '☆☆☆☆☆'.slice(0, 5 - r.stars), who: r.who, date: r.date,
          verified: r.verified, product: prod.name, img: prod.images[0], text: r.text,
          status: r.featured ? r.status + ' · Mis en avant' : r.status, statusBg: st[0], statusColor: st[1],
          featureLabel: r.featured ? 'Retirer de la une' : 'Mettre en avant',
          approve: () => this.setReview(r.id, { status: 'Approuvé' }, 'Avis approuvé'),
          reject: () => this.setReview(r.id, { status: 'Rejeté', featured: false }, 'Avis rejeté'),
          feature: () => this.setReview(r.id, { featured: !r.featured, status: 'Approuvé' }, r.featured ? 'Retiré de la une' : 'Avis mis en avant') };
      }),

      promoActiveCount: s.promos.filter(p => p.active).length,
      promoUses: s.promos.reduce((n, p) => n + p.uses, 0),
      promoRows: s.promos.map(p => ({
        code: p.code, name: p.name, type: p.type, value: p.value, cond: p.cond, period: p.period, uses: p.uses,
        status: p.active ? 'Active' : 'Inactive',
        btnBg: p.active ? '#E8EDE8' : '#FFFFFF', btnColor: p.active ? '#2F5A38' : '#8A8A8A', btnBorder: p.active ? '#CFDFD2' : '#DADADA',
        toggle: () => { this.setState({ promos: s.promos.map(x => x.code === p.code ? Object.assign({}, x, { active: !x.active }) : x) }); this.flash(p.code + (p.active ? ' désactivée' : ' activée')); }
      })),

      cmsRows: s.cms.map((c, i) => ({
        pos: String(i + 1).padStart(2, '0'), name: c.name, note: c.note,
        nameColor: c.on ? '#14120F' : '#B5B5B5',
        visLabel: c.on ? 'Visible' : 'Masquée',
        visBg: c.on ? '#E8EDE8' : '#FFFFFF', visColor: c.on ? '#2F5A38' : '#8A8A8A', visBorder: c.on ? '#CFDFD2' : '#DADADA',
        toggle: () => this.setState({ cms: s.cms.map((x, k) => k === i ? Object.assign({}, x, { on: !x.on }) : x) }),
        up: () => this.moveCms(i, -1), down: () => this.moveCms(i, 1)
      })),
      annonceBar: s.annonces.filter(a => a).map(a => ({ text: a })),
      secHero: s.cms[0].on, secBest: s.cms[1].on, secActifs: s.cms[2].on, secCampaign: s.cms[3].on,
      secKits: s.cms[4].on, secReviews: s.cms[5].on, secLab: s.cms[6].on, secBenefits: s.cms[7].on,
      annonce1: s.annonces[0], annonce2: s.annonces[1], annonce3: s.annonces[2],
      setAnnonce1: (e) => this.setAnnonce(0, e.target.value),
      setAnnonce2: (e) => this.setAnnonce(1, e.target.value),
      setAnnonce3: (e) => this.setAnnonce(2, e.target.value),
      saveAnnonce: () => this.flash('Bandeau publié sur la boutique'),
      heroAdminRows: heroSlides.map(h => ({ img: h.img, kicker: h.kicker, headline: h.l1 + ' ' + h.l2 + ' ' + h.l3 })),

      labPublished: ARTICLES.filter((a, i) => (s.labStatus[i] || 'Publié') === 'Publié').length,
      labDrafts: ARTICLES.filter((a, i) => (s.labStatus[i] || 'Publié') !== 'Publié').length,
      labRows: ARTICLES.map((a, i) => {
        const st = s.labStatus[i] || 'Publié';
        return { title: a.title, cat: a.cat, author: a.author, date: a.date, read: a.read,
          slug: a.title.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
          status: st, btnBg: st === 'Publié' ? '#E8EDE8' : '#FFFFFF', btnColor: st === 'Publié' ? '#2F5A38' : '#8A8A8A',
          btnBorder: st === 'Publié' ? '#CFDFD2' : '#DADADA',
          toggle: () => { const next = st === 'Publié' ? 'Brouillon' : 'Publié';
            this.setState({ labStatus: Object.assign({}, s.labStatus, { [i]: next }) }); this.flash(a.title + ' → ' + next); } };
      }),

      anaKpis: [
        { label: 'Chiffre d\'affaires', value: this.mad(revenue), note: '30 derniers jours' },
        { label: 'Commandes', value: String(nOrders), note: 'Hors annulations' },
        { label: 'Panier moyen', value: this.mad(nOrders ? revenue / nOrders : 0), note: '+4% vs mois préc.' },
        { label: 'Unités vendues', value: String(s.orders.reduce((n, o) => n + o.items.reduce((m, i) => m + i.qty, 0), 0)), note: 'Toutes références' },
        { label: 'Taux de livraison', value: '92%', note: 'COD encaissé' }
      ],
      monthBars: [['Fév', 38], ['Mar', 46], ['Avr', 51], ['Mai', 62], ['Juin', 58], ['Juil', 74], ['Août', 88]].map(m => ({
        label: m[0], val: m[1] + 'k', h: Math.round(m[1] / 88 * 100) + '%'
      })),
      catSplit: (() => {
        const tot = {};
        P.forEach(x => { tot[x.cat] = (tot[x.cat] || 0) + x.reviews; });
        const sum = Object.values(tot).reduce((a, b) => a + b, 0) || 1;
        return Object.keys(tot).sort((a, b) => tot[b] - tot[a]).map(k => ({ name: k, pct: Math.round(tot[k] / sum * 100) + '%' }));
      })(),
      topProducts: P.slice().sort((a, b) => b.reviews - a.reviews).slice(0, 5).map(x => ({
        name: x.name, img: x.images[0], units: Math.round(x.reviews / 3), revenue: this.mad(this.eff(x) * Math.round(x.reviews / 3))
      })),
      cityPerf: ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Agadir', 'Fès'].map((c, i) => {
        const own = s.orders.filter(o => o.city === c);
        const del = [96, 94, 91, 88, 90, 86][i];
        return { name: c, orders: String(own.length || [12, 8, 6, 4, 3, 3][i]),
          revenue: this.mad(own.reduce((n, o) => n + o.total, 0) || [24800, 16400, 11900, 7600, 6100, 5400][i]),
          cod: [72, 64, 78, 81, 84, 88][i] + '%', delivery: del + '%',
          delColor: del >= 92 ? '#2F5A38' : del >= 89 ? '#8A6A22' : '#A83A30' };
      }),

      set: s.set,
      setStore: (e) => this.setSet('store', e.target.value),
      setEmailS: (e) => this.setSet('email', e.target.value),
      setPhoneS: (e) => this.setSet('phone', e.target.value),
      setLang: (e) => this.setSet('lang', e.target.value),
      setCurrency: (e) => this.setSet('currency', e.target.value),
      setFreeShip: (e) => this.setSet('freeShip', e.target.value),
      setShipCost: (e) => this.setSet('ship', e.target.value),
      setTax: (e) => this.setSet('tax', e.target.value),
      setLowGlobal: (e) => this.setSet('low', e.target.value),
      langChips: ['Français', 'Arabe', 'Anglais'].map(l => {
        const on = s.langs.indexOf(l) > -1;
        return { name: on ? l + ' · activée' : l, bg: on ? '#14120F' : '#FFFFFF', color: on ? '#FFFFFF' : '#8A8A8A',
          border: on ? '#14120F' : '#DADADA',
          toggle: () => this.setState({ langs: on ? s.langs.filter(x => x !== l) : s.langs.concat([l]) }) };
      }),
      payToggles: [
        { key: 'cod', name: 'Paiement à la livraison', note: 'Encaissement par le transporteur' },
        { key: 'card', name: 'Carte bancaire (CMI)', note: 'Passerelle marocaine, 3D Secure' },
        { key: 'transfer', name: 'Virement bancaire', note: 'Expédition après réception' }
      ].map(p => ({ name: p.name, note: p.note,
        state: s.payOn[p.key] ? 'Activé' : 'Désactivé',
        bg: s.payOn[p.key] ? '#E8EDE8' : '#F0F0F0', color: s.payOn[p.key] ? '#2F5A38' : '#8A8A8A',
        toggle: () => this.setState({ payOn: Object.assign({}, s.payOn, { [p.key]: !s.payOn[p.key] }) }) })),
      saveSettings: () => this.flash('Réglages enregistrés'),

      noOverlay: !s.drawer && !s.edit && !s.searchOpen && !s.mega,
      kits: kitDefs.map(b => {
        const items = b.ids.map(id => this.find(id)).filter(Boolean);
        const totalRaw = items.reduce((n, x) => n + this.eff(x), 0);
        const price = Math.round(totalRaw * 0.85 / 5) * 5;
        const maxQty = Math.min.apply(null, items.map(x => x.stock));
        return { name: b.name, tag: b.tag, blurb: b.blurb,
          imgs: items.map(x => ({ src: x.images[0] })),
          rows: items.map(x => ({ name: x.name, img: x.images[0], priceStr: this.mad(this.eff(x)) })),
          priceStr: this.mad(price), totalStr: this.mad(totalRaw), saveStr: this.mad(totalRaw - price),
          stockLabel: maxQty === 0 ? 'Coffret indisponible' : maxQty + ' coffrets disponibles',
          add: () => { if (maxQty === 0) { this.flash('Coffret indisponible'); return; } b.ids.forEach(id => this.add(id, 1)); this.flash('Coffret ajouté au panier'); } };
      }),

      ing: (() => {
        const name = s.ingName || 'Caviar';
        const meta = ING[name] || ING['Caviar'];
        const list = P.filter(x => x.ingredient === name);
        return { name: name, count: list.length, intro: meta.intro, what: meta.what, how: meta.how, who: meta.who,
          benefits: meta.benefits.map(b => ({ text: b })),
          img: list.length ? list[0].images[0] : P[0].images[0],
          shop: () => this.shop({ filterIng: name }, name),
          products: list.slice(0, 4).map(x => this.card(x)) };
      })(),

      labFeatured: Object.assign({}, ARTICLES[0], { open: () => this.nav('article', { artIdx: 0 }) }),
      labGrid: ARTICLES.slice(1).map((a, i) => Object.assign({}, a, { open: () => this.nav('article', { artIdx: i + 1 }) })),
      article: (() => {
        const i = s.artIdx || 0, a = ARTICLES[i] || ARTICLES[0];
        return Object.assign({}, a, {
          products: a.ids.map(id => this.find(id)).filter(Boolean).map(x => this.card(x)),
          next: () => this.nav('article', { artIdx: (i + 1) % ARTICLES.length })
        });
      })(),

      account: (() => {
        const own = s.orders.slice(0, 3);
        const spent = own.reduce((n, o) => n + o.total, 0);
        const first = s.lastOrder ? s.lastOrder.first : own[0].first;
        const ref = s.lastOrder || own[0];
        return {
          first: first, email: ref.email, phone: ref.phone, addr: ref.addr, city: ref.city, zip: ref.zip,
          orderCount: String(own.length), spent: this.mad(spent), wishCount: String(s.wish.length),
          orders: own.map(o => {
            const idx = STATUSES.indexOf(o.status);
            return { no: o.no, date: o.date, totalStr: this.mad(o.total),
              itemCount: o.items.reduce((n, i) => n + i.qty, 0) + ' articles',
              status: o.status, statusBg: styleFor(o.status)[0], statusColor: styleFor(o.status)[1],
              steps: STATUSES.map((st, k) => ({ name: st, bar: k <= idx ? '#14120F' : '#EDEDED', color: k <= idx ? '#14120F' : '#9B9B9B' })),
              items: o.items.map(i => ({ name: i.name, qty: i.qty, img: i.img, lineStr: this.mad(i.unit * i.qty) })),
              trackingLabel: o.tracking ? 'Suivi transporteur · ' + o.tracking : (o.status === 'Annulée' ? 'Commande annulée' : 'Numéro de suivi communiqué à l\'expédition'),
              reorder: () => { o.items.forEach(i => this.add(i.id, i.qty)); this.flash('Articles ajoutés au panier'); },
              review: () => this.flash('Formulaire d\'avis envoyé par email') };
          }),
          wish: s.wish.map(id => this.find(id)).filter(Boolean).map(x => ({ name: x.name, img: x.images[0], open: () => this.openPdp(x.id), add: () => this.add(x.id, 1) })),
          wishEmpty: s.wish.length === 0,
          editAddr: () => this.flash('Édition d\'adresse : à connecter au compte client'),
          support: () => this.flash('Service client : +212 5 22 00 00 00 · WhatsApp disponible')
        };
      })(),

      switchStoreBg: isStore ? '#14120F' : '#FFFFFF', switchStoreColor: isStore ? '#FFFFFF' : '#14120F',
      switchAdminBg: isAdmin ? '#14120F' : '#FFFFFF', switchAdminColor: isAdmin ? '#FFFFFF' : '#14120F',
      hasToast: !!s.toast, toast: s.toast
    };
  }
}

