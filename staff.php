<?php
// staff.php
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
    <title>ຈັດການພະນັກງານ - Staff Management</title>
    <link rel="stylesheet" href="css/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-nav">
        <h1><i class="fas fa-users"></i> ລະບົບຈັດການພະນັກງານ</h1>
        <div style="display: flex; gap: 1rem;">
            <a href="settings.php"><i class="fas fa-cog"></i> ຕັ້ງຄ່າຮ້ານ</a>
            <a href="pos.php"><i class="fas fa-cash-register"></i> ກັບຄືນໜ້າຂາຍ</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-actions">
            <div>
                <h2 style="font-size: 1.5rem; color: var(--text);">ລາຍຊື່ພະນັກງານທັງໝົດ</h2>
                <p style="color: var(--text-light); font-size: 0.9rem;">ຈັດການຂໍ້ມູນສ່ວນຕົວ ແລະ ສິດການເຂົ້າໃຊ້ລະບົບ</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i> ເພີ່ມພະນັກງານໃໝ່
            </button>
        </div>

        <div id="staffGrid" class="staff-grid">
            <!-- Staff cards will be loaded here -->
        </div>
    </div>

    <!-- Staff Modal -->
    <div id="staffModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 2rem;">ເພີ່ມຂໍ້ມູນພະນັກງານ</h2>
            <form id="staffForm">
                <input type="hidden" name="id" id="staffId">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>ຊື່ ແລະ ນາມສະກຸນ *</label>
                        <input type="text" name="full_name" id="fullName" placeholder="ເຊັ່ນ: ສົມພອນ ດີດີ" required>
                    </div>
                    <div class="form-group">
                        <label>ຊື່ຜູ້ໃຊ້ (Username) *</label>
                        <input type="text" name="username" id="username" placeholder="ສຳລັບເຂົ້າລະບົບ" required>
                    </div>
                    <div class="form-group">
                        <label>ລະຫັດຜ່ານ (Password) <span id="pwHint" style="color: var(--danger); font-size: 0.7rem; display: none;">(ປະຫວ່າງໄວ້ຖ້າບໍ່ປ່ຽນ)</span></label>
                        <input type="password" name="password" id="password" placeholder="ລະຫັດຜ່ານ">
                    </div>
                    <div class="form-group">
                        <label>ເບີໂທລະສັບ</label>
                        <input type="text" name="phone" id="phone" placeholder="020...">
                    </div>
                    <div class="form-group">
                        <label>ບົດບາດ (Role)</label>
                        <select name="role" id="role">
                            <option value="cashier">ພະນັກງານຂາຍ (Cashier)</option>
                            <option value="admin">ແອັດມິນ (Administrator)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ເງິນເດືອນ (Salary)</label>
                        <input type="number" name="salary" id="salary" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>ວັນທີເລີ່ມວຽກ (Join Date)</label>
                        <input type="date" name="join_date" id="joinDate">
                    </div>
                    <div class="form-group full">
                        <label>ທີ່ຢູ່</label>
                        <textarea name="address" id="address" rows="2" placeholder="ບ້ານ, ເມືອງ, ແຂວງ..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>ສະຖານະພະນັກງານ</label>
                        <select name="is_active" id="isActive">
                            <option value="1">ເປີດໃຊ້ງານ (Active)</option>
                            <option value="0">ປິດໃຊ້ງານ (Inactive)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                    <button type="button" class="btn" style="background: #e2e8f0;" onclick="closeModal()">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">ບັນທຶກຂໍ້ມູນ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="alertBox" class="alert"></div>

    <script>
        document.addEventListener('DOMContentLoaded', loadStaff);

        async function loadStaff() {
            try {
                const res = await fetch('api/staff.php?action=get_staff_list');
                const json = await res.json();
                if (json.success) {
                    renderStaff(json.data);
                }
            } catch (e) { console.error(e); }
        }

        function renderStaff(data) {
            const grid = document.getElementById('staffGrid');
            grid.innerHTML = '';
            
            data.forEach(s => {
                const badge = s.role === 'admin' ? 'badge-admin' : 'badge-cashier';
                const statusIcon = s.is_active == 1 ? 'fa-check-circle' : 'fa-times-circle';
                const statusColor = s.is_active == 1 ? 'var(--success)' : 'var(--danger)';
                
                const card = document.createElement('div');
                card.className = 'staff-card';
                card.innerHTML = `
                    <div style="position: absolute; top: 15px; right: 15px; color: ${statusColor}; font-size: 1.2rem;">
                        <i class="fas ${statusIcon}"></i>
                    </div>
                    <div class="staff-header">
                        <div class="staff-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="staff-info">
                            <h3>${s.full_name || 'ບໍ່ມີຊື່'}</h3>
                            <span class="badge ${badge}">${s.role.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="staff-details">
                        <div class="detail-item">
                            <label>ຊື່ຜູ້ໃຊ້</label>
                            <p>${s.username}</p>
                        </div>
                        <div class="detail-item">
                            <label>ເບີໂທລະສັບ</label>
                            <p>${s.phone || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label>ເງິນເດືອນ</label>
                            <p>${formatCurrency(s.salary)} ₭</p>
                        </div>
                        <div class="detail-item">
                            <label>ວັນທີເລີ່ມວຽກ</label>
                            <p>${s.join_date || '-'}</p>
                        </div>
                    </div>
                    <div class="staff-actions">
                        <button class="btn btn-edit" onclick='openModal(${JSON.stringify(s)})'>
                            <i class="fas fa-edit"></i> ແກ້ໄຂ
                        </button>
                        <button class="btn btn-delete" onclick="deleteStaff(${s.id})">
                            <i class="fas fa-trash"></i> ລຶບ
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function openModal(staff = null) {
            const form = document.getElementById('staffForm');
            form.reset();
            document.getElementById('staffId').value = staff ? staff.id : '';
            document.getElementById('fullName').value = staff ? staff.full_name : '';
            document.getElementById('username').value = staff ? staff.username : '';
            document.getElementById('phone').value = staff ? staff.phone : '';
            document.getElementById('address').value = staff ? staff.address : '';
            document.getElementById('salary').value = staff ? staff.salary : 0;
            document.getElementById('joinDate').value = staff ? staff.join_date : '';
            document.getElementById('role').value = staff ? staff.role : 'cashier';
            document.getElementById('isActive').value = staff ? staff.is_active : 1;
            
            document.getElementById('modalTitle').innerText = staff ? 'ແກ້ໄຂຂໍ້ມູນພະນັກງານ' : 'ເພີ່ມຂໍ້ມູນພະນັກງານ';
            document.getElementById('password').required = !staff;
            document.getElementById('pwHint').style.display = staff ? 'inline' : 'none';
            
            document.getElementById('staffModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('staffModal').style.display = 'none';
        }

        document.getElementById('staffForm').onsubmit = async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'save_staff');

            try {
                const res = await fetch('api/staff.php', { method: 'POST', body: fd });
                const json = await res.json();
                showAlert(json.success ? 'success' : 'danger', json.message);
                if (json.success) {
                    closeModal();
                    loadStaff();
                }
            } catch (e) { showAlert('danger', 'Error processing request'); }
        };

        async function deleteStaff(id) {
            if (!confirm('ຢືນຢັນການລຶບພະນັກງານຜູ້ນີ້? (ຂໍ້ມູນຈະຖຶກລຶບຖາວອນ)')) return;
            const fd = new FormData();
            fd.append('action', 'delete_staff');
            fd.append('id', id);

            try {
                const res = await fetch('api/staff.php', { method: 'POST', body: fd });
                const json = await res.json();
                showAlert(json.success ? 'success' : 'danger', json.message);
                if (json.success) loadStaff();
            } catch (e) { showAlert('danger', 'Error deleting staff'); }
        }

        function showAlert(type, msg) {
            const el = document.getElementById('alertBox');
            el.className = 'alert ' + (type === 'success' ? 'btn-primary' : 'btn-delete');
            el.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)'; 
            el.style.color = 'white';
            el.innerText = msg;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 3000);
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('lo-LA').format(amount);
        }
    </script>
</body>
</html>
