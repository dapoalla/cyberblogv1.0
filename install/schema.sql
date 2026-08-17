-- CyberBlog v2.0 database schema
-- Applied automatically by install/index.php; safe to re-run (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS cms_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_editor','editor','viewer') NOT NULL DEFAULT 'editor',
  display_name VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  profile_image VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_oauth_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider VARCHAR(50) NOT NULL DEFAULT 'google',
  provider_id VARCHAR(255) NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  name VARCHAR(255) DEFAULT NULL,
  picture VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_provider_user (provider, provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  excerpt TEXT DEFAULT NULL,
  content_html LONGTEXT DEFAULT NULL,
  cover_image VARCHAR(500) DEFAULT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  status ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  published_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  meta_title VARCHAR(255) DEFAULT NULL,
  meta_description VARCHAR(500) DEFAULT NULL,
  og_image VARCHAR(500) DEFAULT NULL,
  related_post_ids VARCHAR(255) DEFAULT NULL,
  author_name VARCHAR(255) DEFAULT NULL,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_slug (slug),
  KEY idx_category (category_id),
  KEY idx_status (status),
  KEY idx_published_at (published_at),
  KEY idx_author_name (author_name),
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES cms_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_settings (
  id INT UNSIGNED NOT NULL,
  ads_header_code TEXT DEFAULT NULL,
  ads_inpost_code TEXT DEFAULT NULL,
  ads_midcontent_code TEXT DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO cms_settings (id, ads_header_code, ads_inpost_code, ads_midcontent_code) VALUES (1, '', '', '');

CREATE TABLE IF NOT EXISTS cms_comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  oauth_user_id INT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  status ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_post (post_id),
  KEY idx_oauth_user (oauth_user_id),
  KEY idx_status (status),
  KEY idx_created_at (created_at),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES cms_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (oauth_user_id) REFERENCES cms_oauth_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_newsletter (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_contacts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','archived') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_status (status),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_user_suggestions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  oauth_user_id INT UNSIGNED NOT NULL,
  suggestion_type ENUM('topic','feature','feedback') NOT NULL DEFAULT 'topic',
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending','approved','rejected','reviewed') NOT NULL DEFAULT 'pending',
  admin_notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_oauth_user (oauth_user_id),
  KEY idx_status (status),
  KEY idx_created_at (created_at),
  CONSTRAINT fk_suggestions_user FOREIGN KEY (oauth_user_id) REFERENCES cms_oauth_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
