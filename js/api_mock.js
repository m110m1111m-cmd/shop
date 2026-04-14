// js/api_mock.js
// This file replaces the PHP backend by using browser localStorage

const STORAGE_KEYS = {
    PRODUCTS: 'pos_products',
    CATEGORIES: 'pos_categories',
    SETTINGS: 'pos_settings',
    SALES: 'pos_sales',
    USER: 'pos_current_user'
};

// Initial Data setup
const DEFAULT_CATEGORIES = [
    { id: 1, name: 'ອາຫານ (Food)' },
    { id: 2, name: 'ເຄື່ອງດື່ມ (Drinks)' },
    { id: 3, name: 'ເຄື່ອງໃຊ້ (Supplies)' }
];

const DEFAULT_PRODUCTS = [
    { id: 1, barcode: '1001', name: 'Pepsi', category_id: 2, unit_name: 'Cup', selling_price: 15000, image_path: 'https://images.unsplash.com/photo-1629203851022-3cd26ab1b296?auto=format&fit=crop&q=80&w=150' },
    { id: 2, barcode: '1002', name: 'Burger', category_id: 1, unit_name: 'Set', selling_price: 35000, image_path: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=150' },
    { id: 3, barcode: '1003', name: 'Coffee', category_id: 2, unit_name: 'Cup', selling_price: 25000, image_path: 'https://images.unsplash.com/photo-1541167760496-162955ed8a9f?auto=format&fit=crop&q=80&w=150' }
];

const DEFAULT_SETTINGS = {
    vat_rate: 7,
    vat_type: 'exclusive',
    currency_symbol: '₭',
    decimal_places: 0,
    bank_name: 'BCEL',
    bank_account_name: 'SHOP OWNER',
    bank_account_number: '1601200012345678',
    qr_code: 'https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg',
    open_time: '08:00',
    close_time: '20:00',
    auto_close_enabled: '0'
};

// --- Helper functions ---
const getStorage = (key, defaultVal) => {
    const data = localStorage.getItem(key);
    return data ? JSON.parse(data) : defaultVal;
};

const setStorage = (key, val) => {
    localStorage.setItem(key, JSON.stringify(val));
};

const DEFAULT_STAFF = [
    { id: 1, full_name: 'ແອັດມິນ', username: 'admin', role: 'admin', phone: '020 1234 5678', salary: 5000000, join_date: '2023-01-01', is_active: 1 }
];

// Initialize system
if (!localStorage.getItem(STORAGE_KEYS.PRODUCTS)) setStorage(STORAGE_KEYS.PRODUCTS, DEFAULT_PRODUCTS);
if (!localStorage.getItem(STORAGE_KEYS.CATEGORIES)) setStorage(STORAGE_KEYS.CATEGORIES, DEFAULT_CATEGORIES);
if (!localStorage.getItem(STORAGE_KEYS.SETTINGS)) setStorage(STORAGE_KEYS.SETTINGS, DEFAULT_SETTINGS);
if (!localStorage.getItem('pos_staff')) setStorage('pos_staff', DEFAULT_STAFF);

// --- API Mock Implementation ---
window.APIMock = {
    // Inventory
    getCategories: async () => ({ success: true, data: getStorage(STORAGE_KEYS.CATEGORIES, []) }),
    getProducts: async () => ({ success: true, data: getStorage(STORAGE_KEYS.PRODUCTS, []) }),
    getProductByBarcode: async (barcode) => {
        const products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        const product = products.find(p => p.barcode === barcode);
        return product ? { success: true, data: product } : { success: false, message: 'Product not found' };
    },
    searchProducts: async (query) => {
        const products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        const results = products.filter(p => 
            p.name.toLowerCase().includes(query.toLowerCase()) || 
            p.barcode.includes(query)
        ).slice(0, 10);
        return { success: true, data: results };
    },
    
    // Inventory Mutations
    addProduct: async (data) => {
        const products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        const newProduct = {
            id: Date.now(),
            barcode: data.barcode || (Date.now() + Math.floor(Math.random() * 1000)).toString(),
            name: data.name,
            category_id: data.category_id,
            unit_name: data.unit_name,
            selling_price: parseFloat(data.selling_price) || 0,
            avg_cost_price: parseFloat(data.cost_price) || 0,
            stock_qty: parseFloat(data.stock_qty) || 0,
            alert_threshold: parseFloat(data.alert_threshold) || 5,
            image_path: data.image_path || 'https://via.placeholder.com/150?text=Product'
        };
        products.push(newProduct);
        setStorage(STORAGE_KEYS.PRODUCTS, products);
        return { success: true, message: 'Product added successfully' };
    },
    updateProduct: async (id, data) => {
        const products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        const index = products.findIndex(p => p.id == id);
        if (index > -1) {
            products[index] = { ...products[index], ...data };
            setStorage(STORAGE_KEYS.PRODUCTS, products);
            return { success: true, message: 'ອັບເດດສິນຄ້າສຳເລັດແລ້ວ!' };
        }
        return { success: false, message: 'Product not found' };
    },
    deleteProduct: async (id) => {
        let products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        products = products.filter(p => p.id != id);
        setStorage(STORAGE_KEYS.PRODUCTS, products);
        return { success: true, message: 'ລຶບສິນຄ້າສຳເລັດແລ້ວ!' };
    },
    receiveStock: async (product_id, add_qty) => {
        const products = getStorage(STORAGE_KEYS.PRODUCTS, []);
        const index = products.findIndex(p => p.id == product_id);
        if (index > -1) {
            products[index].stock_qty = (parseFloat(products[index].stock_qty) || 0) + parseFloat(add_qty);
            setStorage(STORAGE_KEYS.PRODUCTS, products);
            return { success: true, message: 'ອັບເດດສະຕ໋ອກສຳເລັດແລ້ວ!' };
        }
        return { success: false, message: 'Product not found' };
    },

    // Settings
    getSettings: async () => ({ success: true, data: getStorage(STORAGE_KEYS.SETTINGS, DEFAULT_SETTINGS) }),
    updateSettings: async (data) => {
        const settings = getStorage(STORAGE_KEYS.SETTINGS, DEFAULT_SETTINGS);
        const updated = { ...settings, ...data };
        setStorage(STORAGE_KEYS.SETTINGS, updated);
        return { success: true, message: 'ບັນທຶກການຕັ້ງຄ່າສຳເລັດແລ້ວ!' };
    },
    
    // POS / Checkout
    checkout: async (payload) => {
        const sales = getStorage(STORAGE_KEYS.SALES, []);
        const newSale = {
            id: Date.now(),
            date: new Date().toISOString(),
            ...payload
        };
        sales.push(newSale);
        setStorage(STORAGE_KEYS.SALES, sales);
        return { success: true, message: 'Payment successful!' };
    },

    getSales: async (filters = {}) => {
        let sales = getStorage(STORAGE_KEYS.SALES, []);
        
        if (filters.start_date) {
            const startD = new Date(filters.start_date);
            startD.setHours(0, 0, 0, 0);
            sales = sales.filter(s => {
                if (!s.date) return false;
                return new Date(s.date) >= startD;
            });
        }
        if (filters.end_date) {
            const endD = new Date(filters.end_date);
            endD.setHours(23, 59, 59, 999);
            sales = sales.filter(s => {
                if (!s.date) return false;
                return new Date(s.date) <= endD;
            });
        }
        
        // Reverse to show latest first
        return { success: true, data: [...sales].reverse() };
    },
    getSaleById: async (id) => {
        const sales = getStorage(STORAGE_KEYS.SALES, []);
        const sale = sales.find(s => s.id == id);
        return sale ? { success: true, data: sale } : { success: false, message: 'Sale not found' };
    },
    updateSale: async (id, data) => {
        const sales = getStorage(STORAGE_KEYS.SALES, []);
        const index = sales.findIndex(s => s.id == id);
        if (index > -1) {
            sales[index] = { ...sales[index], ...data };
            setStorage(STORAGE_KEYS.SALES, sales);
            return { success: true, message: 'ອັບເດດບິນສຳເລັດແລ້ວ!' };
        }
        return { success: false, message: 'Sale not found' };
    },
    deleteSale: async (id) => {
        let sales = getStorage(STORAGE_KEYS.SALES, []);
        sales = sales.filter(s => s.id != id);
        setStorage(STORAGE_KEYS.SALES, sales);
        return { success: true, message: 'ລຶບບິນສຳເລັດແລ້ວ!' };
    },

    // Staff Management
    getStaffList: async () => ({ success: true, data: getStorage('pos_staff', []) }),
    saveStaff: async (data) => {
        let staff = getStorage('pos_staff', []);
        if (data.id) {
            const index = staff.findIndex(s => s.id == data.id);
            if (index > -1) {
                staff[index] = { ...staff[index], ...data };
            }
        } else {
            const newStaff = {
                id: Date.now(),
                ...data,
                is_active: data.is_active || 1
            };
            staff.push(newStaff);
        }
        setStorage('pos_staff', staff);
        return { success: true, message: 'ບັນທຶກຂໍ້ມູນພະນັກງານສຳເລັດ!' };
    },
    deleteStaff: async (id) => {
        let staff = getStorage('pos_staff', []);
        staff = staff.filter(s => s.id != id);
        setStorage('pos_staff', staff);
        return { success: true, message: 'ລຶບຂໍ້ມູນພະນັກງານສຳເລັດ!' };
    },

    // Auth
    login: async (username, password) => {
        let staff = getStorage('pos_staff', []);
        let user = staff.find(s => s.username === username);
        
        // Failsafe for locked out admin
        if (!user && username === 'admin' && password === 'admin') {
            user = DEFAULT_STAFF[0];
            staff.push(user);
            setStorage('pos_staff', staff);
        }

        if (!user) {
            return { success: false, message: 'ບໍ່ພົບຊື່ຜູ້ໃຊ້ໃນລະບົບ!' };
        }
        if (user.is_active == 0 || user.is_active === '0') {
            return { success: false, message: 'ບັນຊີຂອງທ່ານຖືກລະງັບການນຳໃຊ້!' };
        }
        
        // Mock password check
        const storedPassword = user.password || 'admin'; // default password for seeded admin
        if (storedPassword !== password) {
            // Additional failsafe for admin password reset
            if (username === 'admin' && password === 'admin') {
                user.password = 'admin';
                const index = staff.findIndex(s => s.id === user.id);
                if (index > -1) staff[index] = user;
                setStorage('pos_staff', staff);
            } else {
                return { success: false, message: 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ!' };
            }
        }

        setStorage(STORAGE_KEYS.USER, { 
            id: user.id,
            username: user.username, 
            name: user.full_name,
            role: user.role 
        });
        return { success: true };
    },
    getCurrentUser: () => getStorage(STORAGE_KEYS.USER, null),
    logout: () => {
        localStorage.removeItem(STORAGE_KEYS.USER);
        window.location.href = 'index.html';
    }
};
