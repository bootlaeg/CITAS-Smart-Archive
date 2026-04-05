<?php
$name = 'Andi Muh Reza B Makkaraka';
$patterns = [
    '/^[A-Z][a-z]+([-][A-Z]([a-z]+|\s|$))*$/i' => 'Current pattern',
    '/^[A-Z][a-z]+(\s+[A-Z])*(\s+[A-Z][a-z]+)*$/i' => 'Alt 1: capitals with words',
    '/^[A-Za-z ]+$/' => 'Simple: letters and spaces only',
    '/^[A-Z][a-zA-Z\s-]+$/' => 'Alt 2: capitals to start, then letters/spaces',
];

echo "Testing against: \"$name\"\n\n";

foreach ($patterns as $pattern => $desc) {
    if (preg_match($pattern, $name)) {
        echo "✓ MATCH: $desc\n";
        echo "  Pattern: $pattern\n";
    } else {
        echo "✗ NO match: $desc\n";
    }
}
?>
