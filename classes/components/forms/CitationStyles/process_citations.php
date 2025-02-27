<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['citationStyle'])) {

        $xmlFilePath = $_POST['xmlFilePath'];

        $unifiedArray = [];
        foreach ($_POST['citationStyle'] as $rid => $citationStyle) {
            $unifiedArray[$rid] = $citationStyle;
        }
        foreach ($_POST['customCitation'] as $rid => $customCitation) {
            $unifiedArray[$rid] = $customCitation;
        }
    
        $dom = new DOMDocument;
        $dom->load($xmlFilePath);
        $xpath = new DOMXPath($dom);

        $xrefs = $xpath->query('//article/body/sec//xref');
        foreach ($unifiedArray as $unique_id => $citationText) {
            foreach ($xrefs as $xref) {
                if ($xref->getAttribute('id') === $unique_id) {
                    $xref->nodeValue = $citationText;
                }
            }
        }

        $dom->save($xmlFilePath);

        $citationData = json_encode($unifiedArray);

        print_r($citationData);

        header("Location: " . $_SERVER['REQUEST_URI']);
} 

?>