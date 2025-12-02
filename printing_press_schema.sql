

-- admin table: predefined admin (note: password hashed)
CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(150),
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  immutable TINYINT(1) DEFAULT 1 COMMENT 'If 1, admin creds cannot be changed through UI'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- customers
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- services (admin manages)
CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- lettercards (available cards)
CREATE TABLE lettercards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  description TEXT,
  image VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- orders
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  service_id INT,
  qty INT DEFAULT 1,
  details TEXT,
  sample_file VARCHAR(255),
  required_by DATETIME,
  status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- feedbacks
CREATE TABLE feedbacks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  message TEXT,
  rating TINYINT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- contact details (single row admin updates)
CREATE TABLE contact_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  address TEXT,
  phone TEXT,
  email TEXT,
  google_map_embed TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- about images (admin can add/edit images shown on about page)
CREATE TABLE about_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200),
  image VARCHAR(255),
  caption VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- insert a contact_details placeholder row so admin can edit
INSERT INTO contact_details (address, phone, email, google_map_embed) VALUES ('Your address here','+91-0000000000','info@yourdomain.com','');

-- create predefined admin (username + password)
-- Password: Admin@123  (hashed below, you can change before deploying)
INSERT INTO admin_users (username, display_name, password, immutable)
VALUES ('Thripura@printingpress.com', 'Super Admin', '$2y$10$P1gZqvJ3g1kzRZ0Sg3K0pOv1F5Kf2WQPXQwz6lR4yGmXbJp2qYb1K', 1);
-- The hash is password_hash('Admin@123', PASSWORD_BCRYPT)
