# This is where your database schema goes

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  dob DATE DEFAULT NULL,
  created DATETIME NOT NULL,
  modified DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE bookmarks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(50) DEFAULT NULL,
  description TEXT,
  url TEXT,
  category VARCHAR(80) DEFAULT NULL,
  created DATETIME NOT NULL,
  modified DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) DEFAULT NULL,
  created DATETIME NOT NULL,
  modified DATETIME NOT NULL,
  UNIQUE KEY title (title)
) ENGINE=InnoDB;

CREATE TABLE bookmarks_tags (
  bookmark_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (bookmark_id,tag_id),
  FOREIGN KEY (tag_id) REFERENCES tags (id),
  FOREIGN KEY (bookmark_id) REFERENCES bookmarks (id)
) ENGINE=InnoDB;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `dob`, `created`, `modified`)
VALUES
	(1,'Demo User','demo@example.com','$2y$10$/clqxdb.aWe43VXDUn8tA.yxKbWHZT3rN7gqITFaj32PZHI3.DkzW','1999-12-28','2018-12-28 15:24:13','2018-12-28 15:24:13'),
	(2,'Guest','guest@example.com','$2y$10$SyT90usSvle3Ao1ofz/qVOoeqd8lVcUG1kCO0/8lNZCTXRnHhQZ/W','1999-12-28', '2018-12-28 15:24:13','2018-12-28 15:24:13');

INSERT INTO `bookmarks` (`id`, `user_id`, `title`, `description`, `url`, `category`, `created`, `modified`)
VALUES
	(1,1,'OriginPHP','The PHP framework for rapidly building scalable web applications.','https://www.originphp.com','Computing','2018-12-28 15:25:34','2018-12-29 09:54:31'),
	(2,1,'Google','The search engine.','https://www.google.com',NULL,'2018-12-28 15:25:41','2018-12-29 09:16:04');

INSERT INTO `tags` (`id`, `title`, `created`, `modified`)
VALUES
	(1, 'Framework', '2019-03-07 18:45:43', '2019-03-07 18:45:43'),
	(2, 'PHP', '2019-03-07 18:45:43', '2019-03-07 18:45:43'),
	(3, 'Search Engine', '2019-03-07 18:45:55', '2019-03-07 18:45:55');

INSERT INTO `bookmarks_tags` (`bookmark_id`, `tag_id`)
VALUES
	(1, 1),
	(1, 2),
	(2, 3);
