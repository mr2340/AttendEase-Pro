<?php
$hash = password_hash('password123', PASSWORD_BCRYPT);
file_put_contents('hash.txt', $hash);
echo "Hash saved!";
