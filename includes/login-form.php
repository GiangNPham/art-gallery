<?php if ($error !== ''): ?><p class="error" role="alert"><?= art_h($error) ?></p><?php endif; ?>
<form method="post" action="authenticate.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>User ID: <input name="userid" autocomplete="username" value="<?= art_h($userid) ?>" maxlength="255" required autofocus></label></p>
    <p><label>Password: <input name="password" type="password" autocomplete="current-password" required></label></p>
    <button type="submit">Sign in</button>
</form>