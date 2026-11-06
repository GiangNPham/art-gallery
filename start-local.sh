#!/bin/sh
set -eu

cd "$(dirname "$0")"

XAMPP=/Applications/XAMPP/xamppfiles
PHP="$XAMPP/bin/php"

if [ ! -x "$PHP" ]; then
    echo "XAMPP PHP was not found at $PHP. Install XAMPP first." >&2
    exit 1
fi

# Each Mac account gets its own private, writable session folder.
umask 077
SESSION_DIR="${TMPDIR:-/tmp}/artwork-sessions-$(id -u)"
mkdir -p "$SESSION_DIR"
if [ ! -w "$SESSION_DIR" ]; then
    echo "Cannot write sessions to $SESSION_DIR." >&2
    exit 1
fi

echo "Starting XAMPP MySQL (your Mac password may be requested)..."
sudo "$XAMPP/xampp" startmysql

"$PHP" -r '
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$config = require "config.php";
try {
    $db = new mysqli($config["host"], $config["username"], $config["password"], $config["database"], $config["port"]);
    $db->query("SELECT 1 FROM CustomerLogon LIMIT 1");
} catch (mysqli_sql_exception $error) {
    fwrite(STDERR, "Database check failed. Check config.php and the README database setup instructions.\n");
    exit(1);
}
'

echo "Open http://localhost:8000/art-login.php"
echo "Keep this Terminal open. Press Control+C to stop the website."
exec "$PHP" -d "session.save_path=$SESSION_DIR" -S localhost:8000 -t .
