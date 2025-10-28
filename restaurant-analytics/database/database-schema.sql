-- God Level Challenge Database Schema
-- Restaurant Analytics Platform
-- Based on generate_data.py structure

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Brands (representing restaurant chains)
CREATE TABLE brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sub-brands (different concepts under a brand)
CREATE TABLE sub_brands (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stores (physical locations)
CREATE TABLE stores (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    sub_brand_id INTEGER REFERENCES sub_brands(id),
    name VARCHAR(255) NOT NULL,
    city VARCHAR(100),
    state VARCHAR(50),
    district VARCHAR(100),
    address_street VARCHAR(255),
    address_number VARCHAR(20),
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6),
    is_active BOOLEAN DEFAULT true,
    is_own BOOLEAN DEFAULT true,
    creation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Channels (sales channels: presential, iFood, Rappi, etc.)
CREATE TABLE channels (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    type CHAR(1) CHECK (type IN ('P', 'D')), -- P=Presential, D=Delivery
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories (for both products and items)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    name VARCHAR(100) NOT NULL,
    type CHAR(1) CHECK (type IN ('P', 'I')), -- P=Product, I=Item
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products (main menu items)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    sub_brand_id INTEGER REFERENCES sub_brands(id),
    category_id INTEGER REFERENCES categories(id),
    name VARCHAR(255) NOT NULL,
    pos_uuid VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items (complements, add-ons, modifications)
CREATE TABLE items (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    sub_brand_id INTEGER REFERENCES sub_brands(id),
    category_id INTEGER REFERENCES categories(id),
    name VARCHAR(255) NOT NULL,
    pos_uuid VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Option groups (grouping for items: "Adicionais", "Remover", etc.)
CREATE TABLE option_groups (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment types
CREATE TABLE payment_types (
    id SERIAL PRIMARY KEY,
    brand_id INTEGER REFERENCES brands(id),
    description VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers
CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone_number VARCHAR(20),
    cpf VARCHAR(14),
    birth_date DATE,
    gender CHAR(2),
    agree_terms BOOLEAN DEFAULT false,
    receive_promotions_email BOOLEAN DEFAULT false,
    registration_origin VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sales (main transaction table)
CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    store_id INTEGER REFERENCES stores(id),
    customer_id INTEGER REFERENCES customers(id) NULL,
    channel_id INTEGER REFERENCES channels(id),
    sub_brand_id INTEGER REFERENCES sub_brands(id) NULL,
    customer_name VARCHAR(255), -- For non-registered customers
    created_at TIMESTAMP NOT NULL,
    sale_status_desc VARCHAR(20) CHECK (sale_status_desc IN ('COMPLETED', 'CANCELLED')),
    total_amount_items DECIMAL(10,2) DEFAULT 0,
    total_discount DECIMAL(10,2) DEFAULT 0,
    total_increase DECIMAL(10,2) DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    service_tax_fee DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    value_paid DECIMAL(10,2) DEFAULT 0,
    production_seconds INTEGER,
    delivery_seconds INTEGER,
    people_quantity INTEGER,
    discount_reason VARCHAR(255),
    origin VARCHAR(50) DEFAULT 'POS'
);

-- Product sales (products sold in each sale)
CREATE TABLE product_sales (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER NOT NULL DEFAULT 1,
    base_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    observations TEXT
);

-- Item product sales (customizations for each product)
CREATE TABLE item_product_sales (
    id SERIAL PRIMARY KEY,
    product_sale_id INTEGER REFERENCES product_sales(id) ON DELETE CASCADE,
    item_id INTEGER REFERENCES items(id),
    option_group_id INTEGER REFERENCES option_groups(id) NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    additional_price DECIMAL(10,2) DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    amount INTEGER DEFAULT 1,
    observations TEXT
);

-- Item item product sales (nested customizations)
CREATE TABLE item_item_product_sales (
    id SERIAL PRIMARY KEY,
    item_product_sale_id INTEGER REFERENCES item_product_sales(id) ON DELETE CASCADE,
    item_id INTEGER REFERENCES items(id),
    option_group_id INTEGER REFERENCES option_groups(id) NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    additional_price DECIMAL(10,2) DEFAULT 0,
    price DECIMAL(10,2) NOT NULL
);

-- Payments (multiple payments per sale possible)
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    payment_type_id INTEGER REFERENCES payment_types(id),
    value DECIMAL(10,2) NOT NULL,
    is_online BOOLEAN DEFAULT false
);

-- Delivery sales (additional info for delivery orders)
CREATE TABLE delivery_sales (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    courier_name VARCHAR(255),
    courier_phone VARCHAR(20),
    courier_type VARCHAR(20) CHECK (courier_type IN ('PLATFORM', 'OWN', 'THIRD_PARTY')),
    delivery_type VARCHAR(20) CHECK (delivery_type IN ('DELIVERY', 'TAKEOUT', 'INDOOR')),
    status VARCHAR(20) DEFAULT 'DELIVERED',
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    courier_fee DECIMAL(10,2) DEFAULT 0
);

-- Delivery addresses
CREATE TABLE delivery_addresses (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    delivery_sale_id INTEGER REFERENCES delivery_sales(id) ON DELETE CASCADE,
    street VARCHAR(255),
    number VARCHAR(20),
    complement VARCHAR(255),
    neighborhood VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(50),
    postal_code VARCHAR(10),
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6)
);

-- Performance indexes
CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_sales_store_id ON sales(store_id);
CREATE INDEX idx_sales_channel_id ON sales(channel_id);
CREATE INDEX idx_sales_customer_id ON sales(customer_id);
CREATE INDEX idx_sales_status ON sales(sale_status_desc);
CREATE INDEX idx_sales_date_status ON sales(DATE(created_at), sale_status_desc);

CREATE INDEX idx_product_sales_sale_id ON product_sales(sale_id);
CREATE INDEX idx_product_sales_product_id ON product_sales(product_id);
CREATE INDEX idx_product_sales_product_sale ON product_sales(product_id, sale_id);

CREATE INDEX idx_item_product_sales_product_sale_id ON item_product_sales(product_sale_id);
CREATE INDEX idx_item_product_sales_item_id ON item_product_sales(item_id);

CREATE INDEX idx_payments_sale_id ON payments(sale_id);
CREATE INDEX idx_payments_type_id ON payments(payment_type_id);

CREATE INDEX idx_delivery_sales_sale_id ON delivery_sales(sale_id);
CREATE INDEX idx_delivery_addresses_sale_id ON delivery_addresses(sale_id);

-- Insert initial brand
INSERT INTO brands (id, name) VALUES (1, 'Challenge Restaurant Group');

-- This schema supports the complete restaurant analytics platform
-- Ready for 500k+ sales with optimized performance