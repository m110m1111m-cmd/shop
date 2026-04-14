<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    die('<div style="padding: 50px; text-align: center; font-family: sans-serif;"><h1>ດຳເນີນການບໍ່ໄດ້</h1><p>ສະເພາະຜູ້ເບິ່ງແຍງລະບົບ (Admin) ເທົ່ານັ້ນ.</p><a href="pos.php">ກັບຄືນໜ້າຂາຍ</a></div>');
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສິນຄ້າ - Product Management</title>
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-nav">
        <h1><i class="fas fa-boxes"></i> ຈັດການສິນຄ້າ & ສະຕ໋ອກ</h1>
        <a href="pos.php"><i class="fas fa-cash-register"></i> ກັບຄືນໜ້າຂາຍ (POS)</a>
    </nav>

    <div class="container">
        <!-- Actions -->
        <div class="actions-card">
            <button class="btn" onclick="openAddModal()"><i class="fas fa-plus-circle"></i> ເພີ່ມສິນຄ້າໃໝ່ (Add Product)</button>
            <button class="btn btn-secondary" onclick="openReceiveModal()"><i class="fas fa-truck-loading"></i> ຮັບເຂົ້າສະຕ໋ອກ (Receive Stock)</button>
            <div style="flex-grow: 1;"></div>
            <div style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #777;"></i>
                <input type="text" id="searchInput" placeholder="ຄົ້ນຫາບາໂຄດ ຫຼື ຊື່ສິນຄ້າ..." style="padding: 10px 10px 10px 35px; border-radius: 6px; border: 1px solid #ccc; width: 250px; outline: none; font-family: inherit;" oninput="filterTable()">
            </div>
        </div>

        <!-- Products List -->
        <div class="table-card">
            <table id="products-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ຮູບພາບ</th>
                        <th>ID / ບາໂຄດ</th>
                        <th>ຊື່ສິນຄ້າ (Product Name)</th>
                        <th>ໝວດໝູ່ (Category)</th>
                        <th>ຫົວໜ່ວຍ</th>
                        <th style="text-align: right;">ລາຄາຕົ້ນທຶນ</th>
                        <th style="text-align: right;">ລາຄາຂາຍ</th>
                        <th style="text-align: right;">ຈຳນວນໃນສະຕ໋ອກ</th>
                        <th style="text-align: center;">ຈັດການ (Actions)</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    <!-- Data here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Add Product -->
    <div class="modal-overlay" id="add-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> ເພີ່ມສິນຄ້າໃໝ່</h2>
                <button class="close-btn" onclick="closeModal('add-modal')">&times;</button>
            </div>
            <div id="add-alert" class="alert-msg"></div>
            <form id="addForm">
                <div class="form-group">
                    <label>ບາໂຄດ (Barcode) - ປະຫວ່າງໄວ້ເພື່ອສ້າງອັດຕະໂນມັດ</label>
                    <input type="text" name="barcode" placeholder="ສະແກນ ຫຼື ພິມ...">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ຊື່ສິນຄ້າ (Name) *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>ໝວດໝູ່ (Category)</label>
                        <select name="category_id" id="category-select">
                            <!-- Options -->
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ຫົວໜ່ວຍ (Unit) * ເຊັ່ນ: ກ່ອງ, ແກ້ວ</label>
                        <input type="text" name="unit_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ລາຄາຕົ້ນທຶນ (Cost)</label>
                        <input type="number" name="cost_price" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>ລາຄາຂາຍ (Selling) *</label>
                        <input type="number" name="selling_price" required min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ສະຕ໋ອກເລີ່ມຕົ້ນ (Initial Stock)</label>
                        <input type="number" name="stock_qty" value="0">
                    </div>
                    <div class="form-group">
                        <label>ແຈ້ງເຕືອນເມື່ອໃກ້ໝົດ (Alert Level)</label>
                        <input type="number" name="alert_threshold" value="5">
                    </div>
                </div>
                <div class="form-group">
                    <label>ຮູບພາບສິນຄ້າ (Product Image)</label>
                    <input type="file" name="image" accept="image/*" style="border: 1px dashed #ccc; padding: 20px; text-align: center; cursor: pointer;">
                </div>
                <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 10px;"><i class="fas fa-save"></i> ບັນທຶກສິນຄ້າ</button>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Product -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> ແກ້ໄຂສິນຄ້າ</h2>
                <button class="close-btn" onclick="closeModal('edit-modal')">&times;</button>
            </div>
            <div id="edit-alert" class="alert-msg"></div>
            <form id="editForm">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label>ຊື່ສິນຄ້າ (Name) *</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ລາຄາຕົ້ນທຶນ (Cost)</label>
                        <input type="number" name="cost_price" id="edit-cost" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>ລາຄາຂາຍ (Selling) *</label>
                        <input type="number" name="selling_price" id="edit-selling" required min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>ປ່ຽນຮູບພາບສິນຄ້າ (Change image)</label>
                    <input type="file" name="image" accept="image/*" style="border: 1px dashed #ccc; padding: 10px; cursor: pointer;">
                </div>
                <button type="submit" class="btn" style="background: #ffc107; color: #000; width: 100%; justify-content: center; margin-top: 10px;"><i class="fas fa-save"></i> ບັນທຶກການແກ້ໄຂ</button>
            </form>
        </div>
    </div>

    <!-- Modal: Receive Stock -->
    <div class="modal-overlay" id="receive-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-truck-loading"></i> ຮັບຂອງເຂົ້າສະຕ໋ອກ</h2>
                <button class="close-btn" onclick="closeModal('receive-modal')">&times;</button>
            </div>
            <div id="receive-alert" class="alert-msg"></div>
            <form id="receiveForm">
                <div class="form-group">
                    <label>ເລືອກສິນຄ້າ (Product)</label>
                    <select name="product_id" id="receive-product-select" required style="width: 100%; padding: 10px; font-size: 1rem;">
                        <option value="">-- ເລືອກສິນຄ້າ --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ຈຳນວນທີ່ຮັບເຂົ້າ (Quantity to Add)</label>
                    <input type="number" name="add_qty" required min="1" value="1">
                </div>
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 10px;"><i class="fas fa-check-circle"></i> ອັບເດດສະຕ໋ອກ</button>
            </form>
        </div>
    </div>

    <script>
        let globalProducts = [];
        let lowStockEnabled = true;

        document.addEventListener('DOMContentLoaded', () => {
            loadSettings();
            loadProducts();
            loadCategories();
        });

        async function loadSettings() {
            try {
                const res = await fetch('api/settings.php?action=get_shop_info');
                const json = await res.json();
                if(json.success) {
                    lowStockEnabled = (json.data.low_stock_enabled === '1');
                }
            } catch(e) {}
        }

        async function loadProducts() {
            try {
                const res = await fetch('api/inventory.php?action=get_all_products');
                const json = await res.json();
                if(json.success) {
                    globalProducts = json.data;
                    renderTable(globalProducts);
                    populateReceiveSelect(globalProducts);
                }
            } catch(e) { console.error(e); }
        }

        async function loadCategories() {
            try {
                const res = await fetch('api/inventory.php?action=get_categories');
                const json = await res.json();
                if(json.success && json.data) {
                    const sel = document.getElementById('category-select');
                    sel.innerHTML = '';
                    json.data.forEach(c => {
                        sel.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                    });
                }
            } catch(e) {}
        }

        function renderTable(data) {
            const tbody = document.getElementById('products-tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem; color:#888;">ບໍ່ມີຂໍ້ມູນສິນຄ້າໃນລະບົບ</td></tr>';
                return;
            }

            data.forEach(p => {
                const stock = parseFloat(p.stock_qty);
                const isLow = stock <= parseFloat(p.alert_threshold || 5);
                const stockCls = (lowStockEnabled && isLow) ? 'stock-low' : 'stock-ok';
                const imgHtml = p.image_path 
                    ? `<img src="${p.image_path}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">`
                    : `<div style="width: 50px; height: 50px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #ccc;"><i class="fas fa-image"></i></div>`;

                tbody.innerHTML += `
                    <tr>
                        <td style="text-align: center;">${imgHtml}</td>
                        <td>
                            <span style="font-size:0.8rem; color:#888;">ID: ${p.id}</span><br>
                            <strong>${p.barcode}</strong>
                        </td>
                        <td>${p.name}</td>
                        <td>${p.category_name || '-'}</td>
                        <td>${p.unit_name}</td>
                        <td style="text-align: right;">${parseFloat(p.avg_cost_price).toLocaleString()} ₭</td>
                        <td style="text-align: right; font-weight:bold; color:var(--primary);">${parseFloat(p.selling_price).toLocaleString()} ₭</td>
                        <td style="text-align: right;" class="${stockCls}">${stock.toLocaleString()}</td>
                        <td style="text-align: center;">
                            <button class="btn" style="padding: 5px 10px; font-size: 0.8rem; background: #ffc107; color: #000;" onclick="openEditModal(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.avg_cost_price}, ${p.selling_price})"><i class="fas fa-edit"></i> ແກ້ໄຂ</button>
                            <button class="btn" style="padding: 5px 10px; font-size: 0.8rem; background: #dc3545; margin-left: 5px;" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i> ລຶບ</button>
                        </td>
                    </tr>
                `;
            });
        }

        function filterTable() {
            const v = document.getElementById('searchInput').value.toLowerCase();
            const filtered = globalProducts.filter(p => 
                p.name.toLowerCase().includes(v) || p.barcode.includes(v)
            );
            renderTable(filtered);
        }

        function populateReceiveSelect(data) {
            const sel = document.getElementById('receive-product-select');
            sel.innerHTML = '<option value="">-- ເລືອກສິນຄ້າ --</option>';
            data.forEach(p => {
                sel.innerHTML += `<option value="${p.id}">[${p.barcode}] ${p.name} (ໃນສະຕ໋ອກ: ${parseFloat(p.stock_qty)})</option>`;
            });
        }

        // Modals
        function openAddModal() {
            document.getElementById('add-alert').style.display = 'none';
            document.getElementById('addForm').reset();
            document.getElementById('add-modal').classList.add('show');
        }

        function openReceiveModal() {
            document.getElementById('receive-alert').style.display = 'none';
            document.getElementById('receiveForm').reset();
            document.getElementById('receive-modal').classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        function showAlert(id, msg, isSuccess) {
            const el = document.getElementById(id);
            el.className = 'alert-msg ' + (isSuccess ? 'alert-success' : 'alert-danger');
            el.innerText = msg;
            el.style.display = 'block';
        }

        // Forms
        document.getElementById('addForm').onsubmit = async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'add_product');
            
            const res = await fetch('api/inventory.php', { method: 'POST', body: fd });
            const json = await res.json();
            
            if(json.success) {
                showAlert('add-alert', 'ເພີ່ມສິນຄ້າເຂົ້າລະບົບສຳເລັດແລ້ວ!', true);
                loadProducts(); // refresh table
                setTimeout(() => closeModal('add-modal'), 1200);
            } else {
                showAlert('add-alert', json.message, false);
            }
        };

        document.getElementById('receiveForm').onsubmit = async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'receive_stock');
            
            const res = await fetch('api/inventory.php', { method: 'POST', body: fd });
            const json = await res.json();
            
            if(json.success) {
                showAlert('receive-alert', 'ອັບເດດສະຕ໋ອກສຳເລັດແລ້ວ!', true);
                loadProducts(); // refresh table
                setTimeout(() => closeModal('receive-modal'), 1200);
            } else {
                showAlert('receive-alert', json.message, false);
            }
        };

        // Edit Modal
        function openEditModal(id, name, cost, selling) {
            document.getElementById('edit-alert').style.display = 'none';
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-cost').value = cost;
            document.getElementById('edit-selling').value = selling;
            document.getElementById('edit-modal').classList.add('show');
        }

        document.getElementById('editForm').onsubmit = async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'update_product');
            
            const res = await fetch('api/inventory.php', { method: 'POST', body: fd });
            const json = await res.json();
            
            if(json.success) {
                showAlert('edit-alert', json.message, true);
                loadProducts(); 
                setTimeout(() => closeModal('edit-modal'), 1200);
            } else {
                showAlert('edit-alert', json.message, false);
            }
        };

        async function deleteProduct(id) {
            if (!confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການຍົກເລີກ/ລຶບສິນຄ້ານີ້?\nຖ້າສິນຄ້ານີ້ເຄີຍຖືກຂາຍໄປແລ້ວ ຈະບໍ່ສາມາດລຶບໄດ້!')) return;
            
            const fd = new FormData();
            fd.append('action', 'delete_product');
            fd.append('id', id);

            try {
                const res = await fetch('api/inventory.php', { method: 'POST', body: fd });
                const json = await res.json();
                
                if (json.success) {
                    alert('ລຶບສິນຄ້າສຳເລັດແລ້ວ!');
                    loadProducts();
                } else {
                    alert(json.message);
                }
            } catch (err) {
                alert('ເກີດຂໍ້ຜິດພາດໃນການລຶບສິນຄ້າ');
            }
        }
    </script>
</body>
</html>
