<?php namespace PKP\components\forms;

use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Reference as HTMLReference;
use PKP\components\forms\Processors\ReferencesProcessor;

require_once __DIR__ . '/../../Processors/ReferencesProcessor.php';
require_once __dir__ . '/CitationStyles/ApaCitationTable.php';

class TableHTML {

    /**
     * Number of words kept before the xref (citation) to show as context in the table.
     */
    private const WORDS_BEFORE = 30;
    
    /**
     * Temporary marker inserted in the context text; later replaced by the styled citation span.
     */
    private const CITATION_MARKER = "{{CITATION_MARKER}}";
    
    /**
     * Final generated HTML (the modal fragment with the complete table).
     */
    private $html = "";
    
    /**
     * Shared DOMXPath instance over the JATS document to perform reusable queries.
     */
    private static $xpath;
    
    /**
     * Selected citation style (e.g. 'apa').
     */
    private $citationStyle;
    
    /**
     * Absolute path to the processed JATS XML file.
     */
    private $absoluteXmlPath;
    
    /**
     * OJS Publication object used to obtain metadata such as its ID.
     */
    private $publication;
    
    /**
     * Requested locale (base format with underscores) used to format references and texts.
     */
    private $locale_key;
    
    /**
     * Previously stored config of custom citations from DB (structure: ['fileId' => [...xrefId => text...]] ).
     */
    private $dbCitationsData;
    
    /**
     * Parsed bibliographic references: id => ['reference' => formatted html/text, 'authors' => author data].
     */
    private $referencesArray = array();
    
    /**
     * Extracted table titles: tableId => 'Table title'.
     */
    private $tablesArray = array();
    
    /**
     * Extracted figure titles: figId => 'Figure title'.
     */
    private $figsArray = array();
    
    /**
     * Detected bibr xrefs: xrefId => ['context','rid','originalText','refType'].
     */
    private $bibrXrefsArray = array();
    
    /**
     * Figure and table xrefs: xrefId => ['context','rid','originalText','title?(if single)','refType'].
     */
    private $figsAndTablesXRefsArray = array();
    
    /**
     * Consolidated structure feeding the final renderer. Contains keys:
     *  - 'bibr_citations_data' => [...]
     *  - 'figs_tables_citations_data' => [...]
     */
    private $arrayData = array();

    public function __construct(String $citationStyle, ?String $absoluteXmlPath, $customCitationData, $publication, String $locale_key)
    {
        $this->locale_key = $locale_key;
        $this->publication = $publication;
        $this->absoluteXmlPath = $absoluteXmlPath;
        
        // Make sure citationsArray is properly structured even if empty
        $this->dbCitationsData = $customCitationData ?: [];

        $dom = new \DOMDocument;
        $dom->load($absoluteXmlPath);
		$xpath = new \DOMXPath($dom);
        self::$xpath = $xpath;

        $this->citationStyle = $citationStyle;

        $this->extractReferences();
        $this->extractFigsTitles();
        $this->extractTablesTitles();

        $this->extractBibrXRefs();
        $this->extractFigsAndTablesXRefs();
        
        $this->mergeArrays();

        $this->makeHtml();
    }

    public function getHtml(){
        return $this->html;
    }

    // Merge xrefs (bibr, tables&figs) to get the final array
    public function mergeArrays(){
        $this->mergeBibrCitations();
        $this->mergeFigsTablesCitations();
    }

    /**
     * Merge bibr citations into arrayData
     */
    private function mergeBibrCitations(): void {
        foreach ($this->bibrXrefsArray as $xrefId => $data){
            $rids = explode(' ', $data['rid']);
            foreach ($rids as $singleRid) {
                if (!isset($this->arrayData['bibr_citations_data'][$xrefId])) {
                    $this->arrayData['bibr_citations_data'][$xrefId] = [
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
                    if (strpos($singleRid, $id) !== false) {
                        $this->arrayData['bibr_citations_data'][$xrefId]['references'][] = [
                            'id' => $id,
                            'reference' => $reference['reference'],
                            'authors' => $reference['authors']
                        ];
                    }
                }
                foreach ($this->dbCitationsData['fileId'] as $xmlPath => $citations) {
                    if (isset($citations[$xrefId])) {
                        $this->arrayData['bibr_citations_data'][$xrefId]['status'] = 'not-default';
                        $this->arrayData['bibr_citations_data'][$xrefId]['citationText'] = $citations[$xrefId];
                        break;
                    }
                }
            }
        }
    }

    /**
     * Merge figs and tables citations into arrayData
     */
    private function mergeFigsTablesCitations(): void {
        foreach ($this->figsAndTablesXRefsArray as $xrefId => $data){
            if (!isset($this->arrayData['figs_tables_citations_data'][$xrefId])) {
                $this->arrayData['figs_tables_citations_data'][$xrefId] = [
                    'xrefId' => $xrefId,
                    'rid' => $data['rid'],
                    'context' => $data['context'],
                    'originalText' => $data['originalText'],
                    'title' => $data['title'],
                    'refType' => $data['refType'],
                    'status' => 'default',
                    'citationText' => ''
                ];
            }
            $rids = preg_split('/\s+/', trim($data['rid']));
            $titlesList = [];
            foreach ($rids as $singleRid) {
                if ($singleRid === '') continue;
                $titleTxt = '';
                if ($data['refType'] === 'fig' && isset($this->figsArray[$singleRid])) {
                    $titleTxt = $this->figsArray[$singleRid];
                } elseif ($data['refType'] === 'table' && isset($this->tablesArray[$singleRid])) {
                    $titleTxt = $this->tablesArray[$singleRid];
                }
                if ($titleTxt === '') { $titleTxt = $singleRid; }
                $titlesList[] = [ 'id' => $singleRid, 'title' => $titleTxt ];
            }
            $this->arrayData['figs_tables_citations_data'][$xrefId]['titles'] = $titlesList;
            foreach ($this->dbCitationsData['fileId'] as $xmlPath => $citations) {
                if (isset($citations[$xrefId])) {
                    $this->arrayData['figs_tables_citations_data'][$xrefId]['status'] = 'not-default';
                    $this->arrayData['figs_tables_citations_data'][$xrefId]['citationText'] = $citations[$xrefId];
                    break;
                }
            }
        }
    }

    public function extractFigsAndTablesXRefs() {
        $xrefsArray = array();
        foreach (self::$xpath->evaluate("//xref[@ref-type='fig' or @ref-type='table']") as $xref) {
            $ridAttr = $xref->getAttribute("rid");
            $ids = preg_split('/\s+/', trim($ridAttr));
            $ridNormalized = implode(' ', $ids); // Normalize spaces
            $id = $xref->getAttribute("id");
            $refType = $xref->getAttribute("ref-type");

            $originalText = $xref->nodeValue;
            $xrefMarkedText = $xref->nodeValue . $id;
            $xref->nodeValue = $xrefMarkedText;
            $parentNode = $xref->parentNode;
            $paragraphText = $parentNode->textContent; 
            $xrefPosition = strpos($paragraphText, $xrefMarkedText);
            $beforeText = substr($paragraphText, 0, $xrefPosition);
            $beforeWords = implode(' ', array_slice(explode(' ', trim($beforeText)), -self::WORDS_BEFORE));
            $xref->nodeValue = $originalText;
            $context = $beforeWords . ' ' . self::CITATION_MARKER;

            // Only assign a single "title" if there is only one rid. If there are multiple, they will be handled later in mergeArrays.
            $title = '';
            if (count($ids) === 1) {
                $singleRid = $ids[0];
                if ($refType === 'fig' && isset($this->figsArray[$singleRid])) {
                    $title = $this->figsArray[$singleRid];
                } elseif ($refType === 'table' && isset($this->tablesArray[$singleRid])) {
                    $title = $this->tablesArray[$singleRid];
                }
            }

            $xrefsArray[$id] = [
                'context' => $context,
                'rid' => $ridNormalized,
                'originalText' => $originalText,
                'title' => $title,
                'refType' => $refType
            ];
        }
        $this->figsAndTablesXRefsArray = $xrefsArray;
    }

    // Extract xrefs (citations) from the XML
    public function extractBibrXRefs(){
        $xrefsArray = array();
        $xrefBrand = 0;
        foreach (self::$xpath->evaluate("//xref[@ref-type='bibr']") as $xref) {
            $rid = $xref->getAttribute("rid"); //saving citations rid attribute
            $id = $xref->getAttribute("id");
            $refType = $xref->getAttribute("ref-type");

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
                'originalText' => $originalText,
                'refType' => $refType
            ];
        }

        $this->bibrXrefsArray = $xrefsArray;
    }

    // Extract fig titles from the XML
    public function extractFigsTitles(){
        $figsArray = array();
        foreach (self::$xpath->evaluate("//fig") as $fig) {
            $id = $fig->getAttribute("id");
            $captionNode = self::$xpath->query(".//caption//title", $fig)->item(0);
            if ($captionNode) {
                $figsArray[$id] = trim($captionNode->nodeValue);
            }
        }
        $this->figsArray = $figsArray;
    }

    // Extract table titles from the XML
    public function extractTablesTitles(){
        $tablesArray = array();
        foreach (self::$xpath->evaluate("//table-wrap") as $table) {
            $id = $table->getAttribute("id");
            $captionNode = self::$xpath->query(".//caption//title", $table)->item(0);
            if ($captionNode) {
                $tablesArray[$id] = trim($captionNode->nodeValue);
            }
        }
        $this->tablesArray = $tablesArray;
    }

    // Extract references from the XML
    public function extractReferences(): void {
        $referencesArray = array();

        // Create a JATSDocument instance
        $jatsDocument = new JATSDocument($this->absoluteXmlPath);
        
        // Get the references from the JATS document
        
        // Create an HTML document to handle formatting
        $htmlDoc = new \JATSParser\HTML\Document($jatsDocument);
        // Set the references with the desired citation style

        $formattedLocaleKey = str_replace('_', '-', $this->locale_key);
        $htmlDoc->setReferences($this->citationStyle, $formattedLocaleKey, false);

        // Get raw formatted references
        $formattedRefs = $htmlDoc->getRawReferences();

        $refsProcessor = new ReferencesProcessor($formattedRefs);
        $formattedRefs = $refsProcessor->getNumberedReferences();

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
                $personGroupNodes = self::$xpath->query(".//person-group", $elementCitation);
                foreach ($personGroupNodes as $personGroupNode) {
                    $publicationType = $elementCitation->getAttribute('publication-type');
                    $personGroupType = $personGroupNode->getAttribute('person-group-type');

                    // rule: save data only if the person-group type is 'author' or 'editor' and publication type is not 'chapter'
                    $saveData = true;
                    if ($personGroupType === 'editor' && $publicationType === 'chapter') {
                        $saveData = false;
                    }

                    if ($saveData) {
                        foreach ($personGroupNode->getElementsByTagName("name") as $authorNode) {
                            $surnameNode = $authorNode->getElementsByTagName("surname")->item(0);
                            if ($surnameNode) {
                                $surname = $surnameNode->nodeValue;
                                if ($surname) {
                                    $data['data_' . $authorsCont]['surname'] = $surname;
                                    $data['data_' . $authorsCont]['year'] = $year;
                                    $data['data_' . $authorsCont]['role'] = $personGroupType; // opcional
                                    $authorsCont++;
                                }
                            }
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
        foreach ($data as $groupKey => &$group) { // bibr_citations_data, figs_tables_citations_data, etc.
            foreach ($group as $xrefId => &$item) {
                // Prioritize custom citation text when available
                $citationText = !empty($item['citationText']) ? $item['citationText'] : $item['originalText'];
                
                $escapedCitationText = htmlspecialchars($citationText, ENT_QUOTES, 'UTF-8');
                
                // Apply inline styling directly to the citation with properly escaped content
                $styledCitation = '<span style="color: #32849c; font-weight: bold; background-color: #f0f8ff; padding: 0 3px; border-radius: 3px;">' 
                    . $escapedCitationText . '</span>';
                
                $item['context'] = str_replace(self::CITATION_MARKER, $styledCitation, $item['context']);
            }
        }
        return $data;
    }
}
