DROP TABLE IF EXISTS articles,articles_tags,comments,groups,profiles,tags,users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created DATETIME,
    modified DATETIME
);
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    created DATETIME,
    modified DATETIME
);
CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    created DATETIME,
    modified DATETIME
);
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    body TEXT,
    published BOOLEAN DEFAULT FALSE,
    created DATETIME,
    modified DATETIME
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    body TEXT,
    created DATETIME,
    modified DATETIME
);
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(191),
    created DATETIME,
    modified DATETIME,
    UNIQUE KEY (title)
) CHARSET=utf8mb4;
CREATE TABLE articles_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL
);
INSERT INTO profiles (user_id,name, created, modified)
VALUES
(1,'Admin', NOW(), NOW());
INSERT INTO profiles (user_id,name, created, modified)
VALUES
(2,'Standard', NOW(), NOW());
INSERT INTO users (name, email, password, created, modified)
VALUES
('James','james@example.com', 'secret1', NOW(), NOW());
INSERT INTO users ( name, email, password, created, modified)
VALUES
('Amanda','amanda@example.com', 'secret2', NOW(), NOW());
INSERT INTO articles (user_id, title, slug, body, published, created, modified)
VALUES
(1, 'First Post', 'first-post', 'This is the first post.', 1, now(), now());
INSERT INTO articles (user_id, title, slug, body, published, created, modified)
VALUES
(2, 'Second Post', 'second-post', 'This is the second post.', 0, now(), now());
INSERT INTO articles (user_id, title, slug, body, published, created, modified)
VALUES
(3, 'Third Post', 'third-post', 'This is the third post.', 0, now(), now());
INSERT INTO groups (name, created, modified)
VALUES
('Male', NOW(), NOW());
INSERT INTO groups (name, created, modified)
VALUES
('Female', NOW(), NOW());
INSERT INTO comments (article_id,body, created, modified)
VALUES
(1,'This is a comment', NOW(), NOW());
INSERT INTO comments (article_id,body, created, modified)
VALUES
(1,'This is another comment', NOW(), NOW());
INSERT INTO comments (article_id,body, created, modified)
VALUES
(1,'it is getting boring', NOW(), NOW());
INSERT INTO tags (title, created, modified)
VALUES
('featured', NOW(), NOW());
INSERT INTO tags (title, created, modified)
VALUES
('new', NOW(), NOW());
INSERT INTO articles_tags (article_id,tag_id)
VALUES
(1,1);
INSERT INTO articles_tags (article_id,tag_id)
VALUES
(1,2);
INSERT INTO articles_tags (article_id,tag_id)
VALUES
(2,2);
