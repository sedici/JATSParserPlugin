<?php
require_once __DIR__ . '/classes/Processors/ReferencesProcessor.php';

use PKP\components\forms\Processors\ReferencesProcessor;

$refs = [
    'ref1' => 'Messineo, T. (2025, 10 of diciembre). Title [Dataset]. En Source (AC-1312). 66555. www.google.com',
    'ref2' => 'Messineo, T. (2025, 24 de enero). Titulo Article. En Source. www.google.com',
    'ref3' => 'Messineo, T. (24 de enero de 2025). Titulo Patente (Argentina Patent Nos. 12312). En Source (Nos. 12312).',
    'ref4' => 'Messineo, T. (s.f., 10 of diciembre). Titulo Magazine Article. Source, 2, 10-20. www.google.com',
    'ref5' => 'Messineo, T. (s.f., 10 of diciembre). Titulo Newspaper: Part Title. Source, 2, 10-20. www.google.com',
    'ref6' => 'Messineo, T. (2024). Solo una referencia. Publisher Name.',
    'ref7' => 'Messineo, T. (2025). Otra mas para completar la serie. Publisher Name.'
];

$processor = new ReferencesProcessor($refs);
$numbered = $processor->getNumberedReferences();

foreach ($numbered as $id => $val) {
    echo $id . ': ' . $val . "\n";
}
