<?php

import('pages.article.ArticleHandler');

class FullTextArticleHandler extends ArticleHandler {

	var $_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		error_log('FullTextArticleHandler::__construct()');
		parent::__construct();
		$this->_plugin = PluginRegistry::getPlugin('generic', 'jatsParser');
	}

	/**
	 * @param $args
	 * @param $request
	 * @brief download supplementary files for article's full-text
	 */
	function downloadFullTextAssoc($args, $request) {
		error_log('FullTextArticleHandler::downloadFullTextAssoc()');
		$fileId = $args[2];
		$dispatcher = $request->getDispatcher(); /** @var $dispatcher Dispatcher */
		if (empty($fileId) || !$this->article || !$this->publication) $dispatcher->handle404();

		if (!$this->userCanViewGalley($request, $this->article->getId())) {
			header('HTTP/1.0 403 Forbidden');
			echo '403 Forbidden<br>';
			exit;
		}

		$fullTextFileIds = $this->publication->getData('jatsParser::fullTextFileId');
		if (empty($fullTextFileIds)) $dispatcher->handle404();

		// Find if the file is an image dependent from the XML file, from which full-text was generated.
		import('lib.pkp.classes.submission.SubmissionFile'); // const
		
		$dependentFilesIterator = Services::get('submissionFile')->getMany([
			'submissionIds' => [$this->article->getId()],
		]);
		$dependentFilesIterator = array_filter($dependentFilesIterator, function($file) use ($fullTextFileIds) {
			return $file->getData('assocType') === ASSOC_TYPE_SUBMISSION_FILE &&
				in_array($file->getData('assocId'), array_values($fullTextFileIds));
		});

		if (!count($dependentFilesIterator)) $dispatcher->handle404();

		$submissionFile = null;
		foreach ($dependentFilesIterator as $dependentFile) {
			if ($fileId == $dependentFile->getData('fileId')) {
				$submissionFile = $dependentFile;
				break;
			}
		}

		if (!$submissionFile) $dispatcher->handle404();

		if (!in_array($submissionFile->getData('mimetype'), $this->_plugin::getSupportedSupplFileTypes())) $dispatcher->handle404();

		// Download file if exists
		import('lib.pkp.classes.file.PrivateFileManager');
		$privateFileManager = new PrivateFileManager();
		$filePath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path');

		if (!file_exists($filePath)) {
			$request->getDispatcher()->handle404();
		}

		$filename = $submissionFile->getLocalizedData('name');
		// Force inline rendering for images instead of forced download
		$privateFileManager->downloadByPath($filePath, null, true, $filename);
	}
}
