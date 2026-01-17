<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destello de Oro 18K | Sistema de Gestión</title>
    
    <!-- Estilos CSS (mantén tu CSS actual) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- QR Code Generator Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <!-- Añadir jsPDF y html2canvas para generar PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <!-- Icono para la ventana del navegador -->
    <link rel="icon" type="image/x-icon" href="https://cdn-icons-png.flaticon.com/512/3135/3135715.png">
    
    <!-- Tu CSS actual aquí (mantén todo tu CSS) -->
    <style>
        /* TODO: PEGA AQUÍ TODO TU CSS ORIGINAL COMPLETO */
        :root {
            --gold-primary: #D4AF37;
            /* ... resto de tu CSS ... */
        }
        
        /* ... TODO TU CSS COMPLETO ... */
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Pantalla de Login -->
        <div id="loginScreen" class="login-container">
            <!-- TODO: PEGA AQUÍ TODO EL HTML DE LOGIN ORIGINAL -->
        </div>
    <?php else: ?>
        <!-- Aplicación principal -->
        <div id="appScreen">
            <!-- TODO: PEGA AQUÍ TODO EL HTML DE LA APLICACIÓN ORIGINAL -->
        </div>
    <?php endif; ?>
    
    <!-- Modal para cambio de contraseña -->
    <div id="passwordChangeModal" class="password-change-container">
        <!-- TODO: PEGA AQUÍ EL HTML DEL MODAL DE CAMBIO DE CONTRASEÑA -->
    </div>
    
    <!-- Modal para olvidé mi contraseña -->
    <div id="forgotPasswordModal" class="forgot-password-container">
        <!-- TODO: PEGA AQUÍ EL HTML DEL MODAL DE OLVIDÉ CONTRASEÑA -->
    </div>
    
    <!-- Modal de factura -->
    <div id="invoiceModal" class="invoice-modal">
        <!-- TODO: PEGA AQUÍ EL HTML DEL MODAL DE FACTURA -->
    </div>
    
    <!-- Diálogo personalizado -->
    <div id="customDialog" class="custom-dialog">
        <!-- TODO: PEGA AQUÍ EL HTML DEL DIÁLOGO PERSONALIZADO -->
    </div>

    <script>
        // ==============================
        // CONFIGURACIÓN DE LA API
        // ==============================
        const API_BASE_URL = '../backend/api';
        
        // ==============================
        // VARIABLES GLOBALES
        // ==============================
        let currentUser = <?php 
            echo isset($_SESSION['user_id']) ? json_encode([
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ]) : 'null'; 
        ?>;
        
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let selectedRole = 'admin';
        let saleProducts = [];
        
        // Métodos de pago
        const paymentMethods = {
            'transfer': { name: 'Transferencia', class: 'payment-transfer' },
            'cash': { name: 'Efectivo', class: 'payment-cash' },
            'bold': { name: 'Bold', class: 'payment-bold' },
            'addi': { name: 'Addi', class: 'payment-addi' },
            'sistecredito': { name: 'Sistecrédito', class: 'payment-sistecredito' },
            'cod': { name: 'Contra Entrega', class: 'payment-cod' },
            'card': { name: 'Tarjeta', class: 'payment-card' },
            'nequi': { name: 'Nequi/Daviplata', class: 'payment-nequi' }
        };
        
        // URLs de redes sociales
        const socialMediaUrls = {
            instagram: 'https://www.instagram.com/destellodeoro18k',
            whatsapp: 'https://wa.me/message/YUXMM6PDVXEGN1',
            website: 'https://destellodeoro18k.com'
        };
        
        const adminWhatsApp = '3182687488';
        
        // ==============================
        // FUNCIONES DE API
        // ==============================
        
        /**
         * Función genérica para llamadas a la API
         */
        async function apiCall(endpoint, method = 'GET', data = null) {
            const url = `${API_BASE_URL}/${endpoint}`;
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(url, options);
                const result = await response.json();
                
                if (!response.ok) {
                    if (response.status === 401) {
                        // Sesión expirada
                        await showDialog('Sesión Expirada', 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.', 'error');
                        window.location.reload();
                        return null;
                    }
                    throw new Error(result.message || 'Error en la solicitud');
                }
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                await showDialog('Error', error.message || 'Error de conexión con el servidor', 'error');
                return null;
            }
        }
        
        /**
         * Login con PHP/MySQL
         */
        async function loginWithPHP(username, password, role) {
            const result = await apiCall('auth/login.php', 'POST', {
                username: username,
                password: password,
                role: role
            });
            
            if (result && result.status === 'success') {
                currentUser = result.data.user;
                await showDialog('¡Bienvenido!', `Bienvenido ${currentUser.name}`, 'success');
                window.location.reload();
            } else {
                await showDialog('Error de Acceso', 'Credenciales incorrectas', 'error');
            }
        }
        
        /**
         * Logout
         */
        async function logout() {
            const confirmed = await showDialog(
                'Cerrar Sesión',
                '¿Está seguro de que desea cerrar sesión?',
                'question',
                true
            );
            
            if (confirmed) {
                await apiCall('auth/logout.php', 'POST');
                window.location.reload();
            }
        }
        
        /**
         * Obtener productos desde MySQL
         */
        async function loadProducts() {
            const result = await apiCall('products/get_all.php');
            return result?.data || [];
        }
        
        /**
         * Agregar producto a MySQL
         */
        async function addProduct(productData) {
            const result = await apiCall('products/add.php', 'POST', productData);
            
            if (result && result.status === 'success') {
                await showDialog('Éxito', 'Producto agregado exitosamente', 'success');
                return true;
            }
            return false;
        }
        
        /**
         * Eliminar producto
         */
        async function deleteProductAPI(productId) {
            const result = await apiCall(`products/delete.php?id=${productId}`, 'DELETE');
            return result?.status === 'success';
        }
        
        /**
         * Registrar venta
         */
        async function registerSale(saleData) {
            const result = await apiCall('sales/add.php', 'POST', saleData);
            return result;
        }
        
        /**
         * Obtener ventas pendientes
         */
        async function loadPendingSales() {
            const result = await apiCall('sales/get_all.php?status=pending');
            return result?.data || [];
        }
        
        /**
         * Confirmar pago de venta pendiente
         */
        async function confirmPaymentAPI(saleId) {
            const result = await apiCall('sales/confirm.php', 'POST', { sale_id: saleId });
            return result?.status === 'success';
        }
        
        /**
         * Cancelar venta pendiente
         */
        async function cancelPendingSaleAPI(saleId) {
            const result = await apiCall(`sales/delete.php?id=${saleId}`, 'DELETE');
            return result?.status === 'success';
        }
        
        /**
         * Registrar garantía
         */
        async function registerWarranty(warrantyData) {
            const result = await apiCall('warranties/add.php', 'POST', warrantyData);
            return result?.status === 'success';
        }
        
        /**
         * Obtener garantías
         */
        async function loadWarranties() {
            const result = await apiCall('warranties/get_all.php');
            return result?.data || [];
        }
        
        /**
         * Eliminar garantía
         */
        async function deleteWarrantyAPI(warrantyId) {
            const result = await apiCall(`warranties/delete.php?id=${warrantyId}`, 'DELETE');
            return result?.status === 'success';
        }
        
        /**
         * Registrar gasto
         */
        async function registerExpense(expenseData) {
            const result = await apiCall('expenses/add.php', 'POST', expenseData);
            return result?.status === 'success';
        }
        
        /**
         * Obtener gastos
         */
        async function loadExpenses() {
            const result = await apiCall('expenses/get_all.php');
            return result?.data || [];
        }
        
        /**
         * Eliminar gasto
         */
        async function deleteExpenseAPI(expenseId) {
            const result = await apiCall(`expenses/delete.php?id=${expenseId}`, 'DELETE');
            return result?.status === 'success';
        }
        
        /**
         * Surtir inventario
         */
        async function restockProduct(productId, quantity) {
            const result = await apiCall('restock/add.php', 'POST', {
                product_id: productId,
                quantity: quantity
            });
            return result?.status === 'success';
        }
        
        /**
         * Obtener historial mensual
         */
        async function loadMonthlyHistory(month, year) {
            const result = await apiCall(`history/get_monthly.php?month=${month}&year=${year}`);
            return result?.data || { stats: {}, transactions: [] };
        }
        
        /**
         * Obtener usuarios
         */
        async function loadUsers() {
            const result = await apiCall('users/get_all.php');
            return result?.data || [];
        }
        
        /**
         * Cambiar contraseña
         */
        async function changePasswordAPI(adminUsername, adminPassword, userToChange, newPassword) {
            const result = await apiCall('auth/change_password.php', 'POST', {
                admin_username: adminUsername,
                admin_password: adminPassword,
                user_to_change: userToChange,
                new_password: newPassword
            });
            return result?.status === 'success';
        }
        
        /**
         * Olvidé contraseña
         */
        async function forgotPasswordAPI(adminUsername, adminPassword, userToReset, method) {
            const result = await apiCall('auth/forgot_password.php', 'POST', {
                admin_username: adminUsername,
                admin_password: adminPassword,
                user_to_reset: userToReset,
                method: method
            });
            return result;
        }
        
        // ==============================
        // FUNCIONES DE LA APLICACIÓN
        // ==============================
        
        // Inicializar pasos del login
        function initLoginSteps() {
            // Mostrar solo el primer paso (selección de rol)
            showLoginStep('roleSelection');
        }
        
        // Mostrar paso específico del login
        function showLoginStep(stepId) {
            // Ocultar todos los pasos
            document.getElementById('roleSelection').style.display = 'none';
            document.getElementById('userInfoForm').style.display = 'none';
            document.getElementById('loginCredentials').style.display = 'none';
            document.getElementById('loginInfo').style.display = 'none';
            
            // Mostrar el paso solicitado
            document.getElementById(stepId).style.display = 'block';
            
            // Si es el paso de credenciales, mostrar info
            if (stepId === 'loginCredentials') {
                document.getElementById('loginInfo').style.display = 'block';
            }
        }
        
        // Mostrar la aplicación después del login
        function showApp() {
            document.getElementById('loginScreen').style.display = 'none';
            document.getElementById('appScreen').style.display = 'block';
            
            updateCurrentUserDisplay();
            updateUIForUserRole();
            
            // Cargar datos iniciales
            loadInitialData();
        }
        
        // Actualizar nombre del usuario actual
        function updateCurrentUserDisplay() {
            if (currentUser) {
                const userBadge = document.getElementById('currentUserRole');
                const userNameDisplay = document.getElementById('currentUserNameDisplay');
                
                userNameDisplay.textContent = currentUser.name;
                
                // Actualizar clases según rol
                userBadge.className = currentUser.role === 'admin' ? 'user-badge admin' : 'user-badge worker';
                userBadge.innerHTML = `
                    <i class="fas fa-${currentUser.role === 'admin' ? 'user-shield' : 'user-tie'}"></i>
                    <span id="currentUserNameDisplay">${currentUser.name} (${currentUser.role === 'admin' ? 'Administrador' : 'Trabajador'})</span>
                `;
            }
        }
        
        // Actualizar interfaz según rol del usuario
        function updateUIForUserRole() {
            const isAdmin = currentUser.role === 'admin';
            const userBadge = document.getElementById('currentUserRole');
            
            // Actualizar badge del usuario
            userBadge.className = isAdmin ? 'user-badge admin' : 'user-badge worker';
            userBadge.innerHTML = `
                <i class="fas fa-${isAdmin ? 'user-shield' : 'user-tie'}"></i>
                <span id="currentUserNameDisplay">${currentUser.name} (${isAdmin ? 'Administrador' : 'Trabajador'})</span>
            `;
            
            // Mostrar/ocultar elementos según rol
            const adminElements = document.querySelectorAll('.admin-only');
            adminElements.forEach(element => {
                element.style.display = isAdmin ? '' : 'none';
            });
            
            // Si es trabajador, asegurarse de que solo pueda ver inventario y ventas
            if (!isAdmin) {
                // Desactivar todas las secciones excepto inventario y ventas
                const sections = document.querySelectorAll('.section-container');
                sections.forEach(section => {
                    if (section.id !== 'inventory' && section.id !== 'sales') {
                        section.style.display = 'none';
                    }
                });
                
                // Ocultar botones de navegación no permitidos
                const navButtons = document.querySelectorAll('.nav-btn');
                navButtons.forEach(button => {
                    if (button.dataset.section !== 'inventory' && button.dataset.section !== 'sales') {
                        button.style.display = 'none';
                    }
                });
            }
        }
        
        // Cargar datos iniciales
        async function loadInitialData() {
            await loadInventoryTable();
            await loadExpensesTable();
            await loadPendingSalesTable();
            await loadWarrantiesTable();
            await loadHistoryData();
            
            // Inicializar venta múltiple
            initializeMultipleSale();
        }
        
        // ==============================
        // FUNCIONES DE TABLAS
        // ==============================
        
        // Cargar tabla de inventario
        async function loadInventoryTable() {
            const products = await loadProducts();
            const tableBody = document.getElementById('inventoryTableBody');
            
            if (!tableBody) return;
            
            tableBody.innerHTML = '';
            
            products.forEach(product => {
                const profit = product.retail_price - product.purchase_price;
                const profitPercentage = product.purchase_price > 0 ? 
                    (profit / product.purchase_price * 100).toFixed(2) : 0;
                    
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td><strong>${product.id}</strong></td>
                    <td>${product.name}</td>
                    <td>
                        <span class="badge ${product.quantity > 10 ? 'badge-success' : product.quantity > 0 ? 'badge-warning' : 'badge-danger'}">
                            ${product.quantity} unidades
                        </span>
                    </td>
                    <td>${formatCurrency(product.purchase_price)}</td>
                    <td>${formatCurrency(product.wholesale_price)}</td>
                    <td>${formatCurrency(product.retail_price)}</td>
                    <td>
                        ${formatCurrency(profit)}<br>
                        <small>(${profitPercentage}%)</small>
                    </td>
                    <td>${product.supplier}</td>
                    <td class="admin-only">
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct('${product.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Cargar tabla de garantías
        async function loadWarrantiesTable() {
            const warranties = await loadWarranties();
            const tableBody = document.getElementById('warrantiesTableBody');
            
            if (!tableBody) return;
            
            tableBody.innerHTML = '';
            
            warranties.forEach(warranty => {
                const row = document.createElement('tr');
                
                // Determinar producto nuevo
                let newProduct = warranty.original_product_name;
                if (warranty.replacement_type === 'different' && warranty.replacement_product_name) {
                    newProduct = warranty.replacement_product_name;
                }
                
                row.innerHTML = `
                    <td>${formatDate(warranty.warranty_date)}</td>
                    <td>${warranty.customer_name}<br><small>${warranty.customer_phone}</small></td>
                    <td>${warranty.original_product_id}<br><small>${warranty.original_product_name}</small></td>
                    <td>${newProduct}</td>
                    <td>${getWarrantyReasonName(warranty.warranty_reason)}<br><small>${warranty.warranty_description.substring(0, 30)}...</small></td>
                    <td>${formatCurrency(warranty.shipping_cost)}<br><small>${warranty.shipping_paid_by}</small></td>
                    <td>${formatCurrency(warranty.price_difference)}</td>
                    <td>
                        <span class="badge ${warranty.user_name ? 'badge-admin' : 'badge-worker'}">
                            ${warranty.user_name || warranty.user_id}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteWarranty('${warranty.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Cargar tabla de gastos
        async function loadExpensesTable() {
            const expenses = await loadExpenses();
            const tableBody = document.getElementById('expensesTableBody');
            
            if (!tableBody) return;
            
            tableBody.innerHTML = '';
            
            expenses.forEach(expense => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${formatDate(expense.expense_date)}</td>
                    <td>${expense.description}</td>
                    <td>
                        <span class="badge ${expense.category === 'warranty' ? 'badge-warning' : expense.category === 'shipping' ? 'badge-info' : 'badge-success'}">
                            ${getExpenseCategoryName(expense.category)}
                        </span>
                    </td>
                    <td><strong>${formatCurrency(expense.amount)}</strong></td>
                    <td>
                        <span class="badge ${expense.user_name ? 'badge-admin' : 'badge-worker'}">
                            ${expense.user_name || expense.user_id}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteExpense('${expense.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Cargar tabla de ventas pendientes
        async function loadPendingSalesTable() {
            const pendingSales = await loadPendingSales();
            const tableBody = document.getElementById('pendingTableBody');
            
            if (!tableBody) return;
            
            tableBody.innerHTML = '';
            
            pendingSales.forEach(sale => {
                const row = document.createElement('tr');
                
                // Contar productos
                const productCount = sale.items ? sale.items.length : 1;
                const productsText = sale.items ? 
                    `${productCount} productos` : 
                    (sale.product_name || '1 producto');
                
                row.innerHTML = `
                    <td><strong>${sale.id}</strong></td>
                    <td>${sale.customer_name || 'Cliente de mostrador'}</td>
                    <td>${productsText}</td>
                    <td><strong>${formatCurrency(sale.total)}</strong></td>
                    <td><span class="badge ${paymentMethods[sale.payment_method]?.class || 'badge-warning'}">${getPaymentMethodName(sale.payment_method)}</span></td>
                    <td>${formatDate(sale.sale_date)}</td>
                    <td>
                        <span class="badge ${sale.user_name ? 'badge-admin' : 'badge-worker'}">
                            ${sale.user_name || sale.user_id}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick="confirmPayment('${sale.id}')" style="margin-right: 5px;">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="cancelPendingSale('${sale.id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Cargar datos del historial
        async function loadHistoryData() {
            const historyData = await loadMonthlyHistory(currentMonth + 1, currentYear);
            
            // Actualizar resumen mensual
            const monthlySummary = document.getElementById('monthlySummary');
            if (monthlySummary) {
                const stats = historyData.stats || {};
                monthlySummary.innerHTML = `
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">${formatCurrency(stats.sales?.total_sales || 0)}</div>
                        <div class="stat-label">Ventas Totales</div>
                        <small>${stats.sales?.sales_count || 0} ventas confirmadas</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-value">${formatCurrency(stats.expenses?.total_expenses || 0)}</div>
                        <div class="stat-label">Gastos Totales</div>
                        <small>${stats.expenses?.expenses_count || 0} gastos registrados</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-value">${formatCurrency(stats.restocks?.total_restocks || 0)}</div>
                        <div class="stat-label">Inventario Surtido</div>
                        <small>${stats.restocks?.restocks_count || 0} surtidos realizados</small>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-value">${formatCurrency(
                            (stats.sales?.total_sales || 0) - 
                            (stats.expenses?.total_expenses || 0) - 
                            (stats.restocks?.total_restocks || 0)
                        )}</div>
                        <div class="stat-label">Ganancia Neta</div>
                        <small>Ventas - Gastos - Surtidos</small>
                    </div>
                `;
            }
            
            // Actualizar tabla de historial
            const historyTableBody = document.getElementById('historyTableBody');
            if (historyTableBody) {
                historyTableBody.innerHTML = '';
                
                const transactions = historyData.transactions || [];
                
                transactions.forEach(transaction => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${transaction.date_formatted}</td>
                        <td>
                            <span class="badge badge-${transaction.type_class}">
                                <i class="fas fa-${getTransactionIcon(transaction.type)}"></i> 
                                ${transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1)}
                            </span>
                        </td>
                        <td>${transaction.description}</td>
                        <td>
                            <span class="badge ${transaction.user_name ? 'badge-admin' : 'badge-worker'}">
                                ${transaction.user_name || transaction.user_id}
                            </span>
                        </td>
                        <td>1</td>
                        <td><strong>${transaction.amount_formatted}</strong></td>
                        <td>
                            ${transaction.type === 'venta' ? 
                                `<button class="btn btn-pdf btn-sm" onclick="viewSalePDF('${transaction.id}')" title="Ver factura en PDF">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>` : 
                                '<span class="badge badge-info">No aplica</span>'
                            }
                        </td>
                    `;
                    
                    historyTableBody.appendChild(row);
                });
            }
        }
        
        // ==============================
        // FUNCIONES DE VENTAS MÚLTIPLES
        // ==============================
        
        // Inicializar venta múltiple
        function initializeMultipleSale() {
            const addProductBtn = document.getElementById('addProductToSale');
            const clearSaleBtn = document.getElementById('clearSaleForm');
            
            if (addProductBtn) {
                addProductBtn.addEventListener('click', function() {
                    addProductToSale();
                });
            }
            
            if (clearSaleBtn) {
                clearSaleBtn.addEventListener('click', async function() {
                    const confirmed = await showDialog(
                        'Limpiar Venta',
                        '¿Está seguro de que desea limpiar toda la venta? Se eliminarán todos los productos.',
                        'question',
                        true
                    );
                    
                    if (confirmed) {
                        clearSale();
                    }
                });
            }
        }
        
        // Agregar producto a la venta múltiple
        function addProductToSale(product = null) {
            const container = document.getElementById('saleProductsContainer');
            const noProductsMessage = document.getElementById('noProductsMessage');
            
            // Ocultar mensaje de no productos
            if (noProductsMessage) {
                noProductsMessage.style.display = 'none';
            }
            
            const productId = product ? product.id : `product-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            
            const productItem = document.createElement('div');
            productItem.className = 'product-item';
            productItem.id = productId;
            
            productItem.innerHTML = `
                <div class="product-item-header">
                    <h4>Producto ${document.querySelectorAll('.product-item').length + 1}</h4>
                    <button type="button" class="remove-product-btn" onclick="removeProductFromSale('${productId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Referencia del Producto *</label>
                        <input type="text" class="product-ref form-control" placeholder="Ej: REF001" value="${product ? product.ref : ''}" required>
                        <div class="product-info" style="margin-top: 6px; font-size: 0.85rem; color: var(--info);"></div>
                    </div>
                    <div class="form-group">
                        <label>Cantidad *</label>
                        <input type="number" class="product-quantity form-control" min="1" value="${product ? product.quantity : 1}" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Venta *</label>
                        <select class="product-sale-type form-control" required>
                            <option value="retail" ${product && product.saleType === 'retail' ? 'selected' : ''}>Detal</option>
                            <option value="wholesale" ${product && product.saleType === 'wholesale' ? 'selected' : ''}>Mayorista</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descuento Individual (%)</label>
                        <input type="number" class="product-discount form-control" min="0" max="100" value="${product ? product.discount : 0}">
                    </div>
                </div>
            `;
            
            container.appendChild(productItem);
            
            // Agregar evento para buscar producto
            const refInput = productItem.querySelector('.product-ref');
            const infoDiv = productItem.querySelector('.product-info');
            
            refInput.addEventListener('input', async function() {
                const productRef = this.value.trim().toUpperCase();
                if (productRef) {
                    const products = await loadProducts();
                    const product = products.find(p => p.id === productRef);
                    
                    if (product) {
                        const retailPrice = formatCurrency(product.retail_price);
                        const wholesalePrice = formatCurrency(product.wholesale_price);
                        infoDiv.innerHTML = 
                            `<strong>${product.name}</strong><br>
                             💰 Detal: ${retailPrice} | 📦 Mayorista: ${wholesalePrice}<br>
                             📊 Stock disponible: ${product.quantity} unidades`;
                    } else {
                        infoDiv.innerHTML = '<span style="color: var(--danger);">❌ Producto no encontrado</span>';
                    }
                } else {
                    infoDiv.textContent = '';
                }
                
                updateSaleSummary();
            });
            
            // Agregar eventos para actualizar resumen
            productItem.querySelectorAll('.product-quantity, .product-sale-type, .product-discount').forEach(input => {
                input.addEventListener('input', updateSaleSummary);
            });
            
            // Si se pasó un producto, actualizar información
            if (product && product.ref) {
                refInput.dispatchEvent(new Event('input'));
            }
            
            updateSaleSummary();
        }
        
        // Remover producto de la venta
        window.removeProductFromSale = function(productId) {
            const productItem = document.getElementById(productId);
            if (productItem) {
                productItem.remove();
            }
            
            // Mostrar mensaje si no hay productos
            const container = document.getElementById('saleProductsContainer');
            if (container && container.children.length === 0) {
                const noProductsMessage = document.getElementById('noProductsMessage');
                if (noProductsMessage) {
                    noProductsMessage.style.display = 'block';
                }
            }
            
            updateSaleSummary();
        };
        
        // Limpiar toda la venta
        function clearSale() {
            const container = document.getElementById('saleProductsContainer');
            if (container) {
                container.innerHTML = '';
            }
            
            // Mostrar mensaje de no productos
            const noProductsMessage = document.getElementById('noProductsMessage');
            if (noProductsMessage) {
                noProductsMessage.style.display = 'block';
            }
            
            // Limpiar formulario de cliente
            const customerFields = ['customerName', 'customerId', 'customerPhone', 'customerEmail', 'customerAddress', 'customerCity'];
            customerFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) element.value = '';
            });
            
            // Restablecer otros campos
            const saleType = document.getElementById('saleType');
            const discount = document.getElementById('discount');
            const paymentMethod = document.getElementById('paymentMethod');
            const deliveryType = document.getElementById('deliveryType');
            const deliveryCost = document.getElementById('deliveryCost');
            
            if (saleType) saleType.value = 'retail';
            if (discount) discount.value = '0';
            if (paymentMethod) paymentMethod.value = 'cash';
            if (deliveryType) deliveryType.value = 'store';
            if (deliveryCost) deliveryCost.value = '0';
            
            updateSaleSummary();
        }
        
        // Actualizar resumen de venta múltiple
        async function updateSaleSummary() {
            const productItems = document.querySelectorAll('.product-item');
            const generalDiscount = parseFloat(document.getElementById('discount')?.value || 0);
            const deliveryCost = parseFloat(document.getElementById('deliveryCost')?.value || 0);
            
            let subtotal = 0;
            let itemsTotal = 0;
            let totalItems = 0;
            let discountAmount = 0;
            
            const products = await loadProducts();
            
            for (const item of productItems) {
                const ref = item.querySelector('.product-ref')?.value.trim().toUpperCase() || '';
                const quantity = parseInt(item.querySelector('.product-quantity')?.value || 0);
                const saleType = item.querySelector('.product-sale-type')?.value || 'retail';
                const discount = parseFloat(item.querySelector('.product-discount')?.value || 0);
                
                // Buscar producto
                const product = products.find(p => p.id === ref);
                
                if (product && quantity > 0) {
                    const unitPrice = saleType === 'retail' ? product.retail_price : product.wholesale_price;
                    const itemSubtotal = unitPrice * quantity;
                    const itemDiscount = itemSubtotal * (discount / 100);
                    itemsTotal += itemSubtotal - itemDiscount;
                    totalItems += quantity;
                }
            }
            
            subtotal = itemsTotal;
            discountAmount = subtotal * (generalDiscount / 100);
            const total = subtotal - discountAmount + deliveryCost;
            
            // Actualizar UI
            const subtotalElement = document.getElementById('subtotalAmount');
            const discountElement = document.getElementById('discountAmount');
            const deliveryElement = document.getElementById('deliveryAmount');
            const totalElement = document.getElementById('totalAmount');
            const productsCountElement = document.getElementById('productsCount');
            const totalItemsElement = document.getElementById('totalItems');
            
            if (subtotalElement) subtotalElement.textContent = formatCurrency(subtotal);
            if (discountElement) discountElement.textContent = formatCurrency(discountAmount);
            if (deliveryElement) deliveryElement.textContent = formatCurrency(deliveryCost);
            if (totalElement) totalElement.textContent = formatCurrency(total);
            if (productsCountElement) productsCountElement.textContent = `Productos: ${productItems.length}`;
            if (totalItemsElement) totalItemsElement.textContent = `Artículos: ${totalItems}`;
        }
        
        // ==============================
        // CONFIGURACIÓN DE EVENTOS
        // ==============================
        
        // Configurar eventos de login
        function setupLoginEvents() {
            const adminRoleBtn = document.getElementById('adminRole');
            const workerRoleBtn = document.getElementById('workerRole');
            const nextToUserInfoBtn = document.getElementById('nextToUserInfo');
            const backToRoleSelectionBtn = document.getElementById('backToRoleSelection');
            const nextToLoginBtn = document.getElementById('nextToLogin');
            const backToUserInfoBtn = document.getElementById('backToUserInfo');
            const loginForm = document.getElementById('loginForm');
            const userInfoForm = document.getElementById('userInfoFormData');
            
            // Alternar entre roles
            if (adminRoleBtn && workerRoleBtn) {
                adminRoleBtn.addEventListener('click', function() {
                    adminRoleBtn.classList.add('active');
                    workerRoleBtn.classList.remove('active');
                    selectedRole = 'admin';
                });
                
                workerRoleBtn.addEventListener('click', function() {
                    workerRoleBtn.classList.add('active');
                    adminRoleBtn.classList.remove('active');
                    selectedRole = 'worker';
                });
            }
            
            // Paso 1: Continuar a información personal
            if (nextToUserInfoBtn) {
                nextToUserInfoBtn.addEventListener('click', function() {
                    showLoginStep('userInfoForm');
                });
            }
            
            // Volver a selección de rol
            if (backToRoleSelectionBtn) {
                backToRoleSelectionBtn.addEventListener('click', function() {
                    showLoginStep('roleSelection');
                });
            }
            
            // Paso 2: Continuar a credenciales
            if (nextToLoginBtn) {
                nextToLoginBtn.addEventListener('click', function() {
                    showLoginStep('loginCredentials');
                });
            }
            
            // Volver a información personal
            if (backToUserInfoBtn) {
                backToUserInfoBtn.addEventListener('click', function() {
                    showLoginStep('userInfoForm');
                });
            }
            
            // Guardar información personal
            if (userInfoForm) {
                userInfoForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showLoginStep('loginCredentials');
                });
            }
            
            // Manejar login
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const username = document.getElementById('username').value;
                    const password = document.getElementById('password').value;
                    const expectedRole = selectedRole;
                    
                    await loginWithPHP(username, password, expectedRole);
                });
            }
        }
        
        // Configurar eventos de navegación
        function setupNavigationEvents() {
            const navButtons = document.querySelectorAll('.nav-btn');
            const sections = document.querySelectorAll('.section-container');
            
            // Navegación entre secciones
            navButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetSection = this.dataset.section;
                    
                    // Actualizar botones activos
                    navButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Mostrar sección correspondiente
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === targetSection) {
                            section.classList.add('active');
                        }
                    });
                    
                    // Si es la sección de historial, actualizar datos
                    if (targetSection === 'history') {
                        loadHistoryData();
                    }
                    
                    // Si es la sección de garantías, actualizar datos
                    if (targetSection === 'warranties') {
                        loadWarrantiesTable();
                    }
                });
            });
            
            // Botón de cerrar sesión
            const logoutButton = document.getElementById('logoutButton');
            if (logoutButton) {
                logoutButton.addEventListener('click', logout);
            }
            
            // Botón para agregar producto
            const addProductBtn = document.getElementById('addProductBtn');
            if (addProductBtn) {
                addProductBtn.addEventListener('click', function() {
                    const form = document.getElementById('addProductForm');
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        if (form.style.display === 'block') {
                            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });
            }
            
            // Cancelar agregar producto
            const cancelAddProduct = document.getElementById('cancelAddProduct');
            if (cancelAddProduct) {
                cancelAddProduct.addEventListener('click', function() {
                    const form = document.getElementById('addProductForm');
                    if (form) form.style.display = 'none';
                    const productForm = document.getElementById('productForm');
                    if (productForm) productForm.reset();
                    const profitEstimate = document.getElementById('profitEstimate');
                    if (profitEstimate) profitEstimate.value = '';
                });
            }
            
            // Botón para agregar garantía
            const addWarrantyBtn = document.getElementById('addWarrantyBtn');
            if (addWarrantyBtn) {
                addWarrantyBtn.addEventListener('click', function() {
                    const form = document.getElementById('addWarrantyForm');
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        if (form.style.display === 'block') {
                            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });
            }
            
            // Cancelar agregar garantía
            const cancelWarranty = document.getElementById('cancelWarranty');
            if (cancelWarranty) {
                cancelWarranty.addEventListener('click', function() {
                    const form = document.getElementById('addWarrantyForm');
                    if (form) form.style.display = 'none';
                    const warrantyForm = document.getElementById('warrantyForm');
                    if (warrantyForm) warrantyForm.reset();
                    const replacementProductGroup = document.getElementById('replacementProductGroup');
                    if (replacementProductGroup) replacementProductGroup.style.display = 'none';
                });
            }
            
            // Botón para agregar gasto
            const addExpenseBtn = document.getElementById('addExpenseBtn');
            if (addExpenseBtn) {
                addExpenseBtn.addEventListener('click', function() {
                    const form = document.getElementById('addExpenseForm');
                    if (form) {
                        form.style.display = form.style.display === 'none' ? 'block' : 'none';
                        const expenseDate = document.getElementById('expenseDate');
                        if (expenseDate) expenseDate.valueAsDate = new Date();
                        if (form.style.display === 'block') {
                            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });
            }
            
            // Cancelar agregar gasto
            const cancelExpense = document.getElementById('cancelExpense');
            if (cancelExpense) {
                cancelExpense.addEventListener('click', function() {
                    const form = document.getElementById('addExpenseForm');
                    if (form) form.style.display = 'none';
                    const expenseForm = document.getElementById('expenseForm');
                    if (expenseForm) expenseForm.reset();
                });
            }
        }
        
        // Configurar eventos de formularios
        function setupFormEvents() {
            // Calcular ganancia estimada al cambiar precios
            const retailPrice = document.getElementById('retailPrice');
            const purchasePrice = document.getElementById('purchasePrice');
            
            if (retailPrice && purchasePrice) {
                retailPrice.addEventListener('input', calculateProfit);
                purchasePrice.addEventListener('input', calculateProfit);
            }
            
            // Formulario de producto
            const productForm = document.getElementById('productForm');
            if (productForm) {
                productForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const productData = {
                        id: document.getElementById('productRef')?.value.trim().toUpperCase() || '',
                        name: document.getElementById('productName')?.value.trim() || '',
                        quantity: parseInt(document.getElementById('productQuantity')?.value || 0),
                        purchase_price: parseFloat(document.getElementById('purchasePrice')?.value || 0),
                        wholesale_price: parseFloat(document.getElementById('wholesalePrice')?.value || 0),
                        retail_price: parseFloat(document.getElementById('retailPrice')?.value || 0),
                        supplier: document.getElementById('supplier')?.value.trim() || ''
                    };
                    
                    const success = await addProduct(productData);
                    if (success) {
                        productForm.reset();
                        const profitEstimate = document.getElementById('profitEstimate');
                        if (profitEstimate) profitEstimate.value = '';
                        const addProductForm = document.getElementById('addProductForm');
                        if (addProductForm) addProductForm.style.display = 'none';
                        await loadInventoryTable();
                    }
                });
            }
            
            // Formulario de surtir inventario
            const restockForm = document.getElementById('restockForm');
            if (restockForm) {
                restockForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const productRef = document.getElementById('restockProductRef')?.value.trim().toUpperCase() || '';
                    const quantity = parseInt(document.getElementById('restockQuantity')?.value || 0);
                    
                    if (!productRef || quantity <= 0) {
                        await showDialog('Error', 'Por favor ingrese datos válidos.', 'error');
                        return;
                    }
                    
                    const success = await restockProduct(productRef, quantity);
                    if (success) {
                        restockForm.reset();
                        const restockProductInfo = document.getElementById('restockProductInfo');
                        if (restockProductInfo) restockProductInfo.textContent = '';
                        await loadInventoryTable();
                        await showDialog('Éxito', 'Inventario surtido exitosamente.', 'success');
                    }
                });
                
                // Buscar producto al escribir referencia (surtir)
                const restockProductRef = document.getElementById('restockProductRef');
                if (restockProductRef) {
                    restockProductRef.addEventListener('input', async function() {
                        const productRef = this.value.trim().toUpperCase();
                        const infoDiv = document.getElementById('restockProductInfo');
                        
                        if (!infoDiv) return;
                        
                        if (productRef) {
                            const products = await loadProducts();
                            const product = products.find(p => p.id === productRef);
                            
                            if (product) {
                                infoDiv.innerHTML = 
                                    `<strong>${product.name}</strong><br>
                                     Stock actual: ${product.quantity} unidades<br>
                                     Precio detal: ${formatCurrency(product.retail_price)}`;
                            } else {
                                infoDiv.textContent = '❌ Producto no encontrado';
                            }
                        } else {
                            infoDiv.textContent = '';
                        }
                    });
                }
            }
            
            // Formulario de garantías
            const warrantyForm = document.getElementById('warrantyForm');
            if (warrantyForm) {
                warrantyForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const warrantyData = {
                        customer_name: document.getElementById('warrantyCustomerName')?.value.trim() || '',
                        customer_phone: document.getElementById('warrantyCustomerPhone')?.value.trim() || '',
                        customer_identification: document.getElementById('warrantyCustomerId')?.value.trim() || '',
                        original_product_id: document.getElementById('originalProductRef')?.value.trim().toUpperCase() || '',
                        warranty_reason: document.getElementById('warrantyReason')?.value || '',
                        warranty_description: document.getElementById('warrantyDescription')?.value.trim() || '',
                        replacement_type: document.getElementById('replacementType')?.value || '',
                        replacement_product_id: document.getElementById('replacementProductRef')?.value.trim().toUpperCase() || '',
                        price_difference: parseFloat(document.getElementById('priceDifference')?.value || 0),
                        shipping_cost: parseFloat(document.getElementById('warrantyShippingCost')?.value || 0),
                        shipping_paid_by: document.getElementById('shippingPaidBy')?.value || 'admin'
                    };
                    
                    const success = await registerWarranty(warrantyData);
                    if (success) {
                        warrantyForm.reset();
                        const replacementProductGroup = document.getElementById('replacementProductGroup');
                        if (replacementProductGroup) replacementProductGroup.style.display = 'none';
                        const addWarrantyForm = document.getElementById('addWarrantyForm');
                        if (addWarrantyForm) addWarrantyForm.style.display = 'none';
                        await loadWarrantiesTable();
                        await loadExpensesTable();
                    }
                });
                
                // Eventos para formulario de garantías
                const originalProductRef = document.getElementById('originalProductRef');
                if (originalProductRef) {
                    originalProductRef.addEventListener('input', async function() {
                        const productRef = this.value.trim().toUpperCase();
                        const infoDiv = document.getElementById('originalProductInfo');
                        
                        if (!infoDiv) return;
                        
                        if (productRef) {
                            const products = await loadProducts();
                            const product = products.find(p => p.id === productRef);
                            
                            if (product) {
                                infoDiv.innerHTML = 
                                    `<strong>${product.name}</strong><br>
                                     Precio detal: ${formatCurrency(product.retail_price)}`;
                            } else {
                                infoDiv.textContent = '❌ Producto no encontrado';
                            }
                        } else {
                            infoDiv.textContent = '';
                        }
                    });
                }
                
                const replacementProductRef = document.getElementById('replacementProductRef');
                if (replacementProductRef) {
                    replacementProductRef.addEventListener('input', async function() {
                        const productRef = this.value.trim().toUpperCase();
                        const infoDiv = document.getElementById('replacementProductInfo');
                        
                        if (!infoDiv) return;
                        
                        if (productRef) {
                            const products = await loadProducts();
                            const product = products.find(p => p.id === productRef);
                            
                            if (product) {
                                infoDiv.innerHTML = 
                                    `<strong>${product.name}</strong><br>
                                     Precio detal: ${formatCurrency(product.retail_price)}`;
                            } else {
                                infoDiv.textContent = '❌ Producto no encontrado';
                            }
                        } else {
                            infoDiv.textContent = '';
                        }
                    });
                }
                
                const replacementType = document.getElementById('replacementType');
                if (replacementType) {
                    replacementType.addEventListener('change', function() {
                        const replacementProductGroup = document.getElementById('replacementProductGroup');
                        if (this.value === 'different') {
                            if (replacementProductGroup) replacementProductGroup.style.display = 'block';
                        } else {
                            if (replacementProductGroup) replacementProductGroup.style.display = 'none';
                        }
                    });
                }
                
                // Actualizar resumen de garantía
                const priceDifference = document.getElementById('priceDifference');
                const warrantyShippingCost = document.getElementById('warrantyShippingCost');
                const shippingPaidBy = document.getElementById('shippingPaidBy');
                
                if (priceDifference) priceDifference.addEventListener('input', updateWarrantySummary);
                if (warrantyShippingCost) warrantyShippingCost.addEventListener('input', updateWarrantySummary);
                if (shippingPaidBy) shippingPaidBy.addEventListener('change', updateWarrantySummary);
            }
            
            // Formulario de gastos
            const expenseForm = document.getElementById('expenseForm');
            if (expenseForm) {
                expenseForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const expenseData = {
                        description: document.getElementById('expenseDescription')?.value.trim() || '',
                        expense_date: document.getElementById('expenseDate')?.value || '',
                        amount: parseFloat(document.getElementById('expenseAmount')?.value || 0),
                        category: document.getElementById('expenseCategory')?.value || 'general'
                    };
                    
                    const success = await registerExpense(expenseData);
                    if (success) {
                        expenseForm.reset();
                        const addExpenseForm = document.getElementById('addExpenseForm');
                        if (addExpenseForm) addExpenseForm.style.display = 'none';
                        await loadExpensesTable();
                        await loadHistoryData();
                    }
                });
            }
            
            // Formulario de venta múltiple
            const saleForm = document.getElementById('saleForm');
            if (saleForm) {
                saleForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Validar que haya al menos un producto
                    const productItems = document.querySelectorAll('.product-item');
                    if (productItems.length === 0) {
                        await showDialog('Error', 'Debe agregar al menos un producto a la venta.', 'error');
                        return;
                    }
                    
                    // Validar datos del cliente
                    const customerName = document.getElementById('customerName')?.value.trim() || '';
                    const customerId = document.getElementById('customerId')?.value.trim() || '';
                    const customerPhone = document.getElementById('customerPhone')?.value.trim() || '';
                    const customerAddress = document.getElementById('customerAddress')?.value.trim() || '';
                    const customerCity = document.getElementById('customerCity')?.value.trim() || '';
                    
                    if (!customerName || !customerId || !customerPhone || !customerAddress || !customerCity) {
                        await showDialog('Error', 'Por favor complete todos los datos obligatorios del cliente (*).', 'error');
                        return;
                    }
                    
                    // Recopilar productos de la venta
                    const saleItems = [];
                    let allValid = true;
                    let errorMessage = '';
                    
                    const products = await loadProducts();
                    
                    productItems.forEach((item, index) => {
                        const ref = item.querySelector('.product-ref')?.value.trim().toUpperCase() || '';
                        const quantity = parseInt(item.querySelector('.product-quantity')?.value || 0);
                        const saleType = item.querySelector('.product-sale-type')?.value || 'retail';
                        const discount = parseFloat(item.querySelector('.product-discount')?.value || 0);
                        
                        // Validar producto
                        const product = products.find(p => p.id === ref);
                        
                        if (!product) {
                            allValid = false;
                            errorMessage = `Producto ${index + 1}: No se encontró un producto con la referencia "${ref}".`;
                            return;
                        }
                        
                        if (quantity > product.quantity) {
                            allValid = false;
                            errorMessage = `Producto ${index + 1}: No hay suficiente stock. Solo hay ${product.quantity} unidades disponibles de "${product.name}".`;
                            return;
                        }
                        
                        if (quantity <= 0) {
                            allValid = false;
                            errorMessage = `Producto ${index + 1}: La cantidad debe ser mayor a 0.`;
                            return;
                        }
                        
                        saleItems.push({
                            product_id: ref,
                            product_name: product.name,
                            quantity: quantity,
                            sale_type: saleType,
                            unit_price: saleType === 'retail' ? product.retail_price : product.wholesale_price,
                            discount: discount
                        });
                    });
                    
                    if (!allValid) {
                        await showDialog('Error', errorMessage, 'error');
                        return;
                    }
                    
                    // Información del cliente
                    const customerInfo = {
                        name: customerName,
                        id: customerId,
                        phone: customerPhone,
                        email: document.getElementById('customerEmail')?.value.trim() || '',
                        address: customerAddress,
                        city: customerCity
                    };
                    
                    // Calcular totales
                    const deliveryCost = parseFloat(document.getElementById('deliveryCost')?.value || 0);
                    const generalDiscount = parseFloat(document.getElementById('discount')?.value || 0);
                    
                    let subtotal = 0;
                    let itemsTotal = 0;
                    let discountAmount = 0;
                    
                    saleItems.forEach(item => {
                        const itemSubtotal = item.unit_price * item.quantity;
                        const itemDiscount = itemSubtotal * (item.discount / 100);
                        itemsTotal += itemSubtotal - itemDiscount;
                    });
                    
                    subtotal = itemsTotal;
                    discountAmount = subtotal * (generalDiscount / 100);
                    const total = subtotal - discountAmount + deliveryCost;
                    
                    // Crear objeto de venta
                    const saleData = {
                        items: saleItems,
                        customer_info: customerInfo,
                        subtotal: subtotal,
                        discount: discountAmount,
                        delivery_cost: deliveryCost,
                        total: total,
                        sale_type: document.getElementById('saleType')?.value || 'retail',
                        payment_method: document.getElementById('paymentMethod')?.value || 'cash',
                        delivery_type: document.getElementById('deliveryType')?.value || 'store'
                    };
                    
                    const result = await registerSale(saleData);
                    
                    if (result) {
                        if (result.data.status === 'completed') {
                            await showDialog('¡Venta Exitosa!', 'La venta ha sido procesada correctamente.', 'success');
                            
                            // Mostrar factura si la venta está completada
                            if (result.data.invoice_id) {
                                await showInvoiceFromAPI(result.data.invoice_id);
                            }
                        } else {
                            await showDialog(
                                'Venta Pendiente', 
                                'Venta registrada como pendiente de pago. El administrador debe confirmar el pago.',
                                'warning'
                            );
                        }
                        
                        // Limpiar formulario
                        clearSale();
                        
                        // Recargar datos
                        await loadInventoryTable();
                        await loadPendingSalesTable();
                        await loadHistoryData();
                    }
                });
            }
            
            // Actualizar resumen de venta al cambiar valores generales
            const discountInput = document.getElementById('discount');
            const deliveryCostInput = document.getElementById('deliveryCost');
            
            if (discountInput) discountInput.addEventListener('input', updateSaleSummary);
            if (deliveryCostInput) deliveryCostInput.addEventListener('input', updateSaleSummary);
        }
        
        // Configurar eventos de la factura
        function setupInvoiceEvents() {
            // Cerrar factura
            const closeInvoice = document.getElementById('closeInvoice');
            if (closeInvoice) {
                closeInvoice.addEventListener('click', function() {
                    const invoiceModal = document.getElementById('invoiceModal');
                    if (invoiceModal) invoiceModal.style.display = 'none';
                });
            }
            
            // Imprimir factura
            const printInvoice = document.getElementById('printInvoice');
            if (printInvoice) {
                printInvoice.addEventListener('click', function() {
                    window.print();
                });
            }
            
            // Descargar factura como PDF
            const downloadInvoice = document.getElementById('downloadInvoice');
            if (downloadInvoice) {
                downloadInvoice.addEventListener('click', async function() {
                    await generateInvoicePDF();
                });
            }
            
            // Compartir factura por WhatsApp
            const shareInvoice = document.getElementById('shareInvoice');
            if (shareInvoice) {
                shareInvoice.addEventListener('click', function() {
                    const invoiceNumber = document.getElementById('invoiceNumber')?.textContent || '';
                    const invoiceDate = document.getElementById('invoiceDate')?.textContent || '';
                    const customerName = document.getElementById('invoiceCustomerName')?.textContent || '';
                    const total = document.getElementById('invoiceTotal')?.textContent || '';
                    
                    // Crear mensaje para WhatsApp
                    const message = `*FACTURA DESTELLO DE ORO 18K*\n\n` +
                                   `${invoiceNumber}\n` +
                                   `${invoiceDate}\n\n` +
                                   `*Cliente:* ${customerName}\n` +
                                   `*Total:* ${total}\n\n` +
                                   `¡Gracias por su compra!\n` +
                                   `Visite nuestras redes sociales:\n` +
                                   `Instagram: ${socialMediaUrls.instagram}\n` +
                                   `WhatsApp: ${socialMediaUrls.whatsapp}\n` +
                                   `Web: ${socialMediaUrls.website}`;
                    
                    // Codificar el mensaje para URL
                    const encodedMessage = encodeURIComponent(message);
                    
                    // Crear enlace de WhatsApp
                    const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
                    
                    // Abrir en nueva pestaña
                    window.open(whatsappUrl, '_blank');
                });
            }
        }
        
        // Configurar diálogo personalizado
        function setupCustomDialog() {
            const dialog = document.getElementById('customDialog');
            const confirmBtn = document.getElementById('dialogConfirm');
            const cancelBtn = document.getElementById('dialogCancel');
            
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (dialog) dialog.style.display = 'none';
                    if (typeof window.dialogCallback === 'function') {
                        window.dialogCallback(true);
                    }
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    if (dialog) dialog.style.display = 'none';
                    if (typeof window.dialogCallback === 'function') {
                        window.dialogCallback(false);
                    }
                });
            }
        }
        
        // Configurar cambio de contraseña
        function setupPasswordChange() {
            const showBtn = document.getElementById('showPasswordChange');
            const closeBtn = document.getElementById('closePasswordChange');
            const cancelBtn = document.getElementById('cancelPasswordChange');
            const modal = document.getElementById('passwordChangeModal');
            const form = document.getElementById('passwordChangeForm');
            
            // Mostrar modal
            if (showBtn) {
                showBtn.addEventListener('click', async function() {
                    if (modal) modal.style.display = 'flex';
                    
                    // Cargar usuarios en el select
                    await loadUsersForPasswordChange();
                    
                    // Enfocar el primer campo
                    setTimeout(() => {
                        const adminUsername = document.getElementById('adminUsername');
                        if (adminUsername) adminUsername.focus();
                    }, 100);
                });
            }
            
            // Cerrar modal
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                    if (form) form.reset();
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                    if (form) form.reset();
                });
            }
            
            // Cerrar modal al hacer clic fuera
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                        if (form) form.reset();
                    }
                });
            }
            
            // Enviar formulario
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const adminUsername = document.getElementById('adminUsername')?.value || '';
                    const adminPassword = document.getElementById('adminPassword')?.value || '';
                    const userToChange = document.getElementById('userToChange')?.value || '';
                    const newPassword = document.getElementById('newPassword')?.value || '';
                    const confirmPassword = document.getElementById('confirmPassword')?.value || '';
                    
                    // Validar que las contraseñas coincidan
                    if (newPassword !== confirmPassword) {
                        await showDialog('Error', 'Las contraseñas no coinciden.', 'error');
                        return;
                    }
                    
                    const success = await changePasswordAPI(adminUsername, adminPassword, userToChange, newPassword);
                    
                    if (success) {
                        form.reset();
                        if (modal) modal.style.display = 'none';
                    }
                });
            }
        }
        
        // Configurar olvidé contraseña
        function setupForgotPassword() {
            const showBtn = document.getElementById('showForgotPassword');
            const closeBtn = document.getElementById('closeForgotPassword');
            const cancelBtn = document.getElementById('cancelForgotPassword');
            const modal = document.getElementById('forgotPasswordModal');
            const form = document.getElementById('forgotPasswordForm');
            const resetMethod = document.getElementById('resetMethod');
            
            // Mostrar modal
            if (showBtn) {
                showBtn.addEventListener('click', async function() {
                    if (modal) modal.style.display = 'flex';
                    
                    // Cargar usuarios en el select
                    await loadUsersForPasswordReset();
                    
                    // Enfocar el primer campo
                    setTimeout(() => {
                        const forgotAdminUsername = document.getElementById('forgotAdminUsername');
                        if (forgotAdminUsername) forgotAdminUsername.focus();
                    }, 100);
                });
            }
            
            // Cerrar modal
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                    if (form) form.reset();
                    const whatsappInfo = document.getElementById('whatsappInfo');
                    const directInfo = document.getElementById('directInfo');
                    if (whatsappInfo) whatsappInfo.style.display = 'none';
                    if (directInfo) directInfo.style.display = 'none';
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                    if (form) form.reset();
                    const whatsappInfo = document.getElementById('whatsappInfo');
                    const directInfo = document.getElementById('directInfo');
                    if (whatsappInfo) whatsappInfo.style.display = 'none';
                    if (directInfo) directInfo.style.display = 'none';
                });
            }
            
            // Cerrar modal al hacer clic fuera
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                        if (form) form.reset();
                        const whatsappInfo = document.getElementById('whatsappInfo');
                        const directInfo = document.getElementById('directInfo');
                        if (whatsappInfo) whatsappInfo.style.display = 'none';
                        if (directInfo) directInfo.style.display = 'none';
                    }
                });
            }
            
            // Mostrar información según método seleccionado
            if (resetMethod) {
                resetMethod.addEventListener('change', function() {
                    const method = this.value;
                    const whatsappInfo = document.getElementById('whatsappInfo');
                    const directInfo = document.getElementById('directInfo');
                    
                    if (whatsappInfo) whatsappInfo.style.display = method === 'whatsapp' ? 'block' : 'none';
                    if (directInfo) directInfo.style.display = method === 'direct' ? 'block' : 'none';
                });
            }
            
            // Enviar formulario
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const adminUsername = document.getElementById('forgotAdminUsername')?.value || '';
                    const adminPassword = document.getElementById('forgotAdminPassword')?.value || '';
                    const userToReset = document.getElementById('userToReset')?.value || '';
                    const resetMethod = document.getElementById('resetMethod')?.value || '';
                    
                    const result = await forgotPasswordAPI(adminUsername, adminPassword, userToReset, resetMethod);
                    
                    if (result) {
                        form.reset();
                        const whatsappInfo = document.getElementById('whatsappInfo');
                        const directInfo = document.getElementById('directInfo');
                        if (whatsappInfo) whatsappInfo.style.display = 'none';
                        if (directInfo) directInfo.style.display = 'none';
                        if (modal) modal.style.display = 'none';
                    }
                });
            }
        }
        
        // Configurar selectores de fecha para historial
        function setupDateSelectors() {
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');
            
            if (!monthSelect || !yearSelect) return;
            
            // Limpiar selectores
            monthSelect.innerHTML = '';
            yearSelect.innerHTML = '';
            
            // Agregar meses
            const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            
            monthNames.forEach((month, index) => {
                const option = document.createElement('option');
                option.value = index + 1;
                option.textContent = month;
                if (index === currentMonth) {
                    option.selected = true;
                }
                monthSelect.appendChild(option);
            });
            
            // Agregar años (desde 2026 hasta 2030)
            for (let year = 2026; year <= 2030; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) {
                    option.selected = true;
                }
                yearSelect.appendChild(option);
            }
            
            // Agregar eventos
            monthSelect.addEventListener('change', function() {
                currentMonth = parseInt(this.value) - 1;
                loadHistoryData();
            });
            
            yearSelect.addEventListener('change', function() {
                currentYear = parseInt(this.value);
                loadHistoryData();
            });
        }
        
        // ==============================
        // FUNCIONES AUXILIARES
        // ==============================
        
        // Calcular ganancia estimada
        function calculateProfit() {
            const purchasePrice = parseFloat(document.getElementById('purchasePrice')?.value || 0);
            const retailPrice = parseFloat(document.getElementById('retailPrice')?.value || 0);
            const profitEstimate = document.getElementById('profitEstimate');
            
            if (!profitEstimate) return;
            
            if (purchasePrice > 0 && retailPrice > 0) {
                const profit = retailPrice - purchasePrice;
                const profitPercentage = (profit / purchasePrice * 100).toFixed(2);
                profitEstimate.value = 
                    `${formatCurrency(profit)} (${profitPercentage}%)`;
            } else {
                profitEstimate.value = '';
            }
        }
        
        // Actualizar resumen de garantía
        function updateWarrantySummary() {
            const shippingCost = parseFloat(document.getElementById('warrantyShippingCost')?.value || 0);
            const priceDifference = parseFloat(document.getElementById('priceDifference')?.value || 0);
            const shippingPaidBy = document.getElementById('shippingPaidBy')?.value || 'admin';
            
            let customerTotal = priceDifference;
            
            // Calcular costo para el cliente según quién paga el envío
            if (shippingPaidBy === 'customer') {
                customerTotal += shippingCost;
            } else if (shippingPaidBy === 'shared') {
                customerTotal += shippingCost / 2;
            }
            
            // Actualizar UI
            const shippingAmount = document.getElementById('warrantyShippingAmount');
            const priceDifferenceAmount = document.getElementById('priceDifferenceAmount');
            const warrantyTotalAmount = document.getElementById('warrantyTotalAmount');
            
            if (shippingAmount) shippingAmount.textContent = formatCurrency(shippingCost);
            if (priceDifferenceAmount) priceDifferenceAmount.textContent = formatCurrency(priceDifference);
            if (warrantyTotalAmount) warrantyTotalAmount.textContent = formatCurrency(customerTotal);
        }
        
        // Cargar usuarios para restablecimiento de contraseña
        async function loadUsersForPasswordReset() {
            const userSelect = document.getElementById('userToReset');
            if (!userSelect) return;
            
            const users = await loadUsers();
            
            // Limpiar select
            userSelect.innerHTML = '<option value="">Seleccione un usuario</option>';
            
            // Agregar opciones
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.username;
                option.textContent = `${user.name} ${user.last_name || ''} (${user.username}) - ${user.role === 'admin' ? 'Administrador' : 'Trabajador'}`;
                userSelect.appendChild(option);
            });
        }
        
        // Cargar usuarios para cambio de contraseña
        async function loadUsersForPasswordChange() {
            const userSelect = document.getElementById('userToChange');
            if (!userSelect) return;
            
            const users = await loadUsers();
            
            // Limpiar select
            userSelect.innerHTML = '<option value="">Seleccione un usuario</option>';
            
            // Agregar opciones
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.username;
                option.textContent = `${user.name} ${user.last_name || ''} (${user.username}) - ${user.role === 'admin' ? 'Administrador' : 'Trabajador'}`;
                userSelect.appendChild(option);
            });
        }
        
        // Función para generar QR Code
        function generateQRCode() {
            const qrContainer = document.getElementById('qrCodeCanvas');
            if (!qrContainer) return;
            
            qrContainer.innerHTML = ''; // Limpiar contenido previo
            
            // Crear contenido para el QR
            const qrContent = `
Destello de Oro 18K
Tienda de Oro Laminado

📱 CONTACTO Y REDES SOCIALES 📱

Instagram: ${socialMediaUrls.instagram}
WhatsApp: ${socialMediaUrls.whatsapp}
Página Web: ${socialMediaUrls.website}

Teléfono: 3182687488

¡Gracias por tu compra!
            `;
            
            try {
                // Crear elemento canvas para el QR
                const canvas = document.createElement('canvas');
                canvas.id = 'qrCanvas';
                canvas.width = 180;
                canvas.height = 180;
                canvas.style.border = '2px solid #333';
                canvas.style.padding = '5px';
                canvas.style.backgroundColor = '#FFFFFF';
                
                // Usar la librería QRCode para generar el código
                QRCode.toCanvas(canvas, qrContent, {
                    width: 180,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    },
                    errorCorrectionLevel: 'M'
                }, function(error) {
                    if (error) {
                        console.error('Error generando QR:', error);
                        createFallbackQR();
                    } else {
                        qrContainer.appendChild(canvas);
                    }
                });
                
            } catch (error) {
                console.error('Error:', error);
                createFallbackQR();
            }
            
            // Función de respaldo si falla la generación del QR
            function createFallbackQR() {
                const fallbackDiv = document.createElement('div');
                fallbackDiv.style.width = '180px';
                fallbackDiv.style.height = '180px';
                fallbackDiv.style.backgroundColor = '#FFFFFF';
                fallbackDiv.style.border = '2px solid #333';
                fallbackDiv.style.display = 'flex';
                fallbackDiv.style.alignItems = 'center';
                fallbackDiv.style.justifyContent = 'center';
                fallbackDiv.style.flexDirection = 'column';
                fallbackDiv.style.padding = '10px';
                
                fallbackDiv.innerHTML = `
                    <div style="text-align: center; font-size: 12px;">
                        <div style="font-weight: bold; margin-bottom: 5px;">Destello de Oro 18K</div>
                        <div style="margin-bottom: 3px;">📱 Instagram:</div>
                        <div style="font-size: 10px;">${socialMediaUrls.instagram}</div>
                        <div style="margin-top: 5px; margin-bottom: 3px;">📱 WhatsApp:</div>
                        <div style="font-size: 10px;">${socialMediaUrls.whatsapp}</div>
                    </div>
                `;
                
                qrContainer.appendChild(fallbackDiv);
            }
        }
        
        // Función para generar PDF de la factura
        async function generateInvoicePDF() {
            // Mostrar mensaje de carga
            await showDialog('Generando PDF', 'Por favor espere mientras se genera el PDF de la factura...', 'info');
            
            try {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Obtener datos de la factura
                const invoiceNumber = document.getElementById('invoiceNumber')?.textContent || '';
                const invoiceDate = document.getElementById('invoiceDate')?.textContent || '';
                const customerName = document.getElementById('invoiceCustomerName')?.textContent || '';
                const total = document.getElementById('invoiceTotal')?.textContent || '';
                
                // Agregar contenido al PDF
                pdf.setFontSize(20);
                pdf.setTextColor(212, 175, 55); // Color oro
                pdf.text('Destello de Oro 18K', 20, 20);
                
                pdf.setFontSize(12);
                pdf.setTextColor(0, 0, 0);
                pdf.text('Factura de Venta', 20, 30);
                pdf.text(invoiceNumber, 20, 40);
                pdf.text(invoiceDate, 20, 45);
                
                pdf.setFontSize(14);
                pdf.text('Datos del Cliente:', 20, 60);
                pdf.setFontSize(10);
                pdf.text(`Nombre: ${customerName}`, 20, 70);
                
                pdf.setFontSize(14);
                pdf.text('Total:', 20, 85);
                pdf.setFontSize(16);
                pdf.setTextColor(212, 175, 55);
                pdf.text(total, 60, 85);
                
                pdf.setFontSize(10);
                pdf.setTextColor(100, 100, 100);
                pdf.text('Gracias por su compra!', 20, 100);
                pdf.text('Contacto: 3182687488', 20, 105);
                
                // Guardar PDF
                pdf.save(`Factura_${invoiceNumber.replace('Factura ', '')}.pdf`);
                
                await showDialog('PDF Generado', 'La factura se ha descargado exitosamente.', 'success');
                
            } catch (error) {
                console.error('Error generando PDF:', error);
                await showDialog('Error', 'No se pudo generar el PDF. Intente usar la opción de imprimir.', 'error');
            }
        }
        
        // Mostrar factura desde API
        async function showInvoiceFromAPI(saleId) {
            // Obtener datos de la venta desde la API
            const result = await apiCall(`sales/get_single.php?id=${saleId}`);
            
            if (!result || result.status !== 'success') {
                await showDialog('Error', 'No se pudo cargar la factura', 'error');
                return;
            }
            
            const sale = result.data;
            
            // Configurar datos de la factura
            document.getElementById('invoiceNumber').textContent = `Factura ${sale.id}`;
            document.getElementById('invoiceDate').textContent = `Fecha: ${formatDate(sale.sale_date)}`;
            document.getElementById('invoicePaymentMethod').textContent = 
                `Método de Pago: ${getPaymentMethodName(sale.payment_method)}`;
            
            // Información del cliente
            if (sale.customer_name) {
                document.getElementById('invoiceCustomerName').textContent = sale.customer_name;
                document.getElementById('invoiceCustomerId').textContent = sale.customer_id || 'No proporcionada';
                document.getElementById('invoiceCustomerPhone').textContent = sale.customer_phone || 'No proporcionado';
                document.getElementById('invoiceCustomerEmail').textContent = sale.customer_email || 'No proporcionado';
                document.getElementById('invoiceCustomerAddress').textContent = sale.customer_address || 'No proporcionada';
                document.getElementById('invoiceCustomerCity').textContent = sale.customer_city || 'No proporcionada';
            } else {
                document.getElementById('invoiceCustomerName').textContent = 'Cliente de mostrador';
                document.getElementById('invoiceCustomerId').textContent = 'No proporcionada';
                document.getElementById('invoiceCustomerPhone').textContent = 'No proporcionado';
                document.getElementById('invoiceCustomerEmail').textContent = 'No proporcionado';
                document.getElementById('invoiceCustomerAddress').textContent = 'Recoge en tienda';
                document.getElementById('invoiceCustomerCity').textContent = 'No proporcionada';
            }
            
            // Detalles de la venta
            const invoiceItemsBody = document.getElementById('invoiceItemsBody');
            invoiceItemsBody.innerHTML = '';
            
            // Agregar cada producto
            sale.items.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${item.product_name}</strong></td>
                    <td>${item.product_id}</td>
                    <td>${item.quantity}</td>
                    <td>${formatCurrency(item.unit_price)}</td>
                    <td><strong>${formatCurrency(item.unit_price * item.quantity)}</strong></td>
                `;
                invoiceItemsBody.appendChild(row);
            });
            
            // Agregar descuento si existe
            if (sale.discount > 0) {
                const discountRow = document.createElement('tr');
                discountRow.innerHTML = `
                    <td colspan="4" style="text-align: right; color: var(--danger);">Descuento:</td>
                    <td style="color: var(--danger);">-${formatCurrency(sale.discount)}</td>
                `;
                invoiceItemsBody.appendChild(discountRow);
            }
            
            // Agregar costo de envío si existe
            if (sale.delivery_cost > 0) {
                const deliveryRow = document.createElement('tr');
                deliveryRow.innerHTML = `
                    <td colspan="4" style="text-align: right; color: var(--info);">Costo de envío:</td>
                    <td style="color: var(--info);">${formatCurrency(sale.delivery_cost)}</td>
                `;
                invoiceItemsBody.appendChild(deliveryRow);
            }
            
            // Total
            document.getElementById('invoiceTotal').textContent = formatCurrency(sale.total);
            
            // GENERAR QR CODE FUNCIONAL
            setTimeout(() => {
                generateQRCode();
            }, 100);
            
            // Mostrar modal
            document.getElementById('invoiceModal').style.display = 'block';
        }
        
        // Ver factura en PDF desde historial
        window.viewSalePDF = async function(saleId) {
            try {
                // Obtener datos de la venta
                const result = await apiCall(`sales/get_single.php?id=${saleId}`);
                
                if (!result || result.status !== 'success') {
                    await showDialog('Error', 'No se encontró la venta especificada.', 'error');
                    return;
                }
                
                const sale = result.data;
                
                // Mostrar mensaje de carga
                await showDialog('Generando PDF', 'Generando factura en formato PDF...', 'info');
                
                // Crear PDF con jsPDF
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Título y encabezado
                pdf.setFontSize(20);
                pdf.setTextColor(212, 175, 55);
                pdf.text('Destello de Oro 18K', 20, 20);
                
                pdf.setFontSize(12);
                pdf.setTextColor(0, 0, 0);
                pdf.text('FACTURA DE VENTA', 20, 30);
                pdf.text(`Factura: ${sale.id}`, 20, 38);
                pdf.text(`Fecha: ${formatDate(sale.sale_date)}`, 20, 44);
                pdf.text(`Vendedor: ${sale.user_name}`, 20, 50);
                
                // Información del cliente
                pdf.setFontSize(14);
                pdf.text('Datos del Cliente:', 20, 65);
                pdf.setFontSize(10);
                
                let yPos = 72;
                pdf.text(`Nombre: ${sale.customer_name || 'Cliente de mostrador'}`, 20, yPos);
                yPos += 6;
                pdf.text(`Cédula: ${sale.customer_id || 'No proporcionada'}`, 20, yPos);
                yPos += 6;
                pdf.text(`Teléfono: ${sale.customer_phone || 'No proporcionado'}`, 20, yPos);
                yPos += 6;
                pdf.text(`Dirección: ${sale.customer_address || 'No proporcionada'}`, 20, yPos);
                
                // Detalles de la venta
                pdf.setFontSize(14);
                pdf.text('Detalles de la Compra:', 20, yPos + 10);
                yPos += 18;
                
                // Encabezado de tabla
                pdf.setFillColor(212, 175, 55);
                pdf.rect(20, yPos - 5, 170, 8, 'F');
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(10);
                pdf.text('Producto', 22, yPos);
                pdf.text('Cant.', 120, yPos);
                pdf.text('Precio', 140, yPos);
                pdf.text('Total', 160, yPos);
                
                pdf.setTextColor(0, 0, 0);
                yPos += 10;
                
                // Productos
                sale.items.forEach((item, index) => {
                    if (yPos > 250) {
                        pdf.addPage();
                        yPos = 20;
                    }
                    
                    pdf.setFontSize(9);
                    pdf.text(item.product_name.substring(0, 40), 22, yPos);
                    pdf.text(item.quantity.toString(), 120, yPos);
                    pdf.text(formatCurrency(item.unit_price), 140, yPos);
                    pdf.text(formatCurrency(item.unit_price * item.quantity), 160, yPos);
                    yPos += 7;
                });
                
                // Totales
                yPos += 10;
                pdf.setFontSize(10);
                pdf.text('Subtotal:', 120, yPos);
                pdf.text(formatCurrency(sale.subtotal), 160, yPos);
                yPos += 7;
                
                if (sale.discount > 0) {
                    pdf.text('Descuento:', 120, yPos);
                    pdf.text(`-${formatCurrency(sale.discount)}`, 160, yPos);
                    yPos += 7;
                }
                
                if (sale.delivery_cost > 0) {
                    pdf.text('Costo de envío:', 120, yPos);
                    pdf.text(formatCurrency(sale.delivery_cost), 160, yPos);
                    yPos += 7;
                }
                
                pdf.setFontSize(12);
                pdf.setFont(undefined, 'bold');
                pdf.text('TOTAL:', 120, yPos);
                pdf.text(formatCurrency(sale.total), 160, yPos);
                
                // Información de contacto
                pdf.setFontSize(10);
                pdf.setTextColor(100, 100, 100);
                pdf.text('¡Gracias por su compra!', 20, 270);
                pdf.text('Contacto: 3182687488', 20, 275);
                pdf.text('Destello de Oro 18K - Tienda de Oro Laminado', 20, 280);
                
                // Guardar PDF
                pdf.save(`Factura_${sale.id}.pdf`);
                
                await showDialog('PDF Generado', 'La factura se ha descargado exitosamente.', 'success');
                
            } catch (error) {
                console.error('Error generando PDF:', error);
                await showDialog('Error', 'No se pudo generar el PDF. Intente nuevamente.', 'error');
            }
        };
        
        // ==============================
        // FUNCIONES GLOBALES (window)
        // ==============================
        
        window.deleteProduct = async function(productId) {
            const confirmed = await showDialog(
                'Eliminar Producto',
                '¿Está seguro de que desea eliminar este producto? Esta acción no se puede deshacer.',
                'warning',
                true
            );
            
            if (confirmed) {
                const success = await deleteProductAPI(productId);
                if (success) {
                    await showDialog('Éxito', 'Producto eliminado correctamente.', 'success');
                    await loadInventoryTable();
                }
            }
        };
        
        window.deleteExpense = async function(expenseId) {
            const confirmed = await showDialog(
                'Eliminar Gasto',
                '¿Está seguro de que desea eliminar este gasto? Esta acción no se puede deshacer.',
                'warning',
                true
            );
            
            if (confirmed) {
                const success = await deleteExpenseAPI(expenseId);
                if (success) {
                    await showDialog('Éxito', 'Gasto eliminado correctamente.', 'success');
                    await loadExpensesTable();
                    await loadHistoryData();
                }
            }
        };
        
        window.deleteWarranty = async function(warrantyId) {
            const confirmed = await showDialog(
                'Eliminar Garantía',
                '¿Está seguro de que desea eliminar esta garantía? Esta acción no se puede deshacer.',
                'warning',
                true
            );
            
            if (confirmed) {
                const success = await deleteWarrantyAPI(warrantyId);
                if (success) {
                    await showDialog('Éxito', 'Garantía eliminada correctamente.', 'success');
                    await loadWarrantiesTable();
                    await loadHistoryData();
                }
            }
        };
        
        window.confirmPayment = async function(saleId) {
            const confirmed = await showDialog(
                'Confirmar Pago',
                '¿Confirmar que se recibió el pago de esta venta?',
                'question',
                true
            );
            
            if (confirmed) {
                const success = await confirmPaymentAPI(saleId);
                if (success) {
                    await showDialog('Éxito', 'Pago confirmado y venta procesada exitosamente.', 'success');
                    await loadPendingSalesTable();
                    await loadInventoryTable();
                    await loadHistoryData();
                }
            }
        };
        
        window.cancelPendingSale = async function(saleId) {
            const confirmed = await showDialog(
                'Cancelar Venta Pendiente',
                '¿Cancelar esta venta pendiente? Esta acción no se puede deshacer.',
                'warning',
                true
            );
            
            if (confirmed) {
                const success = await cancelPendingSaleAPI(saleId);
                if (success) {
                    await showDialog('Éxito', 'Venta pendiente cancelada correctamente.', 'success');
                    await loadPendingSalesTable();
                }
            }
        };
        
        // ==============================
        // FUNCIONES DE UTILIDAD
        // ==============================
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-CO', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function getPaymentMethodName(method) {
            return paymentMethods[method]?.name || method;
        }
        
        function getUserName(username) {
            // Esta función ahora se maneja desde el backend
            return username;
        }
        
        function getWarrantyReasonName(reason) {
            const reasons = {
                'color_change': 'Cambio color',
                'scratch': 'Rayón',
                'broken': 'Roto',
                'missing_part': 'Falta pieza',
                'other': 'Otro'
            };
            return reasons[reason] || reason;
        }
        
        function getExpenseCategoryName(category) {
            const categories = {
                'general': 'General',
                'shipping': 'Envíos',
                'warranty': 'Garantías',
                'supplies': 'Insumos',
                'transport': 'Transporte',
                'other': 'Otro'
            };
            return categories[category] || category;
        }
        
        function getTransactionIcon(type) {
            const icons = {
                'venta': 'shopping-cart',
                'gasto': 'file-invoice-dollar',
                'surtido': 'truck-loading',
                'garantía': 'shield-alt'
            };
            return icons[type] || 'info-circle';
        }
        
        // Mostrar diálogo personalizado
        function showDialog(title, message, type = 'info', showCancel = false) {
            return new Promise((resolve) => {
                const dialog = document.getElementById('customDialog');
                const icon = document.getElementById('dialogIcon');
                const dialogTitle = document.getElementById('dialogTitle');
                const dialogMessage = document.getElementById('dialogMessage');
                const confirmBtn = document.getElementById('dialogConfirm');
                const cancelBtn = document.getElementById('dialogCancel');
                
                if (!dialog || !icon || !dialogTitle || !dialogMessage || !confirmBtn) {
                    console.error('Elementos del diálogo no encontrados');
                    resolve(false);
                    return;
                }
                
                // Configurar icono según tipo
                icon.innerHTML = getDialogIcon(type);
                icon.style.color = getDialogColor(type);
                
                // Configurar texto
                dialogTitle.textContent = title;
                dialogMessage.textContent = message;
                
                // Configurar botones
                if (cancelBtn) {
                    cancelBtn.style.display = showCancel ? 'inline-flex' : 'none';
                }
                
                // Guardar callback
                window.dialogCallback = (result) => {
                    resolve(result);
                    delete window.dialogCallback;
                };
                
                // Mostrar diálogo
                dialog.style.display = 'flex';
            });
        }
        
        // Obtener icono para diálogo
        function getDialogIcon(type) {
            switch(type) {
                case 'success': return '<i class="fas fa-check-circle"></i>';
                case 'error': return '<i class="fas fa-exclamation-circle"></i>';
                case 'warning': return '<i class="fas fa-exclamation-triangle"></i>';
                case 'info': return '<i class="fas fa-info-circle"></i>';
                case 'question': return '<i class="fas fa-question-circle"></i>';
                default: return '<i class="fas fa-info-circle"></i>';
            }
        }
        
        // Obtener color para diálogo
        function getDialogColor(type) {
            switch(type) {
                case 'success': return 'var(--success)';
                case 'error': return 'var(--danger)';
                case 'warning': return 'var(--warning)';
                case 'info': return 'var(--info)';
                case 'question': return 'var(--gold-primary)';
                default: return 'var(--info)';
            }
        }
        
        // ==============================
        // INICIALIZACIÓN
        // ==============================
        
        // Cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Si hay sesión activa, cargar la aplicación
            if (currentUser) {
                showApp();
            } else {
                initLoginSteps();
            }
            
            // Configurar eventos
            setupLoginEvents();
            setupNavigationEvents();
            setupFormEvents();
            setupInvoiceEvents();
            setupCustomDialog();
            setupPasswordChange();
            setupForgotPassword();
            
            if (currentUser) {
                updateUIForUserRole();
                initializeMultipleSale();
                setupDateSelectors();
            }
        });
        
    </script>
</body>
</html>