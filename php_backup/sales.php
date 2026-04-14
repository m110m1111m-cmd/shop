<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Optional: restrict to admin
if ($_SESSION['role'] !== 'admin') {
    die('<div style="padding: 50px; text-align: center; font-family: sans-serif;"><h1>ດຳເນີນການບໍ່ໄດ້</h1><p>ສະເພາະຜູ້ເບິ່ງແຍງລະບົບ (Admin) ເທົ່ານັ້ນທີ່ສາມາດເຂົ້າເບິ່ງໜ້ານີ້ໄດ້.</p><a href="pos.php">ກັບຄືນໜ້າຂາຍ</a></div>');
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ປະຫວັດການຂາຍ - Sales Management</title>
    <link rel="stylesheet" href="css/sales.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="top-nav">
        <h1><i class="fas fa-chart-line"></i> ປະຫວັດການຂາຍລວມ</h1>
        <a href="pos.php"><i class="fas fa-cash-register"></i> ກັບຄືນໜ້າຂາຍ (POS)</a>
    </nav>

    <div class="container">
        <!-- Filters -->
        <div class="filter-card">
            <div class="input-group">
                <label>ຕັ້ງແຕ່ວັນທີ</label>
                <input type="date" id="start-date">
            </div>
            <div class="input-group">
                <label>ເຖິງວັນທີ</label>
                <input type="date" id="end-date">
            </div>
            <button class="btn" onclick="fetchSales()"><i class="fas fa-search"></i> ຄົ້ນຫາ</button>
            <button class="btn" style="background: #e0e0e0; color: #333; margin-left: 10px;" onclick="resetFilters()"><i class="fas fa-redo"></i> ລີເຊັດ</button>
        </div>

        <!-- Sales List -->
        <div class="sales-card">
            <table id="sales-table">
                <thead>
                    <tr>
                        <th>ລະຫັດບິນ (Bill ID)</th>
                        <th>ວັນເວລາ (Time)</th>
                        <th>ພະນັກງານຂາຍ (Cashier)</th>
                        <th>ວິທີຊຳລະ (Payment)</th>
                        <th style="text-align: right;">ຍອດລວມ (Total)</th>
                    </tr>
                </thead>
                <tbody id="sales-tbody">
                    <!-- Data will be loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Bill Details -->
    <div class="modal-overlay" id="details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>ລາຍລະອຽດໃບບິນ: <span id="modal-bill-id"></span></h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="details-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ສິນຄ້າ (Product)</th>
                            <th style="text-align: center;">ຈຳນວນ (Qty)</th>
                            <th style="text-align: right;">ລາຄາ (Price)</th>
                            <th style="text-align: right;">ລວມ (Subtotal)</th>
                        </tr>
                    </thead>
                    <tbody id="details-tbody">
                        <!-- Details loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetchSales();
        });

        async function fetchSales() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            const tbody = document.getElementById('sales-tbody');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> ກຳລັງໂຫຼດຂໍ້ມູນ...</td></tr>';

            try {
                let url = `api/get_sales.php?start_date=${startDate}&end_date=${endDate}`;
                const response = await fetch(url);
                const res = await response.json();

                if (res.success) {
                    renderSales(res.data);
                } else {
                    throw new Error(res.error || 'Failed to load sales');
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color: red;">${err.message}</td></tr>`;
            }
        }

        function renderSales(data) {
            const tbody = document.getElementById('sales-tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color: #777;">ບໍ່ມີຂໍ້ມູນການຂາຍ</td></tr>';
                return;
            }

            data.forEach(sale => {
                const tr = document.createElement('tr');
                tr.className = 'sale-row';
                tr.onclick = () => openDetails(sale.id);

                const paymentBadge = sale.payment_method === 'cash' 
                    ? '<span class="badge cash">ເງິນສົດ (Cash)</span>' 
                    : '<span class="badge transfer">ເງິນໂອນ (Transfer)</span>';

                tr.innerHTML = `
                    <td>#${sale.id.toString().padStart(6, '0')}</td>
                    <td>${sale.sale_time}</td>
                    <td><i class="fas fa-user-circle"></i> ${sale.cashier_name || 'N/A'}</td>
                    <td>${paymentBadge}</td>
                    <td style="text-align: right; font-weight: 600;">${parseFloat(sale.total_amount).toLocaleString()} ₭</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function resetFilters() {
            document.getElementById('start-date').value = '';
            document.getElementById('end-date').value = '';
            fetchSales();
        }

        async function openDetails(saleId) {
            const modal = document.getElementById('details-modal');
            const tbody = document.getElementById('details-tbody');
            document.getElementById('modal-bill-id').innerText = '#' + saleId.toString().padStart(6, '0');
            
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> ໂຫຼດຂໍ້ມູນ...</td></tr>';
            
            modal.classList.add('show');

            try {
                const response = await fetch(`api/get_sale_details.php?sale_id=${saleId}`);
                const res = await response.json();

                if (res.success) {
                    tbody.innerHTML = '';
                    let grandTotal = 0;
                    res.data.forEach(item => {
                        const sub = parseFloat(item.subtotal);
                        grandTotal += sub;
                        tbody.innerHTML += `
                            <tr>
                                <td>${item.product_name}</td>
                                <td style="text-align: center;">${parseFloat(item.qty)} ${item.unit_name}</td>
                                <td style="text-align: right;">${parseFloat(item.price).toLocaleString()} ₭</td>
                                <td style="text-align: right; font-weight: 500;">${sub.toLocaleString()} ₭</td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML += `
                        <tr>
                            <td colspan="3" class="total-row">ຍອດລວມທັງໝົດ:</td>
                            <td class="total-row" style="color: var(--primary);">${grandTotal.toLocaleString()} ₭</td>
                        </tr>
                    `;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color: red;">ຜິດພາດໃນການໂຫຼດຂໍ້ມູນ</td></tr>`;
            }
        }

        function closeModal() {
            document.getElementById('details-modal').classList.remove('show');
        }

        document.getElementById('details-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
