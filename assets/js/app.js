// ============================================
// MARKETOTO - APP.JS
// ============================================

// ---------- TOAST NOTIFICATIONS ----------
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span>${icons[type] || ''}</span> ${message}`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3500);
}

// ---------- NAVBAR MOBILE TOGGLE ----------
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            menu.classList.toggle('open');
        });
    }
});

// ---------- TABS ----------
function initTabs(containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const buttons = container.querySelectorAll('.tabs button');
    const contents = container.querySelectorAll('.tab-content');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            buttons.forEach(b => b.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const targetContent = container.querySelector(`#${target}`);
            if (targetContent) targetContent.classList.add('active');
        });
    });
}

// ---------- CUSTOMER REGISTRATION ----------
function initRegistration() {
    const form = document.getElementById('register-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        const isim = form.querySelector('#isim').value.trim();
        const telefon = form.querySelector('#telefon').value.trim();
        const email = form.querySelector('#email').value.trim();

        // Validation
        let hasError = false;
        if (isim.length < 2) {
            showFieldError('isim', 'İsim en az 2 karakter olmalıdır.');
            hasError = true;
        }

        const phoneRegex = /^05\d{9}$/;
        if (!phoneRegex.test(telefon)) {
            showFieldError('telefon', 'Geçerli bir telefon numarası girin (05XXXXXXXXX).');
            hasError = true;
        }

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFieldError('email', 'Geçerli bir e-posta adresi girin.');
            hasError = true;
        }

        if (hasError) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></span> Kaydediliyor...';

        try {
            const formData = new FormData();
            formData.append('isim', isim);
            formData.append('telefon', telefon);
            formData.append('email', email);

            const res = await fetch('api_customer.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                showToast('Müşteri başarıyla kaydedildi! 🎉', 'success');
                form.reset();

                // Show success alert above form
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success';
                alertDiv.innerHTML = `✅ <strong>${data.customer.isim}</strong> başarıyla kaydedildi!`;
                form.parentNode.insertBefore(alertDiv, form);
                setTimeout(() => alertDiv.remove(), 5000);
            } else {
                showToast(data.error || 'Kayıt sırasında bir hata oluştu.', 'error');
            }
        } catch (err) {
            showToast('Sunucu ile bağlantı kurulamadı.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '🚀 Kayıt Ol';
        }
    });

    // Phone formatting
    const phoneInput = form.querySelector('#telefon');
    if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').substring(0, 11);
        });
    }
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const group = field.closest('.form-group');
    if (group) {
        group.classList.add('error');
        let errMsg = group.querySelector('.error-msg');
        if (!errMsg) {
            errMsg = document.createElement('div');
            errMsg.className = 'error-msg';
            group.appendChild(errMsg);
        }
        errMsg.textContent = message;
    }
}

function clearErrors() {
    document.querySelectorAll('.form-group.error').forEach(g => {
        g.classList.remove('error');
        const msg = g.querySelector('.error-msg');
        if (msg) msg.remove();
    });
}

// ---------- PRODUCT LOADING (INDEX) ----------
async function loadProducts(filter = 'all') {
    const grid = document.getElementById('products-grid');
    if (!grid) return;

    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;"><div class="spinner"></div></div>';

    try {
        const params = new URLSearchParams();
        if (filter !== 'all') params.append('kategori', filter);

        const res = await fetch('api_products.php?' + params.toString());
        const data = await res.json();

        if (data.products && data.products.length > 0) {
            grid.innerHTML = data.products.map(p => createProductCard(p)).join('');
        } else {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="empty-icon">🔍</div>
                    <h3>Ürün bulunamadı</h3>
                    <p>Farklı bir filtre ile tekrar deneyin.</p>
                </div>
            `;
        }

        // Update stats
        if (data.stats) {
            updateStats(data.stats);
        }
    } catch (err) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-icon">⚠️</div>
                <h3>Yükleme Hatası</h3>
                <p>Ürünler yüklenirken bir hata oluştu.</p>
            </div>
        `;
    }
}

function createProductCard(p) {
    const discountPercent = p.indirim_yuzdesi || 0;
    const effectivePrice = p.efektif_fiyat || p.satis_fiyati;
    const daysLeft = p.kalan_gun;

    let sktClass = 'safe';
    let sktText = `${daysLeft} gün`;
    if (daysLeft <= 1) { sktClass = 'danger'; sktText = 'Son gün!'; }
    else if (daysLeft <= 3) { sktClass = 'danger'; sktText = `${daysLeft} gün kaldı!`; }
    else if (daysLeft <= 7) { sktClass = 'warning'; sktText = `${daysLeft} gün kaldı`; }

    // Category emoji mapping for fallback
    const categoryEmojis = {
        'Süt Ürünleri': '🥛', 'Et & Tavuk': '🍗', 'Meyve & Sebze': '🥬',
        'Fırın': '🍞', 'Temel Gıda': '🌾', 'İçecekler': '🥤',
        'Temizlik': '🧴', 'Kahvaltılık': '🍯', 'Konserve': '🥫',
        'Sos & Baharat': '🌶️', 'Atıştırmalık': '🍫', 'Dondurma': '🍦',
        'Bebek': '🍼', 'Kişisel Bakım': '🧼', 'Ev & Yaşam': '🏠'
    };
    const emoji = categoryEmojis[p.kategori] || '🛒';

    // Product image: use resim_url if available, otherwise show category emoji
    let imageHTML;
    if (p.resim_url) {
        imageHTML = `<div class="product-image-wrapper"><img src="${p.resim_url}" alt="${p.urun_adi}" loading="lazy" onerror="this.parentNode.innerHTML='<span class=\\'product-emoji\\'>${emoji}</span>'"></div>`;
    } else {
        imageHTML = `<div class="product-image-wrapper"><span class="product-emoji">${emoji}</span></div>`;
    }

    return `
        <div class="product-card" data-product-id="${p.product_id}" data-inventory-id="${p.inventory_id}">
            ${discountPercent > 0 ? `<div class="discount-badge">%${discountPercent}</div>` : ''}
            ${imageHTML}
            <div class="card-header">
                <span class="product-name">${p.urun_adi}</span>
                <span class="product-category">${p.kategori}</span>
            </div>
            <div class="card-body">
                <div class="market-name">🏪 ${p.market_isim} - ${p.sube}</div>
                <div class="price-row">
                    <span class="price-current">₺${parseFloat(effectivePrice).toFixed(2)}</span>
                    ${discountPercent > 0 ? `<span class="price-old">₺${parseFloat(p.satis_fiyati).toFixed(2)}</span>` : ''}
                </div>
                <div class="skt-row">
                    <span class="skt-badge ${sktClass}">📅 SKT: ${sktText}</span>
                    <span style="font-size:0.8rem;color:var(--neutral-500);">Stok: ${p.stok_miktari}</span>
                </div>
                <button class="btn-add-basket" onclick="addToBasket(${p.inventory_id}, '${p.urun_adi.replace(/'/g, "\\'")}', '${p.market_isim}', ${effectivePrice})">
                    🛒 Sepete Ekle
                </button>
            </div>
        </div>
    `;
}

function updateStats(stats) {
    const els = {
        'stat-total': stats.toplam_urun,
        'stat-expiring': stats.skt_yaklasan,
        'stat-discounted': stats.indirimli,
        'stat-markets': stats.market_sayisi
    };
    for (const [id, val] of Object.entries(els)) {
        const el = document.getElementById(id);
        if (el) animateCounter(el, val);
    }
}

function animateCounter(el, target) {
    const start = parseInt(el.textContent) || 0;
    const duration = 800;
    const startTime = Date.now();

    function update() {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (target - start) * eased);
        if (progress < 1) requestAnimationFrame(update);
    }
    update();
}

// ---------- SEARCH ----------
function initSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchProducts(e.target.value.trim());
        }, 300);
    });
}

async function searchProducts(query) {
    const grid = document.getElementById('products-grid');
    if (!grid) return;

    const params = new URLSearchParams();
    if (query) params.append('arama', query);

    try {
        const res = await fetch('api_products.php?' + params.toString());
        const data = await res.json();

        if (data.products && data.products.length > 0) {
            grid.innerHTML = data.products.map(p => createProductCard(p)).join('');
        } else {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="empty-icon">🔍</div>
                    <h3>"${query}" için sonuç bulunamadı</h3>
                </div>
            `;
        }
    } catch (err) {
        console.error('Search error:', err);
    }
}

// ---------- FILTER PILLS ----------
function initFilterPills() {
    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            loadProducts(pill.dataset.filter || 'all');
        });
    });
}

// ---------- BASKET ----------
let basket = JSON.parse(localStorage.getItem('smartBasket') || '[]');

function addToBasket(inventoryId, name, market, price) {
    const existingIndex = basket.findIndex(item => item.inventoryId === inventoryId);
    if (existingIndex !== -1) {
        basket[existingIndex].miktar += 1;
        showToast(`${name} miktarı artırıldı!`, 'info');
    } else {
        basket.push({
            inventoryId,
            name,
            market,
            price: parseFloat(price),
            miktar: 1
        });
        showToast(`${name} sepete eklendi!`, 'success');
    }
    saveBasket();
    updateBasketUI();
    updateBasketBadge();
}

function removeFromBasket(inventoryId) {
    basket = basket.filter(item => item.inventoryId !== inventoryId);
    saveBasket();
    updateBasketUI();
    updateBasketBadge();
    showToast('Ürün sepetten çıkarıldı.', 'warning');
}

function updateQuantity(inventoryId, delta) {
    const item = basket.find(i => i.inventoryId === inventoryId);
    if (item) {
        item.miktar += delta;
        if (item.miktar <= 0) {
            removeFromBasket(inventoryId);
            return;
        }
        saveBasket();
        updateBasketUI();
    }
}

function clearBasket() {
    basket = [];
    saveBasket();
    updateBasketUI();
    updateBasketBadge();
}

function saveBasket() {
    localStorage.setItem('smartBasket', JSON.stringify(basket));
}

function updateBasketBadge() {
    const badge = document.getElementById('basket-badge');
    if (badge) {
        const count = basket.reduce((sum, item) => sum + item.miktar, 0);
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

function updateBasketUI() {
    const container = document.getElementById('basket-items');
    const totalEl = document.getElementById('basket-total-amount');

    if (!container) return;

    if (basket.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="padding: 2rem 0;">
                <div class="empty-icon">🛒</div>
                <h3>Sepetiniz boş</h3>
                <p>Ürünleri ekleyerek başlayın</p>
            </div>
        `;
        if (totalEl) totalEl.textContent = '₺0.00';
        return;
    }

    container.innerHTML = basket.map(item => `
        <div class="basket-item">
            <div class="item-info">
                <div class="item-name">${item.name} (x${item.miktar})</div>
                <div class="item-market">🏪 ${item.market}</div>
            </div>
            <span class="item-price">₺${(item.price * item.miktar).toFixed(2)}</span>
            <button class="item-remove" onclick="removeFromBasket(${item.inventoryId})" title="Kaldır">✕</button>
        </div>
    `).join('');

    const total = basket.reduce((sum, item) => sum + item.price * item.miktar, 0);
    if (totalEl) totalEl.textContent = `₺${total.toFixed(2)}`;
}

// ---------- SMART BASKET COMPARISON ----------
async function calculateSmartBasket() {
    if (basket.length === 0) {
        showToast('Lütfen önce sepete ürün ekleyin!', 'warning');
        return;
    }

    const resultDiv = document.getElementById('comparison-results');
    if (!resultDiv) return;

    resultDiv.innerHTML = '<div style="text-align:center;"><div class="spinner"></div><p style="color:var(--neutral-400);margin-top:1rem;">En uygun fiyatlar hesaplanıyor...</p></div>';

    try {
        const productNames = basket.map(item => item.name);
        const res = await fetch('api_basket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ products: productNames })
        });
        const data = await res.json();

        if (data.success) {
            renderComparison(data);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-error">❌ ${data.error || 'Hesaplama hatası'}</div>`;
        }
    } catch (err) {
        resultDiv.innerHTML = '<div class="alert alert-error">❌ Sunucu ile bağlantı kurulamadı.</div>';
    }
}

function renderComparison(data) {
    const resultDiv = document.getElementById('comparison-results');
    if (!resultDiv) return;

    let html = '';

    // SAVINGS BANNER
    if (data.tasarruf && data.tasarruf > 0) {
        html += `
            <div class="savings-banner">
                <span class="savings-icon">💰</span>
                <div class="savings-text">
                    <h3>Akıllı Sepet Tasarrufu!</h3>
                    <p>Karma sepet ile en pahalı seçeneğe göre kazancınız</p>
                </div>
                <span class="savings-amount">₺${parseFloat(data.tasarruf).toFixed(2)}</span>
            </div>
        `;
    }

    // KARMA SEPET
    if (data.karma_sepet) {
        html += `
            <div class="comparison-result">
                <h3>🌈 Karma Sepet (En Ucuz Kombinasyon)</h3>
                <div class="comparison-card best">
                    <div class="comp-header">
                        <span class="comp-market">Farklı Marketlerden</span>
                        <span class="comp-total">₺${parseFloat(data.karma_sepet.toplam).toFixed(2)}</span>
                    </div>
                    <div class="comp-items">
                        ${data.karma_sepet.urunler.map(u => `
                            <div class="comp-item">
                                <span>${u.urun_adi} <small style="color:var(--primary-400)">(${u.market})</small></span>
                                <span class="item-price">₺${parseFloat(u.fiyat).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    // TEK MARKET SEPETLER
    if (data.tek_market_sepetler && data.tek_market_sepetler.length > 0) {
        html += `<div class="comparison-result"><h3>🏪 Tek Market Sepetleri</h3>`;
        data.tek_market_sepetler.forEach((sepet, index) => {
            const isBest = index === 0;
            html += `
                <div class="comparison-card ${isBest ? 'best' : ''}">
                    <div class="comp-header">
                        <span class="comp-market">${sepet.market}</span>
                        <span class="comp-total">${sepet.eksik > 0 ? '<span style="font-size:0.8rem;color:var(--danger-400);margin-right:8px;">' + sepet.eksik + ' ürün yok</span>' : ''}₺${parseFloat(sepet.toplam).toFixed(2)}</span>
                    </div>
                    <div class="comp-items">
                        ${sepet.urunler.map(u => `
                            <div class="comp-item">
                                <span>${u.urun_adi}</span>
                                ${u.bulunamadi ? '<span class="not-available">Bulunamadı</span>' : `<span class="item-price">₺${parseFloat(u.fiyat).toFixed(2)}</span>`}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        });
        html += `</div>`;
    }

    resultDiv.innerHTML = html;
}

// ---------- PROFIT PAGE ----------
async function loadProfitData() {
    try {
        const res = await fetch('api_profit.php');
        const data = await res.json();

        if (data.success) {
            // Update stat cards
            if (data.ozet) {
                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };
                setVal('profit-total-sales', '₺' + parseFloat(data.ozet.toplam_satis).toFixed(2));
                setVal('profit-total-profit', '₺' + parseFloat(data.ozet.toplam_kar).toFixed(2));
                setVal('profit-total-orders', data.ozet.toplam_siparis);
                setVal('profit-avg-order', '₺' + parseFloat(data.ozet.ortalama_siparis).toFixed(2));
            }

            // Render chart
            if (data.gunluk && data.gunluk.length > 0) {
                renderBarChart(data.gunluk);
            }

            // Render recent sales table
            if (data.son_satislar && data.son_satislar.length > 0) {
                renderSalesTable(data.son_satislar);
            }
        }
    } catch (err) {
        console.error('Profit data error:', err);
    }
}

function renderBarChart(data) {
    const chart = document.getElementById('profit-chart');
    if (!chart) return;

    const maxVal = Math.max(...data.map(d => Math.max(parseFloat(d.toplam_tutar), parseFloat(d.toplam_kar))));

    chart.innerHTML = data.map(d => {
        const salesHeight = (parseFloat(d.toplam_tutar) / maxVal) * 180;
        const profitHeight = (parseFloat(d.toplam_kar) / maxVal) * 180;
        const formattedDate = new Date(d.tarih).toLocaleDateString('tr-TR', { day: 'numeric', month: 'short' });

        return `
            <div class="bar" style="flex:1;display:flex;gap:4px;align-items:flex-end;">
                <div style="flex:1;">
                    <div class="bar-fill profit" style="height:${salesHeight}px;"></div>
                </div>
                <div style="flex:1;">
                    <div class="bar-fill" style="height:${profitHeight}px;background:var(--gradient-gold);"></div>
                </div>
                <span class="bar-label">${formattedDate}</span>
                <span class="bar-value">₺${parseFloat(d.toplam_kar).toFixed(0)}</span>
            </div>
        `;
    }).join('');
}

function renderSalesTable(sales) {
    const tbody = document.getElementById('sales-tbody');
    if (!tbody) return;

    tbody.innerHTML = sales.map(s => `
        <tr>
            <td>#${s.id}</td>
            <td>${s.musteri || 'Misafir'}</td>
            <td>₺${parseFloat(s.toplam_tutar).toFixed(2)}</td>
            <td style="color:${parseFloat(s.toplam_kar) >= 0 ? 'var(--primary-400)' : 'var(--danger-400)'}">
                ${parseFloat(s.toplam_kar) >= 0 ? '+' : ''}₺${parseFloat(s.toplam_kar).toFixed(2)}
            </td>
            <td>${new Date(s.tarih).toLocaleDateString('tr-TR')}</td>
        </tr>
    `).join('');
}

// ---------- INITIALIZATION ----------
document.addEventListener('DOMContentLoaded', () => {
    // Update basket badge on every page
    updateBasketBadge();

    // Page-specific init
    const page = document.body.dataset.page;

    if (page === 'index') {
        loadProducts();
        initSearch();
        initFilterPills();
    } else if (page === 'register') {
        initRegistration();
    } else if (page === 'basket') {
        updateBasketUI();
    } else if (page === 'profit') {
        loadProfitData();
    }
});
