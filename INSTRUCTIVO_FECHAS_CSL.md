# Instructivo: Inyección de Formato de Fecha en CSL

## 1. Introducción
Esta funcionalidad permite que las referencias bibliográficas generadas por el plugin `JatsParser` respeten el formato de fecha corta configurado en la revista OJS. Anteriormente, el formato de fecha estaba fijo en el archivo CSL, ignorando las preferencias de la revista y el idioma del artículo.

## 2. Configuración en OJS (Guía de Usuario)
Para modificar el formato en el que aparecen las fechas en las referencias bibliográficas, el gestor de la revista debe seguir estos pasos en el panel de administración:

1.  Ir a **Sitio Web** > **Configuración** > **Fecha y hora**.
2.  Buscar el campo **Formato de fecha (corta)**.
3.  Ingresar el formato deseado.
    *   **Ejemplos comunes:**
        *   `d-m-Y`  → 20-01-2025
        *   `d.m.Y`  → 20.01.2025
        *   `d de F de Y` → 20 de enero de 2025
    *   **Tokens disponibles:**
        *   `d`: Día con cero inicial (01-31)
        *   `j`: Día sin cero inicial (1-31)
        *   `m`: Mes con cero inicial (01-12)
        *   `n`: Mes sin cero inicial (1-12)
        *   `M`: Nombre corto del mes (ene, feb)
        *   `F`: Nombre completo del mes (enero, febrero)
        *   `Y`: Año (2025)

**Nota sobre Idiomas:** El sistema respeta el idioma **del artículo (envío)**. Esto es fundamental para garantizar la coherencia en revistas multilingües:
*   Si un artículo está configurado en **Inglés ("en")**, el sistema utilizará:
    1.  El formato de fecha corta definido para Inglés en OJS.
    2.  Los nombres de meses en Inglés ("January" en lugar de "Enero").
*   Si un artículo está en **Español ("es")**, utilizará el formato y nombres en Español.

Esto ocurre independientemente del idioma en el que el administrador esté visualizando el panel de control. Se toma siempre el `Locale Key` del artículo/envío.

---

## 3. Implementación Técnica

### Resumen de la Solución
Se implementó un mecanismo que intercepta la generación de citas en CSL.
1.  **Orquestación:** Se modificó `JatsParserPlugin` para obtener el formato de fecha basado en el *locale* del envío (submission) y pasarlo al `Document`.
2.  **Lógica:** Se creó una nueva clase `DateFormatter` que recibe este formato, lo parsea (soportando formatos PHP y `strftime` de OJS), y lo convierte a XML de CSL (`<date-part>`).
3.  **Inyección:** Se sustituye dinámicamente la macro de fecha en el archivo `.csl` antes de procesarlo con `citeproc-php`.

### Archivos Modificados / Creados

#### A. Nueva Clase: `src/JATSParser/HTML/CSL/DateFormatter.php`
Esta clase contiene toda la lógica de conversión. Maneja la detección inteligente de palabras como "de" u "of" (para no tratarlas como códigos de fecha) y la asignación correcta de separadores (puntos, guiones) según su posición relativa al año.

```php
<?php
namespace JATSParser\HTML\CSL;

class DateFormatter {

    /**
     * Injects the OJS date format into the CSL content.
     */
    public function injectOJSDateFormat(string $cslContent, string $dateFormat): string {
        $generatedDateXml = $this->mapPhpDateToCsl($dateFormat);
        
        // Log para depuración
        error_log("DateFormatter: Generated CSL Date XML: " . $generatedDateXml);

        // Construye el grupo de fecha CSL
        $newMacroContent = '<group delimiter=" " prefix="(" suffix=")">' .
                           '<choose>' .
                           '<if variable="issued">' .
                           '<date variable="issued">' . $generatedDateXml . '</date>' .
                           '</if>' .
                           '<else>' .
                           '<text term="no date" form="short"/>' .
                           '<text variable="year-suffix" prefix="-"/>' .
                           '</else>' .
                           '</choose>' .
                           '</group>';

        // Reemplaza la macro existente 'date-bib'
        $pattern = '/<macro name="date-bib">.*?<\/macro>/s';
        $replacement = '<macro name="date-bib">' . $newMacroContent . '</macro>';

        return preg_replace($pattern, $replacement, $cslContent);
    }

    private function mapPhpDateToCsl(string $format): string {
        $xml = '';
        
        // Soporte para formatos strftime de OJS (ej. %Y-%m-%d)
        if (strpos($format, '%') !== false) {
            $strftimeMap = [
                '%Y' => 'Y', '%y' => 'y', 
                '%m' => 'm', '%d' => 'd', '%e' => 'j',
                '%B' => 'F', '%b' => 'M', '%h' => 'M',
            ];
            $format = strtr($format, $strftimeMap);
            $format = str_replace('%', '', $format);
        }

        $tokens = [];
        $length = strlen($format);
        $buffer = '';
        
        $validTokens = ['d', 'j', 'm', 'n', 'F', 'M', 'Y', 'y'];

        // Tokenización con Heurística para palabras "de", "of", etc.
        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];
            if ($char === '\\') { // Escape manual
                if ($i + 1 < $length) { $buffer .= $format[$i + 1]; $i++; }
                continue;
            }

            if (in_array($char, $validTokens)) {
                // Si un token (ej. 'd') está seguido inmediatamente de una letra,
                // asumimos que es parte de una palabra (ej. "de") y NO una fecha.
                $isToken = true;
                if ($i + 1 < $length) {
                    $nextChar = $format[$i + 1];
                    if (preg_match('/[a-zA-Z]/', $nextChar) && !in_array($nextChar, $validTokens)) {
                        $isToken = false;
                    }
                }

                if ($isToken) {
                    if ($buffer !== '') {
                        $tokens[] = ['type' => 'delim', 'value' => $buffer];
                        $buffer = '';
                    }
                    $tokens[] = ['type' => 'part', 'value' => $char];
                } else {
                    $buffer .= $char;
                }
            } else {
                $buffer .= $char;
            }
        }
        if ($buffer !== '') {
            $tokens[] = ['type' => 'delim', 'value' => $buffer];
        }

        // Lógica "Pivot by Year": Asignar delimitadores
        // - Antes del Año: Son SUFIJOS del elemento anterior.
        // - Después del Año: Son PREFIJOS del elemento siguiente.
        // - El Año no lleva delimitadores pegados.
        $yearIndex = -1;
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i]['type'] === 'part' && ($tokens[$i]['value'] === 'Y' || $tokens[$i]['value'] === 'y')) {
                $yearIndex = $i;
                break;
            }
        }

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if ($token['type'] === 'part') {
                $prefix = '';
                $suffix = '';

                if ($yearIndex > -1) {
                    if ($i < $yearIndex) {
                        // Antes del año: Delimitador siguiente es Sufijo
                        if (isset($tokens[$i + 1]) && $tokens[$i + 1]['type'] === 'delim') {
                            $suffix = $tokens[$i + 1]['value'];
                        }
                        if ($i === 1 && $tokens[0]['type'] === 'delim') $prefix = $tokens[0]['value'];
                    } elseif ($i > $yearIndex) {
                        // Después del año: Delimitador anterior es Prefijo
                        if ($i > 0 && $tokens[$i - 1]['type'] === 'delim') {
                            $prefix = $tokens[$i - 1]['value'];
                        }
                        if ($i === count($tokens) - 2 && $tokens[$i + 1]['type'] === 'delim') $suffix = $tokens[$i + 1]['value'];
                    } else {
                        // Año: chequeo bordes extremos
                        if ($i === 1 && $tokens[0]['type'] === 'delim') $prefix = $tokens[0]['value'];
                        if ($i === count($tokens) - 2 && $tokens[$i + 1]['type'] === 'delim') $suffix = $tokens[$i + 1]['value'];
                    }
                } else {
                    // Fallback
                    if (isset($tokens[$i + 1]) && $tokens[$i + 1]['type'] === 'delim') $suffix = $tokens[$i + 1]['value'];
                }

                $char = $token['value'];
                // Mapeo a XML de CSL
                switch ($char) {
                    case 'd': $xml .= $this->createDatePartXml('day', 'numeric-leading-zeros', $prefix, $suffix); break;
                    case 'j': $xml .= $this->createDatePartXml('day', 'numeric', $prefix, $suffix); break;
                    case 'm': $xml .= $this->createDatePartXml('month', 'numeric-leading-zeros', $prefix, $suffix); break;
                    case 'n': $xml .= $this->createDatePartXml('month', 'numeric', $prefix, $suffix); break;
                    case 'F': $xml .= $this->createDatePartXml('month', 'long', $prefix, $suffix); break;
                    case 'M': $xml .= $this->createDatePartXml('month', 'short', $prefix, $suffix); break;
                    case 'Y': $xml .= $this->createDatePartXml('year', 'long', $prefix, $suffix); break;
                    case 'y': $xml .= $this->createDatePartXml('year', 'short', $prefix, $suffix); break;
                }
            }
        }
        return $xml;
    }

    private function createDatePartXml($name, $form, $prefix, $suffix): string {
        $attrs = 'name="' . $name . '"';
        if ($form) $attrs .= ' form="' . $form . '"';
        if ($prefix) $attrs .= ' prefix="' . htmlspecialchars($prefix) . '"';
        if ($suffix) $attrs .= ' suffix="' . htmlspecialchars($suffix) . '"';
        return '<date-part ' . $attrs . '/>';
    }
}
```

#### B. Modificación en `JatsParserPlugin.inc.php`
Obtención del formato correcto.

```php
$lang = str_replace('_', '-', $submissionFile->getSubmissionLocale());
$dateFormat = $context->getSetting('dateFormatShort');

// Fix: Usar el idioma del ENVÍO, no el de la sesión administrativa
if (is_array($dateFormat)) {
    $locale = $submissionFile->getSubmissionLocale(); 
    $dateFormat = $dateFormat[$locale] ?? reset($dateFormat);
}

// Se pasa $dateFormat como 4to parámetro
$htmlDocument->setReferences($citationStyle, $lang, false, $dateFormat);
```

#### C. Modificación en `src/JATSParser/HTML/Document.php`
Uso de `DateFormatter`.

```php
use JATSParser\HTML\CSL\DateFormatter; // Importar

// ...

public function setReferences(..., string $dateFormat = null): void {
    // ...
    if (!empty($this->jatsDocument->getReferences())) {
        // Pasar dateFormat a extractReferences
        $this->extractReferences($this->jatsDocument->getReferences(), $dateFormat);
    }
}

protected function extractReferences(array $references, string $dateFormat = null): void {
    // ...
    if ($dateFormat) {
        $cslContent = file_get_contents($styleName);
        if ($cslContent) {
            // Instanciar formateador y procesar
            $dateFormatter = new DateFormatter();
            $cslContent = $dateFormatter->injectOJSDateFormat($cslContent, $dateFormat);
            
            // Guardar temporal con .csl para que citeproc lo lea
            $tempStyleFile = tempnam(sys_get_temp_dir(), 'csl_') . '.csl';
            file_put_contents($tempStyleFile, $cslContent);
            $styleName = $tempStyleFile;
        }
    }
    // ...
}
```
