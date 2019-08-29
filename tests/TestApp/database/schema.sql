-- This is a single line comment
# This also is a single line comment

CREATE TABLE `authors` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(80) NOT NULL,
  created DATETIME NOT NULL,
  modified DATETIME NOT NULL
);

CREATE TABLE `posts` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(80) NOT NULL,
  body TEXT,
  published TINYINT(1),
  created DATETIME NOT NULL,
  modified DATETIME NOT NULL
);