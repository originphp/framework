-- Create the actual database
CREATE DATABASE origin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE origin_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Give access to a user
GRANT ALL ON origin.* TO 'origin' IDENTIFIED BY 'secret';
GRANT ALL ON origin_test.* TO 'origin' IDENTIFIED BY 'secret';

USE origin;

FLUSH PRIVILEGES;
