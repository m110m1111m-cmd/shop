<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕັ້ງຄ່າລະບົບ - Settings</title>
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-nav">
        <h1><i class="fas fa-cog"></i> ຕັ້ງຄ່າລະບົບ Admin</h1>
        <a href="pos.php"><i class="fas fa-cash-register"></i> ກັບຄືນໜ້າຂາຍ</a>
    </nav>

    <div class="container">
        <div id="mainAlert" class="alert"></div>

        <div class="settings-card">
            <div class="settings-tabs">
                <button class="tab-btn active" onclick="showTab(event, 'shop')">
                    <i class="fas fa-store"></i> ຂໍ້ມູນຮ້ານ
                </button>
                <button class="tab-btn" onclick="showTab(event, 'finance')">
                    <i class="fas fa-wallet"></i> ການເງິນ & ພາສີ
                </button>
                <button class="tab-btn" onclick="showTab(event, 'receipt')">
                    <i class="fas fa-receipt"></i> ການຕັ້ງຄ່າໃບບິນ
                </button>
                <button class="tab-btn" onclick="showTab(event, 'maintenance')">
                    <i class="fas fa-tools"></i> ບຳລຸງຮັກສາ
                </button>
            </div>

            <div class="settings-content">
                <!-- Shop Profile Tab -->
                <div id="shopTab" class="tab-pane active">
                    <h2><i class="fas fa-id-card"></i> ໂປຣໄຟລ໌ຮ້ານ</h2>
                    <form id="shopForm">
                        <div class="section-card">
                            <div class="form-group">
                                <label>ໂລໂກ້ຮ້ານ</label>
                                <div class="logo-preview">
                                    <img id="logoPreview" src="" alt="Shop Logo">
                                </div>
                                <input type="file" name="shop_logo" accept="image/*" onchange="previewImage(this, 'logoPreview')">
                            </div>
                            <div class="form-group">
                                <label>ຊື່ຮ້ານ</label>
                                <input type="text" name="shop_name" id="shop_name" required>
                            </div>
                            <div class="form-group">
                                <label>ເບີໂທລະສັບ</label>
                                <input type="text" name="shop_phone" id="shop_phone">
                            </div>
                            <div class="form-group">
                                <label>ທີ່ຢູ່ຮ້ານ</label>
                                <textarea name="shop_address" id="shop_address" rows="3"></textarea>
                            </div>
                            <div class="form-group" style="display: flex; align-items: center; gap: 10px; padding: 10px; background: #f8fafc; border-radius: 8px;">
                                <input type="checkbox" name="low_stock_enabled" id="low_stock_enabled" value="1" style="width: 20px; height: 20px;">
                                <label style="margin: 0; cursor: pointer;" for="low_stock_enabled">ເປີດການແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ (Low Stock Alerts)</label>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                                <i class="fas fa-save"></i> ບັນທຶກຂໍ້ມູນຮ້ານ
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Financial Tab -->
                <div id="financeTab" class="tab-pane">
                    <h2><i class="fas fa-coins"></i> ການຕັ້ງຄ່າທາງດ້ານການເງິນ (Financial)</h2>
                    <form id="financeForm">
                        <div class="section-card grid-2">
                            <div class="form-group">
                                <label>ສະກຸນເງິນ (Currency)</label>
                                <select name="currency_code" id="currency_code" class="form-control">
                                    <option value="LAK">ກີບ (LAK)</option>
                                    <option value="THB">ບາດ (THB)</option>
                                    <option value="USD">ໂດລາ (USD)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ຈຸດທົດສະນິຍົມ (Decimal Places)</label>
                                <select name="decimal_places" id="decimal_places" class="form-control">
                                    <option value="0">0 (ບໍ່ມີ)</option>
                                    <option value="2">2 (.00)</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-card grid-2">
                            <div class="form-group">
                                <label>ພາສີ VAT (%)</label>
                                <input type="number" name="vat_rate" id="vat_rate" step="0.01" value="7">
                            </div>
                            <div class="form-group">
                                <label>ຮູບແບບການຄິດໄລ່</label>
                                <select name="vat_type" id="vat_type" class="form-control">
                                    <option value="exclusive">ບວກຕ່າງຫາກ (Exclusive: Price + Tax)</option>
                                    <option value="inclusive">ລວມໃນລາຄາສິນຄ້າ (Inclusive: Tax inside Price)</option>
                                </select>
                            </div>
                        </div>

                        <h2><i class="fas fa-qrcode"></i> ຂໍ້ມູນການຊຳລະເງິນ (F9 Payment)</h2>
                        <div class="section-card">
                            <div class="form-group">
                                <label>QR Code ສຳລັບການໂອນ</label>
                                <div class="qr-preview" style="background: #f1f5f9; padding: 10px; border-radius: 8px; width: fit-content; margin-bottom: 10px;">
                                    <img id="qrPreview" src="" alt="QR Code" style="max-width: 200px; max-height: 200px; object-fit: contain;">
                                </div>
                                <input type="file" name="qr_code" accept="image/*" onchange="previewImage(this, 'qrPreview')">
                            </div>
                            <div class="form-group">
                                <label>ຊື່ທະນາຄານ (Bank Name)</label>
                                <input type="text" name="bank_name" id="bank_name" placeholder="BCEL, OnePay, etc.">
                            </div>
                            <div class="form-group">
                                <label>ຊື່ບັນຊີ (Account Name)</label>
                                <input type="text" name="bank_account_name" id="bank_account_name">
                            </div>
                            <div class="form-group">
                                <label>ເລກບັນຊີ (Account Number)</label>
                                <input type="text" name="bank_account_number" id="bank_account_number">
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> ບັນທຶກການຕັ້ງຄ່າທາງດ້ານການເງິນ
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Receipt Tab -->
                <div id="receiptTab" class="tab-pane">
                    <h2><i class="fas fa-print"></i> ການຕັ້ງຄ່າໃບບິນ (Receipt Settings)</h2>
                    <form id="receiptForm">
                        <div class="section-card">
                            <div class="form-group">
                                <label>ຂໍ້ຄວາມເທິງຫົວບິນ (Header Message)</label>
                                <textarea name="receipt_header" id="receipt_header" rows="3" placeholder="Welcome to Our Shop..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>ຂໍ້ຄວາມທ້າຍບິນ (Footer Message)</label>
                                <textarea name="receipt_footer" id="receipt_footer" rows="3" placeholder="Thank you..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>ຂະໜາດເຈ້ຍ (Paper Size)</label>
                                <select name="receipt_paper_size" id="receipt_paper_size" class="form-control">
                                    <option value="80mm">80mm (Standard POS)</option>
                                    <option value="58mm">58mm (Mobile/Small)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> ບັນທຶກການຕັ້ງຄ່າໃບບິນ
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Maintenance Tab -->
                <div id="maintenanceTab" class="tab-pane">
                    <h2><i class="fas fa-tools"></i> ບຳລຸງຮັກສາ (Maintenance)</h2>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-database"></i> ສຳຮອງຂໍ້ມູນ (Database Backup)</h3>
                        <p style="color: #64748b; margin-bottom: 1rem; font-size: 0.9rem;">ດາວໂຫຼດຂໍ້ມູນທັງໝົດຂອງລະບົບເກັບໄວ້ໃນຮູບແບບ SQL file.</p>
                        <a href="api/backup.php" class="btn btn-success" style="width: fit-content; text-decoration: none;">
                            <i class="fas fa-download"></i> ດາວໂຫຼດໄຟລ໌ສຳຮອງ (.sql)
                        </a>
                    </div>

                    <h3><i class="fas fa-history"></i> ປະຫວັດການໃຊ້ງານ (System Logs)</h3>
                    <div class="section-card" style="max-height: 400px; overflow-y: auto; padding: 0;">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ເວລາ</th>
                                    <th>ຜູ້ໃຊ້</th>
                                    <th>ການກະທຳ</th>
                                    <th>ລາຍລະອຽດ</th>
                                </tr>
                            </thead>
                            <tbody id="logTableBody">
                                <!-- Logs will load here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadShopInfo();
        });

        // Tab Navigation
        function showTab(e, tabName) {
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById(tabName + 'Tab').classList.add('active');
            e.currentTarget.classList.add('active');

            if (tabName === 'maintenance') {
                loadLogs();
            }
        }

        // Shop Info
        async function loadShopInfo() {
            try {
                const res = await fetch('api/settings.php?action=get_shop_info');
                const json = await res.json();
                if (json.success) {
                    const data = json.data;
                    document.getElementById('shop_name').value = data.shop_name || '';
                    document.getElementById('shop_phone').value = data.shop_phone || '';
                    document.getElementById('shop_address').value = data.shop_address || '';
                    if (data.shop_logo) {
                        document.getElementById('logoPreview').src = data.shop_logo;
                    }
                    if (data.low_stock_enabled) {
                        document.getElementById('low_stock_enabled').checked = data.low_stock_enabled === '1';
                    }
                    // Finance Info
                    document.getElementById('currency_code').value = data.currency_code || 'LAK';
                    document.getElementById('decimal_places').value = data.decimal_places || '0';
                    document.getElementById('vat_rate').value = data.vat_rate || '0';
                    document.getElementById('vat_type').value = data.vat_type || 'exclusive';
                    document.getElementById('bank_name').value = data.bank_name || '';
                    document.getElementById('bank_account_name').value = data.bank_account_name || '';
                    document.getElementById('bank_account_number').value = data.bank_account_number || '';
                    if (data.qr_code) {
                        document.getElementById('qrPreview').src = data.qr_code;
                    }
                    // Receipt Info
                    document.getElementById('receipt_header').value = data.receipt_header || '';
                    document.getElementById('receipt_footer').value = data.receipt_footer || '';
                    document.getElementById('receipt_paper_size').value = data.receipt_paper_size || '80mm';
                }
            } catch (e) { console.error(e); }
        }

        async function loadLogs() {
            try {
                const res = await fetch('api/settings.php?action=get_logs');
                const json = await res.json();
                if (json.success) {
                    const tbody = document.getElementById('logTableBody');
                    tbody.innerHTML = json.data.map(log => `
                        <tr>
                            <td style="font-size: 0.8rem;">${log.created_at}</td>
                            <td><span class="badge badge-admin">${log.username || 'System'}</span></td>
                            <td><strong>${log.action}</strong></td>
                            <td style="font-size: 0.85rem;">${log.description || ''}</td>
                        </tr>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        document.getElementById('shopForm').onsubmit = handleFormSubmit;
        document.getElementById('financeForm').onsubmit = handleFormSubmit;
        document.getElementById('receiptForm').onsubmit = handleFormSubmit;

        async function handleFormSubmit(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'update_shop_info');
            
            // Special handling for shopForm's checkbox
            if (this.id === 'shopForm' && !fd.has('low_stock_enabled')) {
                fd.append('low_stock_enabled', '0');
            }

            try {
                const res = await fetch('api/settings.php', { method: 'POST', body: fd });
                const json = await res.json();
                showAlert(json.success ? 'success' : 'danger', json.message);
                if (json.success) loadShopInfo();
            } catch (e) { showAlert('danger', 'Error updating settings'); }
        }

        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById(previewId).src = e.target.result;
                reader.readAsDataURL(input.files[0]);
            }
        }

        function showAlert(type, msg) {
            const el = document.getElementById('mainAlert');
            el.className = 'alert alert-' + type;
            el.innerText = msg;
            el.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => el.style.display = 'none', 3000);
        }
    </script>
</body>
</html>
