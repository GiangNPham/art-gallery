# Genres: draft technical documentation

Author: [enter your name and student ID]  
Team: [enter team number]

## Scope and work list

Implemented: list genres, select a genre to view details, add a genre, and confirm/delete an unused genre. Update and ad hoc search are excluded as crossed out in the assignment. Code is implemented; database/browser validation and screenshot evidence remain pending. This document covers only the Genres module.

## Modules and user interface

| File | Responsibility |
| --- | --- |
| genres.php | Alphabetical table of names, descriptions and painting counts; name opens details; add/delete links |
| genre-detail.php | ID, name, description, safe reference link and linked painting titles |
| genre-add.php | Required name, optional description and reference URL; validation errors preserve input |
| genre-delete.php | Confirmation form; blocks deletion when existing paintings use the genre |
| includes/app.php | Existing login, database, layout, escaping and CSRF helpers; adds Genres navigation |

The existing painting-genre pages remain available. They also operate on Genres; they do not create or remove PaintingGenres relationships.

## Data dictionary

Source: sql/art-small.sql, Genres table. No schema migration is required.

| Column | Database definition | Application behavior |
| --- | --- | --- |
| GenreID | INTEGER, auto increment, primary key | Database assigns ID; users cannot edit it |
| GenreName | VARCHAR(50), required, unique | Trimmed, 1–50 characters; duplicates rejected |
| Description | LONGTEXT, nullable | Optional; form limits input to 10,000 characters |
| Link | VARCHAR(255), nullable | Optional HTTP/HTTPS URL, maximum 255 characters |

PaintingGenres associates GenreID with PaintingID. List/detail queries join Paintings so counts and links only include existing paintings. Deletion checks these associations again inside a transaction, locks records, and refuses to delete a genre used by a painting. It removes stale associations before deleting an unused genre. Adding a genre does not accept foreign keys.

## Use cases

All actions require an authenticated customer. Unauthenticated requests redirect to sign in.

1. View: select Genres in navigation. The server queries Genres and painting counts, then renders the list, including an empty-state message when appropriate.
2. Details: select a genre name. The server validates the ID, fetches the genre and linked paintings, and renders details. Invalid IDs return 400; missing records return 404.
3. Add: select ADD genre, enter fields, and submit. The server checks the CSRF token and validates fields, then inserts with a prepared statement. Success redirects to the list with a message. Invalid fields or a duplicate name return 422 with errors. Cancel returns without writing.
4. Delete: select Delete. GET shows a confirmation without deleting. If paintings use the genre, the page explains why deletion is unavailable. A confirmed POST checks CSRF and repeats the association check in a transaction. Successful deletion redirects to the list. A direct POST for a used genre returns 409. Keep genre returns without writing.

## Test cases and run log

Start Apache/MySQL in XAMPP, import the database as described in README.md, then sign in. Use a disposable genre named `Genre Test 4485`. Do not delete original painting records for testing.

For database snapshots, run in phpMyAdmin before and after each relevant action:

```sql
SELECT GenreID, GenreName, Description, Link FROM Genres ORDER BY GenreID;
SELECT pg.* FROM PaintingGenres pg
JOIN Genres g ON g.GenreID = pg.GenreID
WHERE g.GenreName = 'Genre Test 4485';
```

| Test | Steps | Expected result | Actual result |
| --- | --- | --- | --- |
| List | Open Genres after sign in | Alphabetical rows and painting counts | Pending |
| Details | Select an existing name | All genre fields and matching painting links | Pending |
| Add | Add test name, description and https://example.com | One new row, generated ID, success message | Pending |
| Duplicate | Submit the same test name again | Error; no second row | Pending |
| Validation | Submit blank name; then invalid URL; then oversized values using browser developer tools | Errors; no new row | Pending |
| Output escaping | Use a description containing an HTML script tag on the test genre | No script executes | Pending |
| Cancel delete | Open test genre deletion, select Keep genre | Row remains | Pending |
| Delete unused | Confirm deletion of test genre | Genre removed; original paintings unchanged | Pending |
| Delete used | Select Delete on an original genre with paintings | Blocked; genre/paintings remain | Pending |
| Invalid ID | Open genre-detail.php?id=abc and genre-detail.php?id=2147483647 | 400 and 404 respectively, assuming last ID absent | Pending |
| CSRF | Alter hidden token before submitting add/delete | 403; database unchanged | Pending |
| Login | Sign out and open genres.php | Redirect to login | Pending |

Local automated check: PHP syntax checks passed for all four genre pages and includes/app.php. Database execution was not tested: local MySQL connection was refused. Record actual outcomes and dates after running the cases; do not treat expected outcomes as completed test results.

## Submission evidence still to collect

Capture each of the four page interfaces, form validation errors, successful add/delete messages, blocked deletion, and database before/after snapshots. Insert screenshots alongside the corresponding run-log entries. Add author/team details and a table of contents in the final document. Include the four PHP source listings and the Genres CREATE TABLE statement from sql/art-small.sql with the submission.
