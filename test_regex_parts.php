<?php
$refs = [
    'ref1' => 'Messineo, T. (2025, 10 of diciembre). Title [Dataset]. En Source (AC-1312). 66555. www.google.com',
    'ref2' => 'Messineo, T. (2025, 10 of diciembre). Titulo Article. En Source. www.google.com',
    'ref3' => 'Messineo, T. (2025). Titulo Patente (Argentina Patent Nos. 12312). En Source (Nos. 12312).',
    'ref4' => 'Messineo, T. (s.f., 10 of diciembre). Titulo Magazine Article. Source, 2, 10-20. www.google.com',
    'ref5' => 'Messineo, T. (s.f., 10 of diciembre). Titulo Newspaper: Part Title. Source, 2, 10-20. www.google.com',
    'ref6' => 'Messineo, T. (2024). Solo una referencia. Publisher Name.'
];

$groupedByPrefix = [];

foreach ($refs as $id => $ref) {
    $start = strpos($ref, '(');
    $prefix = '';
    if ($start !== false) {
        $prefix = substr($ref, 0, $start + 1);
        echo "Before cleanup prefix: " . $prefix . "\n";
        $prefix = preg_replace('/\s{2,}/', ' ', trim($prefix));
        echo "After cleanup prefix: " . $prefix . "\n";
        $groupedByPrefix[$prefix][] = ['id' => $id, 'ref' => $ref];
    }
}

$numberedRefs = [];
foreach ($groupedByPrefix as $prefix => $references) {
    $letter = 'a';
    if (count($references) > 1) {
        foreach ($references as $refData) {
            $id = $refData['id'];
            $ref = $refData['ref'];
            $start = strpos($ref, '(');
            $modifiedRef = $ref;
            if ($start !== false) {
                $afterParen = substr($ref, $start + 1);
                if (preg_match('/^(\d{4}|[sS]\.[fF]\.)/', $afterParen, $matches)) {
                    $yearMatch = $matches[1];
                    $insertPos = $start + 1 + strlen($yearMatch);
                    $modifiedRef = substr_replace($ref, $letter, $insertPos, 0);
                }
            }
            $numberedRefs[$id] = $modifiedRef;
            $letter++;
        }
    } else {
        $refData = $references[0];
        $numberedRefs[$refData['id']] = $refData['ref'];
    }
}

print_r($numberedRefs);
