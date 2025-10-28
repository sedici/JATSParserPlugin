<?php

    function getPublicationId(String $path){
        $submissionId = null;
        $args = explode('/', trim($path, '/'));
        
        // Find the position of "workflow" and get the next value as `submissionId`
        if (($key = array_search('workflow', $args)) !== false) {
            if (isset($args[$key + 2])) { // {workflow}/index/{submissionId}
                $submissionId = intval($args[$key + 2]);
            }
        }

        if ($submissionId) {
            $submissionDao = DAORegistry::getDAO('SubmissionDAO'); 
            $submission = $submissionDao->getById($submissionId);
        
            if ($submission) {
                $publicationId = $submission->getCurrentPublication()->getId();
            }
        }

        return $publicationId;
    };