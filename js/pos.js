// js/pos.js

let cart = [];
let currentPaymentMethod = 'cash';
const barcodeInput = document.getElementById('barcode-input');
const cartContainer = document.getElementById('cart-items-container');
const searchResults = document.getElementById('search-results');

let searchTimeout = null;
let selectedSearchIndex = -1;

// Sound effects (optional, mapping to typical POS sounds)
const beepSound = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU'); // dummy base64 for beep, just placeholder

const cameraModal = document.getElementById('camera-modal');
const btnOpenCamera = document.getElementById('btn-open-camera');
let html5QrCode = null;
let lastScannedCode = "";
let lastScannedTime = 0;

barcodeInput.addEventListener('blur', () => {
    if (!cameraModal.classList.contains('show')) { // Changed to 'show'
        setTimeout(() => barcodeInput.focus(), 100);
    }
});

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    const user = typeof APIMock !== 'undefined' ? APIMock.getCurrentUser() : null;
    if (!user) {
        window.location.href = 'index.html';
        return;
    }
    
    const usernameEl = document.getElementById('display-username');
    if (usernameEl) {
        usernameEl.innerText = user.name || user.username;
    }

    if (user.role !== 'admin') {
        const adminLinks = document.querySelectorAll('.admin-only');
        adminLinks.forEach(el => el.style.display = 'none');
    }

    loadSettings();
    loadCategories();
    loadProductGrid('all');
});

let allProducts = []; // Local cache for filtering
let shopSettings = {
    vat_rate: 7,
    vat_type: 'exclusive',
    currency_symbol: '₭',
    decimal_places: 0,
    bank_name: '',
    bank_account_name: '',
    bank_account_number: '',
    qr_code: ''
};

async function loadSettings() {
    try {
        const result = await APIMock.getSettings();
        if (result.success) {
            shopSettings = { ...shopSettings, ...result.data };
            
            // Update UI elements from settings
            const rateDisp = document.getElementById('vat-rate-display');
            if (rateDisp) rateDisp.innerText = shopSettings.vat_rate;
            
            // Update currency symbols on page
            document.querySelectorAll('.currency-symbol').forEach(el => {
                el.innerText = shopSettings.currency_symbol;
            });
            
            // Check Shop Open/Close status
            checkShopStatus();
            if (!window.shopStatusInterval) {
                window.shopStatusInterval = setInterval(checkShopStatus, 60000); // Check every minute
            }
        }
    } catch (e) { console.error('Error loading settings:', e); }
}

let isShopClosed = false;

function checkShopStatus() {
    if (!shopSettings.open_time || !shopSettings.close_time) return;

    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    const [openH, openM] = shopSettings.open_time.split(':').map(Number);
    const [closeH, closeM] = shopSettings.close_time.split(':').map(Number);
    const openMinutes = openH * 60 + (openM || 0);
    const closeMinutes = closeH * 60 + (closeM || 0);

    let isOpen = true;
    if (openMinutes < closeMinutes) {
        isOpen = currentMinutes >= openMinutes && currentMinutes < closeMinutes;
    } else if (openMinutes > closeMinutes) {
        isOpen = currentMinutes >= openMinutes || currentMinutes < closeMinutes;
    }

    const badge = document.getElementById('shop-status-badge');
    if (badge) {
        badge.style.display = 'inline-block';
        if (isOpen) {
            badge.innerText = '🟢 ເປີດຮ້ານ';
            badge.style.background = '#e6f4ea';
            badge.style.color = '#1e8e3e';
        } else {
            badge.innerText = '🔴 ປິດຮ້ານ';
            badge.style.background = '#fce8e8';
            badge.style.color = '#dc2626';
        }
    }

    // Auto-close feature
    const grid = document.getElementById('product-grid');
    const bInput = document.getElementById('barcode-input');
    
    if (shopSettings.auto_close_enabled === '1') {
        if (!isOpen) {
            isShopClosed = true;
            if (grid) grid.style.pointerEvents = 'none';
            if (grid) grid.style.opacity = '0.4';
            if (bInput) bInput.disabled = true;
            
            if (!document.getElementById('shop-closed-overlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'shop-closed-overlay';
                overlay.innerHTML = `
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 38, 38, 0.95); color: white; padding: 30px; border-radius: 12px; font-size: 1.5rem; font-weight: bold; text-align: center; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 350px;">
                        <i class="fas fa-store-alt-slash" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                        ປິດບໍລິການຊົ່ວຄາວ<br>
                        <small style="font-size: 1rem; font-weight: 400; display: block; margin-top: 10px;">ເວລາເຮັດວຽກ: ${shopSettings.open_time} - ${shopSettings.close_time}</small>
                    </div>
                `;
                const selPanel = document.querySelector('.selection-panel');
                if (selPanel) {
                    selPanel.style.position = 'relative';
                    selPanel.appendChild(overlay);
                }
            }
        } else {
            isShopClosed = false;
            if (grid) grid.style.pointerEvents = 'auto';
            if (grid) grid.style.opacity = '1';
            if (bInput) bInput.disabled = false;
            const overlay = document.getElementById('shop-closed-overlay');
            if (overlay) overlay.remove();
        }
    } else {
        // If auto-close is disabled but system was previously disabled
        isShopClosed = false;
        if (grid) grid.style.pointerEvents = 'auto';
        if (grid) grid.style.opacity = '1';
        if (bInput) bInput.disabled = false;
        const overlay = document.getElementById('shop-closed-overlay');
        if (overlay) overlay.remove();
    }
}

async function loadCategories() {
    try {
        const result = await APIMock.getCategories();
        if (result.success) {
            const bar = document.getElementById('category-bar');
            result.data.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'category-pill';
                btn.innerText = cat.name;
                btn.onclick = () => filterByCategory(cat.id, btn);
                bar.appendChild(btn);
            });
        }
    } catch (e) { console.error(e); }
}

async function loadProductGrid(catId = 'all') {
    const grid = document.getElementById('product-grid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 2rem; color: #888;"><i class="fas fa-spinner fa-spin"></i> ກຳລັງໂຫຼດສິນຄ້າ...</div>';
    
    try {
        const result = await APIMock.getProducts();
        if (result.success) {
            allProducts = result.data;
            renderGrid(allProducts);
        }
    } catch (e) { grid.innerHTML = 'Error loading products'; }
}

function renderGrid(products) {
    const grid = document.getElementById('product-grid');
    grid.innerHTML = '';
    
    products.forEach(p => {
        const card = document.createElement('div');
        card.className = 'product-card';
        const imgUrl = p.image_path || 'https://via.placeholder.com/150?text=No+Img';
        
        card.innerHTML = `
            <img src="${imgUrl}" class="product-card-img" onerror="this.src='https://via.placeholder.com/150?text=No+Img'">
            <div class="product-card-info">
                <div class="product-card-name">${p.name}</div>
                <div class="product-card-price">${formatCurrency(p.selling_price)} ${shopSettings.currency_symbol}</div>
            </div>
        `;
        
        card.onclick = () => {
            addToCart(p);
            // Quick visual feedback
            card.style.transform = 'scale(0.95)';
            setTimeout(() => card.style.transform = '', 100);
        };
        grid.appendChild(card);
    });
}

function filterByCategory(catId, btn) {
    // Update UI
    document.querySelectorAll('.category-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else document.querySelector('.category-pill').classList.add('active');

    if (catId === 'all') {
        renderGrid(allProducts);
    } else {
        const filtered = allProducts.filter(p => p.category_id == catId);
        renderGrid(filtered);
    }
}

// Barcode Scanning & Searching logic
barcodeInput.addEventListener('keydown', function (e) {
    const results = searchResults.querySelectorAll('.search-item');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedSearchIndex = Math.min(selectedSearchIndex + 1, results.length - 1);
        updateSearchSelection(results);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedSearchIndex = Math.max(selectedSearchIndex - 1, 0);
        updateSearchSelection(results);
    } else if (e.key === 'Enter') {
        if (selectedSearchIndex > -1 && results[selectedSearchIndex]) {
            e.preventDefault();
            results[selectedSearchIndex].click();
        } else {
            // Default barcode enter logic
            const barcode = this.value.trim();
            if (barcode) {
                fetchProductByBarcode(barcode);
            }
            this.value = '';
            closeSearch();
        }
    } else if (e.key === 'Escape') {
        closeSearch();
    }
});

barcodeInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 1) {
        closeSearch();
        return;
    }

    searchTimeout = setTimeout(() => {
        searchProducts(query);
    }, 300);
});

// Close search if clicking outside
document.addEventListener('click', function(e) {
    if (!barcodeInput.contains(e.target) && !searchResults.contains(e.target)) {
        closeSearch();
    }
});

function updateSearchSelection(results) {
    results.forEach((el, i) => {
        el.classList.toggle('selected', i === selectedSearchIndex);
        if (i === selectedSearchIndex) el.scrollIntoView({ block: 'nearest' });
    });
}

async function searchProducts(query) {
    try {
        const result = await APIMock.searchProducts(query);

        if (result.success && result.data.length > 0) {
            renderSearchResults(result.data);
        } else {
            searchResults.innerHTML = '<div style="padding: 15px; color: #888; text-align: center;">ບໍ່ພົບສິນຄ້າ / No results</div>';
            searchResults.classList.add('active');
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function renderSearchResults(data) {
    searchResults.innerHTML = '';
    selectedSearchIndex = -1;
    
    data.forEach(p => {
        const div = document.createElement('div');
        div.className = 'search-item';
        const imgUrl = p.image_path || 'https://via.placeholder.com/40?text=No+Img';
        
        div.innerHTML = `
            <img src="${imgUrl}" class="search-item-img">
            <div class="search-item-info">
                <strong>${p.name}</strong>
                <span>${p.barcode} | ${p.unit_name}</span>
            </div>
            <div class="search-item-price">${formatCurrency(p.selling_price)}</div>
        `;
        
        div.onclick = () => {
            addToCart(p);
            barcodeInput.value = '';
            closeSearch();
        };
        
        searchResults.appendChild(div);
    });
    
    searchResults.classList.add('active');
}

function closeSearch() {
    searchResults.classList.remove('active');
    selectedSearchIndex = -1;
}

// Shortcut keys
document.addEventListener('keydown', function(e) {
    if (e.key === 'F8') { e.preventDefault(); openPaymentModal('cash'); }
    if (e.key === 'F9') { e.preventDefault(); openPaymentModal('transfer'); }
    if (e.key === 'F12') { e.preventDefault(); clearCart(); }
});

async function fetchProductByBarcode(barcode) {
    try {
        const result = await APIMock.getProductByBarcode(barcode);

        if (result.success) {
            addToCart(result.data);
            // beepSound.play().catch(e => {});
        } else {
            alert('ບໍ່ພົບສິນຄ້ານີ້ໃນລະບົບ! / Product not found!');
        }
    } catch (error) {
        console.error('Error fetching product:', error);
        alert('ໂປຣດກວດສອບການເຊື່ອມຕໍ່ / Connection error');
    }
}

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    if (existingItem) {
        existingItem.qty += 1;
    } else {
        cart.push({
            ...product,
            qty: 1,
            discount_input: '', // Store the raw input like '10%' or '500'
            discount_amount: 0   // Calculated fixed value
        });
    }
    renderCart();
}

function changeQty(id, delta) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.qty = Math.max(0, item.qty + delta);
        if (item.qty === 0) {
            if (confirm('ລົບລາຍການນີ້ອອກຈາກກະຕ່າ?')) {
                removeFromCart(id);
                return;
            } else {
                item.qty = 1;
            }
        }
        renderCart();
    }
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

function updateQty(id, newQty) {
    const item = cart.find(item => item.id === id);
    if (item) {
        let q = parseFloat(newQty);
        if (isNaN(q) || q < 0) q = 0;
        item.qty = q;
        renderCart();
    }
}

function updatePrice(id, newPrice) {
    const item = cart.find(item => item.id === id);
    if (item) {
        let p = parseFloat(newPrice.replace(/,/g, ''));
        if (isNaN(p) || p < 0) p = 0;
        item.selling_price = p;
        renderCart();
    }
}

function renderCart() {
    cartContainer.innerHTML = '';
    let totalQty = 0;
    let subtotalBeforeGlobal = 0;

    cart.forEach(item => {
        // Calculate item discount
        let itemDiscount = 0;
        const discInput = item.discount_input || '';
        if (discInput.endsWith('%')) {
            const percent = parseFloat(discInput) || 0;
            itemDiscount = (item.selling_price * percent / 100) * item.qty;
        } else {
            itemDiscount = parseFloat(discInput) || 0;
        }
        item.discount_amount = itemDiscount;

        const rowTotal = (item.qty * item.selling_price) - itemDiscount;
        totalQty += item.qty;
        subtotalBeforeGlobal += rowTotal;

        const row = document.createElement('div');
        row.className = 'cart-item';
        const imgHtml = item.image_path 
            ? `<img src="${item.image_path}" class="cart-img">`
            : `<div class="cart-img-placeholder"><i class="fas fa-image"></i></div>`;

        row.innerHTML = `
            <div style="text-align: center;">${imgHtml}</div>
            <div>
                <strong style="display:block; font-size: 0.95rem;">${item.name}</strong>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">${item.barcode}</span>
            </div>
            <div style="font-weight: 600;">${formatCurrency(item.selling_price)}</div>
            <div>
                <div class="qty-controls">
                    <button class="btn-qty" onclick="changeQty(${item.id}, -1)"><i class="fas fa-minus"></i></button>
                    <input type="number" class="item-qty-input" value="${item.qty}" onchange="updateQty(${item.id}, this.value)">
                    <button class="btn-qty" onclick="changeQty(${item.id}, 1)"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div>
                <div class="discount-pill">
                    <input type="text" value="${item.discount_input}" placeholder="0" 
                           onchange="updateItemDiscount(${item.id}, this.value)">
                </div>
            </div>
            <div style="font-weight: 800; color: var(--text-primary); text-align: right;">${formatCurrency(rowTotal)}</div>
            <div style="text-align: right;">
                <button class="btn-remove" onclick="removeFromCart(${item.id})" title="ລົບອອກ"><i class="fas fa-trash-alt"></i></button>
            </div>
        `;
        cartContainer.appendChild(row);
    });

    const totals = getFinalTotals();
    const finalTotal = totals.finalTotal;

    const totalEl = document.getElementById('grand-total');
    if (totalEl) totalEl.innerText = formatCurrency(Math.max(0, finalTotal)) + ' ' + shopSettings.currency_symbol;

    const vatEl = document.getElementById('vat-amount');
    if (vatEl) vatEl.innerText = formatCurrency(totals.vatAmount) + ' ' + shopSettings.currency_symbol;
    
    // Auto scroll to bottom
    cartContainer.scrollTop = cartContainer.scrollHeight;
}

function getFinalTotals() {
    let subtotalBeforeGlobal = 0;
    cart.forEach(item => {
        let itemDisc = 0;
        const discInput = item.discount_input || '';
        if (discInput.endsWith('%')) {
            const percent = parseFloat(discInput) || 0;
            itemDisc = (item.selling_price * percent / 100) * item.qty;
        } else {
            itemDisc = parseFloat(discInput) || 0;
        }
        subtotalBeforeGlobal += (item.qty * item.selling_price) - itemDisc;
    });

    const globalDiscInput = document.getElementById('global-discount').value || '';
    let globalDiscValue = 0;
    if (globalDiscInput.endsWith('%')) {
        const percent = parseFloat(globalDiscInput) || 0;
        globalDiscValue = subtotalBeforeGlobal * percent / 100;
    } else {
        globalDiscValue = parseFloat(globalDiscInput) || 0;
    }

    const netBeforeTax = subtotalBeforeGlobal - globalDiscValue;
    const rate = parseFloat(shopSettings.vat_rate) || 0;
    let vatAmount = 0;
    let finalTotal = 0;

    if (shopSettings.vat_type === 'inclusive') {
        // Tax is already inside the price
        finalTotal = netBeforeTax;
        vatAmount = finalTotal - (finalTotal / (1 + (rate / 100)));
    } else {
        // Tax is added on top
        vatAmount = netBeforeTax * (rate / 100);
        finalTotal = netBeforeTax + vatAmount;
    }

    return {
        subtotal: subtotalBeforeGlobal, 
        globalDiscount: globalDiscValue,
        vatAmount: vatAmount,
        finalTotal: Math.max(0, finalTotal)
    };
}

function updateItemDiscount(id, value) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.discount_input = value;
        renderCart();
    }
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('ທ່ານຕ້ອງການຍົກເລີກບິນນີ້ແທ້ບໍ່?')) {
        cart = [];
        renderCart();
    }
}

// Payment Modal Logic
const modal = document.getElementById('payment-modal');
const cashReceivedInput = document.getElementById('cash-received');

function openPaymentModal(method) {
    if (cart.length === 0) {
        alert('ບໍ່ມີສິນຄ້າໃນກະຕ່າ!');
        return;
    }
    currentPaymentMethod = method;
    const total = getFinalTotals().finalTotal;
    
    document.getElementById('modal-amount-due').innerText = formatCurrency(total) + ' ' + shopSettings.currency_symbol;
    document.getElementById('modal-title').innerText = method === 'cash' ? 'ຮັບເງິນສົດ (Cash)' : 'ເງິນໂອນ (Transfer)';
    
    const transferInfo = document.getElementById('transfer-info');
    if (method === 'cash') {
        cashReceivedInput.style.display = 'block';
        cashReceivedInput.value = '';
        document.getElementById('change-row').style.display = 'none'; // Only show when amount entered
        document.getElementById('modal-change').innerText = '0 ' + shopSettings.currency_symbol;
        if (transferInfo) transferInfo.style.display = 'none';
    } else {
        cashReceivedInput.style.display = 'none';
        document.getElementById('change-row').style.display = 'none';
        
        // Show QR/Bank Info
        if (transferInfo) {
            transferInfo.style.display = 'block';
            document.getElementById('modal-bank-name').innerText = shopSettings.bank_name || 'ບໍ່ມີຂໍ້ມູນທະນາຄານ';
            document.getElementById('modal-bank-acc-name').innerText = shopSettings.bank_account_name || '';
            document.getElementById('modal-bank-acc-num').innerText = shopSettings.bank_account_number || '';
            const qrImg = document.getElementById('modal-qr-image');
            if (qrImg) {
                qrImg.src = shopSettings.qr_code || 'https://via.placeholder.com/200?text=No+QR+Code';
                qrImg.style.display = shopSettings.qr_code ? 'inline-block' : 'none';
            }
        }
    }
    
    modal.classList.add('show');
    if (method === 'cash') setTimeout(() => cashReceivedInput.focus(), 100);
}

function closePaymentModal() {
    modal.classList.remove('show');
}

function setQuickCash(amount) {
    cashReceivedInput.value = amount;
    calculateChange();
}

function setExactCash() {
    const total = getFinalTotals().finalTotal;
    cashReceivedInput.value = Math.ceil(total);
    calculateChange();
}

function calculateChange() {
    const total = getFinalTotals().finalTotal;
    const received = parseFloat(cashReceivedInput.value) || 0;
    const change = received - total;
    
    const changeRow = document.getElementById('change-row');
    if (cashReceivedInput.value === '') {
        changeRow.style.display = 'none';
        document.getElementById('btn-confirm-payment').disabled = true;
        return;
    }

    changeRow.style.display = 'block';
    if (change >= 0) {
        document.getElementById('modal-change').innerText = formatCurrency(change) + ' ' + shopSettings.currency_symbol;
        changeRow.style.background = '#f0fdf4';
        changeRow.style.borderColor = '#bbf7d0';
        document.getElementById('modal-change').style.color = '#15803d';
        document.getElementById('btn-confirm-payment').disabled = false;
    } else {
        document.getElementById('modal-change').innerText = 'ເງິນບໍ່ພໍ';
        changeRow.style.background = '#fef2f2';
        changeRow.style.borderColor = '#fecaca';
        document.getElementById('modal-change').style.color = '#dc2626';
        document.getElementById('btn-confirm-payment').disabled = true;
    }
}

async function processPayment() {
    const totals = getFinalTotals();
    const finalTotal = totals.finalTotal;
    
    if (currentPaymentMethod === 'cash') {
        const received = parseFloat(cashReceivedInput.value) || 0;
        if (received < finalTotal) {
            alert('ຮັບເງິນມາບໍ່ພໍດີກັບຍອດລວມ!');
            return;
        }
    }

    const payload = {
        payment_method: currentPaymentMethod,
        total_amount: finalTotal,
        discount_amount: totals.globalDiscount,
        vat_amount: totals.vatAmount,
        items: cart.map(item => ({
            ...item,
            discount_amount: item.discount_amount
        }))
    };

    try {
        const result = await APIMock.checkout(payload);
        
        if (result.success) {
            alert('ຊຳລະເງິນສຳເລັດ! / Payment successful!');
            cart = [];
            renderCart();
            closePaymentModal();
        } else {
            alert('ເກີດຂໍ້ຜິດພາດ: ' + result.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('ໂປຣດກວດສອບການເຊື່ອມຕໍ່ / Connection error');
    }
}

function formatCurrency(amount) {
    const dec = parseInt(shopSettings.decimal_places) || 0;
    return new Intl.NumberFormat('lo-LA', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
    }).format(amount);
}

// --- Camera Scanner Logic ---
btnOpenCamera.addEventListener('click', openCameraModal);

function openCameraModal() {
    cameraModal.classList.add('show');
    startCamera();
}

function closeCameraModal() {
    stopCamera().then(() => {
        cameraModal.classList.remove('show');
        barcodeInput.focus();
    });
}

function startCamera() {
    html5QrCode = new Html5Qrcode("reader");
    const config = { 
        fps: 10, 
        qrbox: { width: 300, height: 120 }, // ປັບເປັນຮູບສີ່ແຈສາກຍາວ ເພື່ອສະແກນບາໂຄດສິນຄ້າໄດ້ດີຂຶ້ນ
        disableFlip: false 
    };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        (decodedText) => {
            // Process scan result
            handleScanSuccess(decodedText);
        },
        (errorMessage) => {
            // Handle parse error, usually can be ignored
        }
    ).catch((err) => {
        console.error("Camera start error:", err);
        alert("ບໍ່ສາມາດເປີດກ້ອງໄດ້: " + err);
        closeCameraModal();
    });
}

async function stopCamera() {
    if (html5QrCode && html5QrCode.isScanning) {
        return html5QrCode.stop().then(() => {
            html5QrCode.clear();
            html5QrCode = null;
        });
    }
    return Promise.resolve();
}

function handleScanSuccess(code) {
    const now = Date.now();
    // Prevent double scanning the same code within 3 seconds
    if (code === lastScannedCode && (now - lastScannedTime) < 3000) {
        return;
    }

    lastScannedCode = code;
    lastScannedTime = now;

    // Play beep sound
    playScanBeep();

    // Close camera on successful scan
    closeCameraModal();

    // Fetch and add to cart
    fetchProductByBarcode(code);
}

function playScanBeep() {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);

    gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
    gainNode.gain.linearRampToValueAtTime(0.1, audioCtx.currentTime + 0.01);
    gainNode.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 0.1);

    oscillator.start(audioCtx.currentTime);
    oscillator.stop(audioCtx.currentTime + 0.1);
}
