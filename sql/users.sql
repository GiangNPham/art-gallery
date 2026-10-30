-- Part 4: required customer accounts, using the original Lab14 tables.
-- Applies only to user1..user4 and their linked Customers records.
-- Rerunning restores these four assignment credentials; other accounts are preserved.
USE `art`;
START TRANSACTION;
SET @part4_next_id = GREATEST((SELECT COALESCE(MAX(CustomerID), 0) FROM Customers), (SELECT COALESCE(MAX(CustomerID), 0) FROM CustomerLogon));

SET @part4_id = COALESCE((SELECT CustomerID FROM CustomerLogon WHERE UserName = 'user1' LIMIT 1), @part4_next_id + 1);
INSERT INTO Customers (CustomerID, FirstName, LastName) VALUES (@part4_id, 'first1', 'last1')
ON DUPLICATE KEY UPDATE FirstName = VALUES(FirstName), LastName = VALUES(LastName);
INSERT INTO CustomerLogon (CustomerID, UserName, Pass, Salt, State, DateJoined, DateLastModified)
VALUES (@part4_id, 'user1', '$2y$10$uvpm4l9WkKeCnPrTNc71quGKrb420D50wweWo.3Yu45vh4TqsZ83W', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE UserName = VALUES(UserName), Pass = VALUES(Pass), Salt = '', State = 1, DateLastModified = NOW();
SET @part4_next_id = GREATEST(@part4_next_id, @part4_id);

SET @part4_id = COALESCE((SELECT CustomerID FROM CustomerLogon WHERE UserName = 'user2' LIMIT 1), @part4_next_id + 1);
INSERT INTO Customers (CustomerID, FirstName, LastName) VALUES (@part4_id, 'first2', 'last2')
ON DUPLICATE KEY UPDATE FirstName = VALUES(FirstName), LastName = VALUES(LastName);
INSERT INTO CustomerLogon (CustomerID, UserName, Pass, Salt, State, DateJoined, DateLastModified)
VALUES (@part4_id, 'user2', '$2y$10$kf.akBxDdZwhgpFBviHTaOR8DDbiublDK1iDHr8jFFes5NLRNNnmW', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE UserName = VALUES(UserName), Pass = VALUES(Pass), Salt = '', State = 1, DateLastModified = NOW();
SET @part4_next_id = GREATEST(@part4_next_id, @part4_id);

SET @part4_id = COALESCE((SELECT CustomerID FROM CustomerLogon WHERE UserName = 'user3' LIMIT 1), @part4_next_id + 1);
INSERT INTO Customers (CustomerID, FirstName, LastName) VALUES (@part4_id, 'first3', 'last3')
ON DUPLICATE KEY UPDATE FirstName = VALUES(FirstName), LastName = VALUES(LastName);
INSERT INTO CustomerLogon (CustomerID, UserName, Pass, Salt, State, DateJoined, DateLastModified)
VALUES (@part4_id, 'user3', '$2y$10$IKovOZNtO4I5RLvnhbxfU.xfu4U45lDenWXjoSlTole2jV4dPO9oG', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE UserName = VALUES(UserName), Pass = VALUES(Pass), Salt = '', State = 1, DateLastModified = NOW();
SET @part4_next_id = GREATEST(@part4_next_id, @part4_id);

SET @part4_id = COALESCE((SELECT CustomerID FROM CustomerLogon WHERE UserName = 'user4' LIMIT 1), @part4_next_id + 1);
INSERT INTO Customers (CustomerID, FirstName, LastName) VALUES (@part4_id, 'first4', 'last4')
ON DUPLICATE KEY UPDATE FirstName = VALUES(FirstName), LastName = VALUES(LastName);
INSERT INTO CustomerLogon (CustomerID, UserName, Pass, Salt, State, DateJoined, DateLastModified)
VALUES (@part4_id, 'user4', '$2y$10$xxkwFo30aqg.4wuCrXkw.u2OgTbwS./Q3FkXMpnPFNYIX8p9rjFbO', '', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE UserName = VALUES(UserName), Pass = VALUES(Pass), Salt = '', State = 1, DateLastModified = NOW();
SET @part4_next_id = GREATEST(@part4_next_id, @part4_id);
COMMIT;
