<?php namespace PKP\components\forms\Processors;

class ReferencesProcessor {

    private $refs = [];

    public function __construct($refs = []) {
        $this->refs = $refs;
    }

    public function getNumberedReferences() {
        $numberedRefs = [];
        $groupedByPrefix = [];
        
        // 1. Agrupar por prefijo (todo antes del primer paréntesis de fecha)
        foreach ($this->refs as $id => $ref) {
            $start = strpos($ref, '(');
            if ($start !== false) {
                // Encontrar el cierre del paréntesis para que el prefijo sea idéntico en fecha
                $afterParen = substr($ref, $start + 1);
                $endParen = strpos($afterParen, ')');
                
                if ($endParen !== false) {
                    // Prefijo: Todo hasta el cierre del paréntesis inclusive
                    $prefix = substr($ref, 0, $start + 1 + $endParen + 1);
                } else {
                    // Fallback si no hay cierre
                    $prefix = substr($ref, 0, $start + 1);
                }
                
                // Quitar cualquier cosa como (Ed.) o excesivos espacios para agrupar de forma segura
                $prefix = str_replace(['(Ed.)', '(Eds.)', '(Coord.)', '(Coords.)'], '', $prefix);
                $prefix = trim(preg_replace('/\s+/', ' ', $prefix));
                
                $groupedByPrefix[$prefix][] = ['id' => $id, 'ref' => $ref];
            } else {
                $numberedRefs[$id] = $ref;
            }
        }
        
        // 2. Insertar letras
        foreach ($groupedByPrefix as $prefix => $references) {
            if (count($references) > 1) {
                $letterIndex = 0;
                $alphabet = 'abcdefghijklmnopqrstuvwxyz';
                
                foreach ($references as $refData) {
                    $id = $refData['id'];
                    $ref = $refData['ref'];
                    $letter = $alphabet[$letterIndex % 26]; // a, b, c...
                    
                    $start = strpos($ref, '(');
                    $modifiedRef = $ref;
                    if ($start !== false) {
                        $afterParen = substr($ref, $start + 1);
                        $endParen = strpos($afterParen, ')');
                        if ($endParen !== false) {
                            $parenthesisContent = substr($afterParen, 0, $endParen);
                        } else {
                            $parenthesisContent = $afterParen;
                        }

                        // Buscar si contiene 4 digitos o s.f.
                        if (preg_match('/(\d{4}|[sS]\.[fF]\.)/', $parenthesisContent, $matches, PREG_OFFSET_CAPTURE)) {
                            $matchStr = $matches[1][0];
                            $matchOffset = $matches[1][1];
                            // Posición de inserción: Inicio del paréntesis (start) + 1 (evitar el '(') + offset dentro del contenido + longitud del año
                            $insertPos = $start + 1 + $matchOffset + strlen($matchStr);
                            $modifiedRef = substr_replace($ref, $letter, $insertPos, 0);
                        }
                    }
                    
                    $numberedRefs[$id] = $modifiedRef;
                    $letterIndex++;
                }
            } else {
                $refData = $references[0];
                $numberedRefs[$refData['id']] = $refData['ref'];
            }
        }
        
        return $numberedRefs;
    }

}