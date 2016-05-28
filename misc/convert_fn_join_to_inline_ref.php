<?php

// find blueprints -type f -iname '*.template' -exec php vendor/aoepeople/stackformation/misc/convert_fn_join_to_inline_ref.php '{}' \;

$file = $argv[1];

if (!is_file($file)) {
    echo "File not found"; exit(1);
}

$json = file_get_contents($file);

$json = preg_replace_callback(
    '/{\s*"Fn::Join"\s*:\s*\[\s*"(.*)"\s*,\s*(\[.*\])\s*\]\s*}/sU',
    function ($matches) {
        $delimiter = $matches[1];
        $pieces = json_decode($matches[2], true);
        $newPieces = [];
        foreach ($pieces as $piece) {
            if (is_array($piece) && isset($piece['Ref'])) {
                $newPieces[] = '{Ref:'.$piece['Ref'].'}';
            } elseif (strpos($piece, "\n") !== false) {
                // don't touch lines with line breaks (like used in UserData)
                return $matches[0];
            } else {
                $newPieces[] = $piece;
            }
        }
        return '"' . implode($delimiter, $newPieces) . '"';
    },
    $json
);

echo "Writing file $file\n";
file_put_contents($file, $json);