-- Base de datos para Destello de Oro 18K
CREATE DATABASE IF NOT EXISTS destello_oro_db;
USE destello_oro_db;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'worker') NOT NULL DEFAULT 'worker',
    name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Tabla de productos
CREATE TABLE products (
    id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    purchase_price DECIMAL(10, 2) NOT NULL,
    wholesale_price DECIMAL(10, 2) NOT NULL,
    retail_price DECIMAL(10, 2) NOT NULL,
    supplier VARCHAR(100),
    added_by INT,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de clientes
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    identification VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de ventas
CREATE TABLE sales (
    id VARCHAR(20) PRIMARY KEY,
    customer_id INT,
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    delivery_cost DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    sale_type ENUM('retail', 'wholesale') NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    delivery_type VARCHAR(50),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    confirmed BOOLEAN DEFAULT FALSE,
    user_id INT,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de items de venta
CREATE TABLE sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id VARCHAR(20) NOT NULL,
    product_id VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    sale_type ENUM('retail', 'wholesale') NOT NULL,
    discount DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabla de garantías
CREATE TABLE warranties (
    id VARCHAR(20) PRIMARY KEY,
    customer_name VARCHAR(200) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_identification VARCHAR(20) NOT NULL,
    original_product_id VARCHAR(20) NOT NULL,
    warranty_reason VARCHAR(100) NOT NULL,
    warranty_description TEXT NOT NULL,
    replacement_type ENUM('same', 'different') NOT NULL,
    replacement_product_id VARCHAR(20),
    price_difference DECIMAL(10, 2) DEFAULT 0,
    shipping_cost DECIMAL(10, 2) DEFAULT 0,
    shipping_paid_by VARCHAR(50),
    user_id INT,
    warranty_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    FOREIGN KEY (original_product_id) REFERENCES products(id),
    FOREIGN KEY (replacement_product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de gastos
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description VARCHAR(200) NOT NULL,
    expense_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de surtidos
CREATE TABLE restocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id VARCHAR(20) NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    total_value DECIMAL(10, 2) NOT NULL,
    user_id INT,
    restock_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de sesiones de usuario
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertar usuario administrador por defecto (contraseña: admin123)
INSERT INTO users (username, password, role, name, last_name, phone, email) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin', 'Administrador', 'Principal', '3182687488', 'admin@destellodeoro.com');

-- Insertar usuario trabajador por defecto (contraseña: trabajador123)
INSERT INTO users (username, password, role, name, last_name, phone, email) 
VALUES ('trabajador', '$2y$10$YourHashedPasswordHere', 'worker', 'Vendedor', 'Principal', '3001234567', 'vendedor@destellodeoro.com');

-- Crear índices para mejorar rendimiento
CREATE INDEX idx_products_supplier ON products(supplier);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_status ON sales(status);
CREATE INDEX idx_expenses_date ON expenses(expense_date);
CREATE INDEX idx_warranties_date ON warranties(warranty_date);
CREATE INDEX idx_restocks_date ON restocks(restock_date);