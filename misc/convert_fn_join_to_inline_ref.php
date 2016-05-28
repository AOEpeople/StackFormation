<?php

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
            } else {
                $newPieces[] = $piece;
            }
        }
        return '"' . implode($delimiter, $newPieces) . '"';
    },
    $json
);

echo "Writing file $file";
file_put_contents($file, $json);