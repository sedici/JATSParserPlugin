<?php namespace PKP\components\forms;

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Reference as HTMLReference;

require_once __dir__ . '/CitationStyles/ApaCitationTable.php';

class TableHTML {

    private const WORDS_BEFORE = 30;
    private const CITATION_MARKER = "{{CITATION_MARKER}}";
    
    private $html = "";
    private static $xpath;
    private $citationStyle;
    private $arrayData = array();
    private $absoluteXmlPath;
    private $publication;
    private $locale_key;

    private $citationsArray;
    private $referencesArray = array();
    private $xrefsArray = array();
    private $debug = false;

    public function __construct(String $citationStyle, ?String $absoluteXmlPath, $customCitationData, $publication, String $locale_key)
    {
        $this->locale_key = $locale_key;
        $this->publication = $publication;
        $this->absoluteXmlPath = $absoluteXmlPath;
        
        // Make sure citationsArray is properly structured even if empty
        $this->citationsArray = $customCitationData ?: [];

        $dom = new \DOMDocument;
        $dom->load($absoluteXmlPath);
		$xpath = new \DOMXPath($dom);
        self::$xpath = $xpath;

        $this->citationStyle = $citationStyle;

        $this->extractReferences();
        $this->extractXRefs();
        $this->mergeArrays();

        $this->makeHtml();
    }

    public function getHtml(){
        return $this->html;
    }

    // Merge xrefs and references arrays
    public function mergeArrays(){

        foreach ($this->xrefsArray as $xrefId => $data){
            $rids = explode(' ', $data['rid']); // Split multiple rids
            
            foreach ($rids as $singleRid) {
                if (!isset($this->arrayData[$xrefId])) {
                    $this->arrayData[$xrefId] = [
                        'xrefId' => $xrefId,
                        'rid' => $data['rid'],
                        'context' => $data['context'],
                        'originalText' => $data['originalText'],
                        'references' => [],
                        'status' => 'default',
                        'citationText' => ''
                    ];
                }

                foreach ($this->referencesArray as $id => $reference) {
                    if (strpos($singleRid, $id) !== false) { // Ensure match
                        $this->arrayData[$xrefId]['references'][] = [
                            'id' => $id,
                            'reference' => $reference['reference'],
                            'authors' => $reference['authors']
                        ];
                    }
                }

                // Try to find by just matching the xrefId across any XML path
                foreach ($this->citationsArray['fileId'] as $xmlPath => $citations) {
                    if (isset($citations[$xrefId])) {
                        $this->arrayData[$xrefId]['status'] = 'not-default';
                        $this->arrayData[$xrefId]['citationText'] = $citations[$xrefId];
                        break;
                    }
                }
            }
        }
    }

    // Extract xrefs (citations) from the XML
    public function extractXRefs(){
        $xrefsArray = array();
        $xrefBrand = 0;
        foreach (self::$xpath->evaluate("//xref[@ref-type='bibr']") as $xref) {
            $rid = $xref->getAttribute("rid"); //saving citations rid attribute
            $id = $xref->getAttribute("id");

            //mark the xref node with a brand to identify it later
            $originalText = $xref->nodeValue;
            $xrefMarkedText = $xref->nodeValue . $xrefBrand;

            $xref->nodeValue = $xrefMarkedText;
            $parentNode = $xref->parentNode;
            $paragraphText = $parentNode->textContent; 

            //get the position of the marked xref node in the paragraph
            $xrefPosition = strpos($paragraphText, $xrefMarkedText);
            $beforeText = substr($paragraphText, 0, $xrefPosition);
            $beforeWords = implode(' ', array_slice(explode(' ', trim($beforeText)), -self::WORDS_BEFORE));
            
            //return the default text to the xref node 
            $xref->nodeValue = $originalText;

            $xrefBrand++;

            // Use a special marker to later replace with the citation text
            $context = $beforeWords . ' ' . self::CITATION_MARKER;

            $xrefsArray[$id] = [
                'context' => $context,
                'rid' => $rid,
                'originalText' => $originalText
            ];
        }

        $this->xrefsArray = $xrefsArray;
    }

    // Extract references from the XML
    public function extractReferences(): void {
        $referencesArray = array();

        // Create a JATSDocument instance
        $jatsDocument = new JATSDocument($this->absoluteXmlPath);
        
        // Get the references from the JATS document
        $references = $jatsDocument->getReferences();
        
        // Create an HTML document to handle formatting
        $htmlDoc = new \JATSParser\HTML\Document($jatsDocument);
        // Set the references with the desired citation style

        $formattedLocaleKey = str_replace('_', '-', $this->locale_key);
        $htmlDoc->setReferences($this->citationStyle, $formattedLocaleKey, false);
        
        // Get raw formatted references
        $formattedRefs = $htmlDoc->getRawReferences();
        
        // Process each reference - maintain your current DOM-based query for author info
        $nodes = self::$xpath->query("/article/back/ref-list/ref");
        foreach ($nodes as $referenceNode) {
            $id = $referenceNode->getAttribute('id');
            $data = [];
            $authorsCont = 1;

            // Keep your existing author extraction logic
            $elementCitations = self::$xpath->query(".//element-citation", $referenceNode);
            foreach ($elementCitations as $elementCitation) {
                // Extract year once per element-citation
                $yearNode = self::$xpath->query(".//year", $elementCitation)->item(0);
                $year = $yearNode ? $yearNode->nodeValue : "s.f.";

                // Process authors
                $authorNodes = self::$xpath->query(".//person-group[@person-group-type='author']//name", $elementCitation);
                foreach ($authorNodes as $authorNode) {
                    $surnameNode = $authorNode->getElementsByTagName("surname")->item(0);
                    if ($surnameNode) {
                        $surname = $surnameNode->nodeValue;
                        if ($surname) {
                            $data['data_' . $authorsCont]['surname'] = $surname;
                            $data['data_' . $authorsCont]['year'] = $year;
                            $authorsCont++;
                        }
                    }
                }

                // Process institutions (collab)
                $collabNodes = self::$xpath->query(".//collab/named-content[@content-type='name']", $elementCitation);
                foreach ($collabNodes as $collabNode) {
                    $institutionName = $collabNode->nodeValue;
                    if ($institutionName) {
                        $data['data_' . $authorsCont]['surname'] = trim($institutionName);
                        $data['data_' . $authorsCont]['year'] = $year;
                        $authorsCont++;
                    }
                }
            }
            
            // Use the formatted reference if available, fallback to original text
            $formattedReference = isset($formattedRefs[$id]) ? $formattedRefs[$id] : $referenceNode->textContent;
            
            // Store both the formatted reference and author data
            $referencesArray[$id] = [
                'reference' => $formattedReference,
                'authors' => $data
            ];
        }
        
        $this->referencesArray = $referencesArray;
    }

    // Make the HTML for the table
    public function makeHtml(): void {

        $className = "PKP\\components\\forms\\CitationStyles\\" . ucfirst($this->citationStyle) . 'CitationTable';

        $processedArrayData = $this->processContexts($this->arrayData);
        
        $tableStyle = new $className(
            $processedArrayData, 
            $this->absoluteXmlPath, 
            $this->citationStyle, 
            $this->publication->getId(), 
            $this->locale_key
        );

        $this->html = $tableStyle->makeHtml();
    }   

    /**
     * Replaces the citation marker in each context with the actual citation text
     * with added styling to visually differentiate it
     * 
     * @param array $data The array data containing contexts and citation texts
     * @return array The processed array with updated contexts
     */
    private function processContexts(array $data): array {
        foreach ($data as $xrefId => &$item) {
            // Prioritize custom citation text when available
            $citationText = !empty($item['citationText']) ? $item['citationText'] : $item['originalText'];
            
            $escapedCitationText = htmlspecialchars($citationText, ENT_QUOTES, 'UTF-8');
            
            // Apply inline styling directly to the citation with properly escaped content
            $styledCitation = '<span style="color: #0066cc; font-weight: bold; background-color: #f0f8ff; padding: 0 3px; border-radius: 3px;">' 
                . $escapedCitationText . '</span>';
            
            $item['context'] = str_replace(self::CITATION_MARKER, $styledCitation, $item['context']);
        }
        return $data;
    }
}