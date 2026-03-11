<?php
require_once(__DIR__ . '/JATSParser/vendor/autoload.php');

use JATSParser\Back\Book;
use JATSParser\Body\Document;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

$xmlString = <<<XML
<article>
  <back>
    <ref-list>
      <ref id="B1">
        <element-citation publication-type="book">
          <person-group person-group-type="author">
            <name>
              <surname>Smith</surname>
              <given-names>John</given-names>
            </name>
          </person-group>
          <source>Book with Edition</source>
          <year>2025</year>
          <edition>10</edition>
          <publisher-name>Editorial ABC</publisher-name>
          <publisher-loc>Madrid</publisher-loc>
        </element-citation>
      </ref>
    </ref-list>
  </back>
</article>
XML;

$dom = new \DOMDocument();
$dom->loadXML($xmlString);

// Simulate Document to set xpath
$xpath = new \DOMXPath($dom);
$docRef = new \ReflectionClass(\JATSParser\Body\Document::class);
$prop = $docRef->getProperty('xpath');
$prop->setAccessible(true);
$prop->setValue(null, $xpath);

$refNodes = $xpath->query('//ref');

$cslRefs = [];
foreach ($refNodes as $refNode) {
    echo "--- Parsing ref: " . $refNode->getAttribute('id') . " ---\n";
    $bookRef = new Book($refNode);
    $htmlRef = new \JATSParser\HTML\Reference($bookRef);
    
    $content = $htmlRef->getContent();
    print_r(json_encode($content, JSON_PRETTY_PRINT));
    echo "\n\n";
    
    $cslRefs[] = json_decode(json_encode($content));
}

$cslStylePath = __DIR__ . '/JATSParser/src/JATSParser/Back/CSL/apa-spanish-SUMARC.csl';
$style = StyleSheet::loadStyleSheet($cslStylePath);
$citeProc = new CiteProc($style, 'es-ES');

echo "--- CITEPROC RENDER ---\n";
echo $citeProc->render($cslRefs, "bibliography");

