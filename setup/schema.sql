-- Cyberrose Blog initial schema

CREATE TABLE IF NOT EXISTS cms_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT NULL,
  content LONGTEXT NULL,
  cover_image VARCHAR(512) NULL,
  category_id INT NULL,
  status ENUM('draft','published') DEFAULT 'draft',
  views INT DEFAULT 0,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(512) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  published_at DATETIME NULL,
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES cms_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_post_tags (
  post_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY(post_id, tag_id),
  CONSTRAINT fk_pt_post FOREIGN KEY (post_id) REFERENCES cms_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_tag FOREIGN KEY (tag_id) REFERENCES cms_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  oauth_user_id INT NOT NULL,
  content TEXT NOT NULL,
  status ENUM('pending','approved','spam') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES cms_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_newsletter (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  status ENUM('active','unsubscribed') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','archived') DEFAULT 'new',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_oauth_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(40) DEFAULT 'google',
  provider_sub VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  name VARCHAR(255) NULL,
  picture VARCHAR(512) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  UNIQUE KEY uniq_provider_sub (provider, provider_sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_user_suggestions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  oauth_user_id INT NOT NULL,
  suggestion_type ENUM('topic','feature','feedback') DEFAULT 'topic',
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending','reviewed','rejected') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sug_user FOREIGN KEY (oauth_user_id) REFERENCES cms_oauth_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_settings (
  id INT PRIMARY KEY,
  ads_header_code TEXT NULL,
  ads_inpost_code TEXT NULL,
  ads_midcontent_code TEXT NULL,
  logo_url VARCHAR(512) NULL,
  site_name VARCHAR(255) NULL,
  footer_caption TEXT NULL,
  about_enabled TINYINT(1) DEFAULT 1,
  github_url VARCHAR(512) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO cms_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS cms_admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_editor','editor','viewer') DEFAULT 'editor',
  display_name VARCHAR(120) NULL,
  bio TEXT NULL,
  profile_image VARCHAR(512) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;