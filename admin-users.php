<?php
// Task 10: admin user management. Change an account's type (client/admin) or disable/enable it

require_once __DIR__ . '/includes/app.php';
art_require_admin();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'POST'], true)) {
    header('Allow: GET, HEAD, POST');
    art_error(405, 'Method not allowed', 'Use the buttons on the user list.');
}
$db = art_db();
$me = art_customer()['customer_id'];
const USERS_PER_PAGE = 20;

$search = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$page = isset($_GET['page']) ? (filter_var($_GET['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1) : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $search = mb_substr(art_post('q'), 0, 100);
    $page = filter_var(art_post('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
    $target = filter_var(art_post('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = art_post('action');
    $value = art_post('value');
    if (!$target) art_error(400, 'Invalid user', 'Choose a user from the list.');
    if ($target === $me) art_error(400, 'Not allowed', 'You cannot change your own account here, so you cannot lock yourself out.');
    $stmt = $db->prepare('SELECT UserName FROM CustomerLogon WHERE CustomerID = ?');
    $stmt->bind_param('i', $target);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) art_error(404, 'User not found', 'That account no longer exists.');
    if ($action === 'role' && in_array($value, ['client', 'admin'], true)) {
        $stmt = $db->prepare('UPDATE CustomerLogon SET Role = ?, DateLastModified = NOW() WHERE CustomerID = ?');
        $stmt->bind_param('si', $value, $target);
        $stmt->execute();
        $message = $found['UserName'] . ' is now ' . ($value === 'admin' ? 'an admin.' : 'a client.');
    } elseif ($action === 'state' && in_array($value, ['0', '1'], true)) {
        $state = (int) $value;
        $stmt = $db->prepare('UPDATE CustomerLogon SET State = ?, DateLastModified = NOW() WHERE CustomerID = ?');
        $stmt->bind_param('ii', $state, $target);
        $stmt->execute();
        $message = $found['UserName'] . ($state ? ' was enabled.' : ' was disabled and can no longer sign in.');
    } else {
        art_error(400, 'Unknown request', 'Use the buttons on the user list.');
    }
    $_SESSION['flash'] = $message;
    header('Location: admin-users.php?' . http_build_query(['q' => $search, 'page' => $page]), true, 303);
    exit;
}

$where = "(? = '' OR l.UserName LIKE CONCAT('%', ?, '%') OR c.FirstName LIKE CONCAT('%', ?, '%')
    OR c.LastName LIKE CONCAT('%', ?, '%') OR c.Email LIKE CONCAT('%', ?, '%'))";
$stmt = $db->prepare('SELECT COUNT(*) FROM CustomerLogon l LEFT JOIN Customers c ON c.CustomerID = l.CustomerID WHERE ' . $where);
$stmt->bind_param('sssss', $search, $search, $search, $search, $search);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_column();
$pages = max(1, (int) ceil($total / USERS_PER_PAGE));
$page = min($page, $pages);
$offset = ($page - 1) * USERS_PER_PAGE;
$limit = USERS_PER_PAGE;
$stmt = $db->prepare('SELECT l.CustomerID, l.UserName, l.Role, l.State, l.DateJoined, c.FirstName, c.LastName, c.Email
    FROM CustomerLogon l LEFT JOIN Customers c ON c.CustomerID = l.CustomerID WHERE ' . $where . '
    ORDER BY l.CustomerID LIMIT ? OFFSET ?');
$stmt->bind_param('sssssii', $search, $search, $search, $search, $search, $limit, $offset);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function user_button(int $id, string $action, string $value, string $label, string $search, int $page): void
{
    echo '<form method="post" action="admin-users.php" class="logout-form">'
        . '<input type="hidden" name="csrf" value="' . art_h(art_token()) . '">'
        . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="action" value="' . art_h($action) . '">'
        . '<input type="hidden" name="value" value="' . art_h($value) . '"><input type="hidden" name="q" value="' . art_h($search) . '">'
        . '<input type="hidden" name="page" value="' . $page . '"><button type="submit">' . art_h($label) . '</button></form> ';
}

art_header('User management');
?>
<p><a href="admin.php">&larr; Admin home</a></p>
<form class="filters" method="get" action="admin-users.php">
    <label for="q">Search: <input id="q" name="q" value="<?= art_h($search) ?>" maxlength="100" placeholder="user ID, name or email"></label>
    <button type="submit">Search</button><a href="admin-users.php">Clear</a>
</form>
<p>Accounts shown <strong><?= number_format($total) ?></strong></p>
<table>
    <thead><tr><th>ID</th><th>User ID</th><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): $uid = (int) $user['CustomerID']; ?>
        <tr>
            <td><?= $uid ?></td>
            <td><?= art_h($user['UserName']) ?></td>
            <td><?= art_h(trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''))) ?></td>
            <td><?= art_h($user['Email'] ?? '') ?></td>
            <td><?= $user['Role'] === 'admin' ? 'Admin' : 'Client' ?></td>
            <td><?= (int) $user['State'] === 1 ? 'Active' : 'Disabled' ?></td>
            <td><?= art_h($user['DateJoined'] ? substr((string) $user['DateJoined'], 0, 10) : '') ?></td>
            <td>
                <?php if ($uid === $me): ?>
                    <em>(you)</em>
                <?php else: ?>
                    <?php user_button($uid, 'role', $user['Role'] === 'admin' ? 'client' : 'admin', $user['Role'] === 'admin' ? 'Make client' : 'Make admin', $search, $page); ?>
                    <?php user_button($uid, 'state', (int) $user['State'] === 1 ? '0' : '1', (int) $user['State'] === 1 ? 'Disable' : 'Enable', $search, $page); ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$users): ?><p>No accounts found.</p><?php endif; ?>
<?php if ($pages > 1): ?>
    <p>Page <?= $page ?> of <?= $pages ?>
        <?php if ($page > 1): ?> | <a href="admin-users.php?<?= art_h(http_build_query(['q' => $search, 'page' => $page - 1])) ?>">Previous</a><?php endif; ?>
        <?php if ($page < $pages): ?> | <a href="admin-users.php?<?= art_h(http_build_query(['q' => $search, 'page' => $page + 1])) ?>">Next</a><?php endif; ?>
    </p>
<?php endif; ?>
<?php art_footer(); ?>