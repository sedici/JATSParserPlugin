<?php namespace PKP\components\forms;

require_once __dir__ . '/CitationStyles/ApaStyle.php';
require_once __dir__ . '/Helpers/getPublicationId.php';

use PKP\components\forms\CitationStyles\ApaStyle;

class TableHTML {

    private const WORDS_BEFORE = 30;
    
    private $html = "";
    private static $xpath;
    private $citationStyle;
    private $arrayData = array();
    private $absoluteXmlPath;
    private $publicationId;

    private $citationsArray;
    private $referencesArray = array();
    private $xrefsArray = array();

    public function __construct($citationStyle, ?String $absoluteXmlPath, $customCitationData, int $publicationId)
    {
        $this->publicationId = $publicationId;
        $this->citationsArray = $customCitationData;
        $this->absoluteXmlPath = $absoluteXmlPath;

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

    public function mergeArrays(){
        foreach ($this->xrefsArray as $xrefId => $data){
            $rids = explode(' ', $data['rid']); // Split multiple rids
            
            foreach ($rids as $singleRid) {
                if (!isset($this->arrayData[$xrefId])) {
                    $this->arrayData[$xrefId] = [
                        'xrefId' => $xrefId,
                        'rid' => $data['rid'],
                        'context' => $data['context'],
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

                if (isset($this->citationsArray[$xrefId])) {
                    $this->arrayData[$xrefId]['status'] = 'not-default';
                    $this->arrayData[$xrefId]['citationText'] = $this->citationsArray[$xrefId];
                }
            }
        }
    }

    public function extractXRefs(){
        $xrefsArray = array();
        $xrefBrand = 0;
        foreach (self::$xpath->evaluate("/article/body//sec//p//xref") as $xref) {
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

            $context = $beforeWords . ' ' . $originalText;

            $xrefsArray[$id] = [
                'context' => $context,
                'rid' => $rid
            ];
        }

        $this->xrefsArray = $xrefsArray;
    }

    public function extractReferences(): void {
        $referencesArray = array();

        $nodes = self::$xpath->query("/article/back/ref-list/ref");
        foreach ($nodes as $reference) {
            $id = $reference->getAttribute('id'); 

            $data = [];
            $authorNodes = self::$xpath->query(".//element-citation//person-group[@person-group-type='author']//name", $reference);
            
            $authorsCont = 1;
            foreach ($authorNodes as $authorNode) {
                $surnameNode = $authorNode->getElementsByTagName("surname")->item(0);
                if ($surnameNode) {
                    $surname = $surnameNode->nodeValue;
                    if ($surname) {
                        $data['data_' . $authorsCont]['surname'] = $surname;
                    }
                }
                
                $yearNode = self::$xpath->query(".//element-citation//year", $reference)->item(0);
                $year = $yearNode ? $yearNode->nodeValue : "s.f.";
                
                $data['data_' . $authorsCont]['year'] = $year;
                $authorsCont++;
            }

            $referencesArray[$id] = [
                'reference' => $reference->textContent,
                'authors' => $data
            ];
        }

        $this->referencesArray = $referencesArray;
    }

    public function makeHtml(): void {

        $className = "PKP\\components\\forms\\CitationStyles\\" . ucfirst($this->citationStyle) . 'Style';
        $this->html = $className::makeHtml($this->arrayData, $this->absoluteXmlPath, $this->citationStyle, $this->publicationId);
    }   
    
    public function getHtml(){
        return $this->html;
    }
}