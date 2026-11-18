-- Task 10: user roles
USE `art`;
ALTER TABLE CustomerLogon ADD COLUMN IF NOT EXISTS Role VARCHAR(10) NOT NULL DEFAULT 'client';

-- existing accounts becomes client; user1 is admin account
UPDATE CustomerLogon SET Role = 'client' WHERE Role NOT IN ('client', 'admin');
UPDATE CustomerLogon SET Role = 'admin' WHERE UserName = 'user1';