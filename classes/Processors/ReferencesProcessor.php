<?php namespace PKP\components\forms\Processors;

class ReferencesProcessor {

    private $refs = [];

    public function __construct($refs = []) {
        $this->refs = $refs;
    }

    public function getNumberedReferences() {
        $numberedRefs = [];
        
        // Array para agrupar referencias con el mismo prefijo
        $groupedByPrefix = [];
        
        // Extraer prefijo de cada referencia y agrupar
        foreach ($this->refs as $id => $ref) {
            // Extraer el texto hasta el cierre del paréntesis
            preg_match('/^.*?\(([^).]*)\)/', $ref, $matches);
            if (isset($matches[0])) {
                $prefix = trim($matches[0]);
                // Quitar solo (Ed.), (Eds.), (Coord.), (Coords.)
                $prefix = preg_replace('/\s*\((?:Ed|Eds|Coord|Coords)\.\)?./i', ' ', $prefix);
                // Normalizar espacios
                $prefix = preg_replace('/\s{2,}/', ' ', trim($prefix));

                // Agrupar referencias con el mismo prefijo
                $groupedByPrefix[$prefix][] = ['id' => $id, 'ref' => $ref];
            } else {
                // Si no hay coincidencia, mantener la referencia sin cambios
                $numberedRefs[$id] = $ref;
            }
        }
        
        file_put_contents(
                __DIR__ . '/test.txt',
                print_r($groupedByPrefix, true)
        );

        // Procesar cada grupo de referencias con el mismo prefijo
        // Para cada grupo diferente, la secuencia de letras se reinicia
        foreach ($groupedByPrefix as $prefix => $references) {
            // Reiniciamos la letra para cada grupo nuevo de referencias con prefijo idéntico
            $letter = 'a';
            
            if (count($references) > 1) {
                // Si hay múltiples referencias con el mismo prefijo, asignar letras
                // secuenciales (a, b, c...) a este grupo
                foreach ($references as $refData) {
                    $id = $refData['id'];
                    $ref = $refData['ref'];
                    
                    // Insertar la letra justo antes del paréntesis de cierre
                    $modifiedRef = preg_replace('/(^.*?\([^.)]+)\)/', '$1' . $letter . ')', $ref);
                    $numberedRefs[$id] = $modifiedRef;
                    
                    // Avanzar a la siguiente letra para la próxima referencia de este grupo
                    $letter++;
                }
            } else {
                // Si solo hay una referencia con este prefijo, mantenerla sin cambios
                // ya que no necesita ser diferenciada con letras
                $refData = $references[0];
                $numberedRefs[$refData['id']] = $refData['ref'];
            }
        }
        
        // Ejemplo del resultado:
        // "Smith, J. (2020)" → se queda igual si es única
        // Pero si hay varios con el mismo prefijo:
        // "Smith, J. (2020a)" y "Smith, J. (2020b)" para dos referencias del mismo autor y año
        
        return $numberedRefs;
    }

}