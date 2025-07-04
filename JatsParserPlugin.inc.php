<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.inc.php
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserPlugin
 * @ingroup plugins_generic_jatsParser
 *
 */

require_once __DIR__ . '/JATSParser/vendor/autoload.php';
require_once __DIR__ . '/JATSParser/src/JATSParser/PDF/PDFConfig/Configuration.php';
require_once __DIR__ . '/JATSParser/src/JATSParser/PDF/PDFConfig/Translations.php';
require_once __DIR__ . '/JATSParser/src/JATSParser/PDF/TemplateStrategy.php';

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.jatsParser.classes.JATSParserDocument');
import('plugins.generic.jatsParser.classes.components.forms.PublicationJATSUploadForm');
import('lib.pkp.classes.citation.Citation');
import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.file.PKPPublicFileManager');

use PKP\citation\CitationListTokenizerFilter;
use JATSParser\PDF\PDFConfig\Translations;
use JATSParser\PDF\PDFConfig\Configuration;
use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use \PKP\components\forms\FormComponent;
use JATSParser\PDF\TemplateStrategy;
use APP\facades\Repo;
use PKP\core\JSONMessage;

use APP\core\Request;
use PKP\context\Context;
use PKP\locale\Locale;
use PKP\galley\Galley;

use PKP\db\DAORegistry;



define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin {
	
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {

			if ($this->getEnabled()) {
				// Add data to the publication
				HookRegistry::add('Template::Workflow::Publication', array($this, 'publicationTemplateData'));
				HookRegistry::add('Schema::get::publication', array($this, 'addToSchema'));
				HookRegistry::add('LoadHandler', array($this, 'loadFullTextAssocHandler'));
				HookRegistry::add('Publication::edit', array($this, 'editPublicationFullText'));
				HookRegistry::add('Templates::Article::Main', array($this, 'displayFullText'));
				HookRegistry::add('TemplateManager::display', array($this, 'themeSpecificStyles'));
				HookRegistry::add('Form::config::before', array($this, 'addCitationsFormFields'));
				HookRegistry::add('Publication::edit', array($this, 'editPublicationReferences'));
				HookRegistry::add('Publication::edit', array($this, 'createPdfGalley'), HOOK_SEQUENCE_LAST);
				HookRegistry::add('TemplateManager::display', function($hookName, $args) {
					$templateMgr = $args[0];
					$request = \Application::get()->getRequest();
					$templateMgr->addJavaScript(
						'citationTable',
						$request->getBaseUrl() . '/plugins/generic/jatsParser/citationTable.js',
						array('contexts' => 'frontend', 'priority' => 10)
					);
					$templateMgr->addStyleSheet(
						'citationTableCss',
						$request->getBaseUrl() . '/plugins/generic/jatsParser/citationTable.css',
						array('contexts' => 'frontend', 'priority' => 10)
					);
					return false;
				});
			}

			return true;
		}
		return false;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.jatsParser.displayName');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.jatsParser.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}
 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				//AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('JatsParserSettingsForm');
				$form = new JatsParserSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	//Get an array of OJS metadata to be used in the PDF generation
	private function getMetadata($publication, $localeKey, $request, $htmlString) {
		error_log('JATSParserPlugin::getMetadata() called');
	
		//$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$submission = Repo::submission()->get($publication->getData('submissionId'));
		$context = $request->getContext(); /* @var $context Journal */
		$journal = $request->getContext();

		$issue = Repo::issue()->get($publication->getData('issueId'));
		$section = Repo::section()->get($publication->getData('sectionId'));
		$userGroups = Repo::userGroup()
			->getCollector()
			->filterByContextIds([$journal->getId()])
			->getMany()
			->all(); // si querés el array


		$plugin = PluginRegistry::getPlugin('generic', 'jatsparserplugin');
		
		$decisions = Repo::decision()
			->getCollector()
			->filterBySubmissionIds([$submission->getId()])
			->getMany();

		$acceptedDate = null;
		foreach ($decisions as $decision) {
			
			if ($decision->getData('stageId') === 3 && $decision->getData('decision') === 1){
				error_log('JATSParserPlugin::getMetadata() - decision: ' . print_r($decision, true));
				$acceptedDate = $decision->getDateDecided();
			}
		}

		$privateFileManager = new PrivateFileManager();
		$journalLogosPath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR ."journals" . DIRECTORY_SEPARATOR . $journal->getId() . DIRECTORY_SEPARATOR . $journal->getData('path');

		$metadata = [
			'section_title' => $section?->getLocalizedTitle(),
			'citation_style' => $plugin->getSetting($context->getId(), 'citationStyle'),
			'publication_id' => $publication->getId(),
			'doi' => $publication->getData('pub-id::doi'), //No tiene
			'journal_id' => $journal->getId(),
			'authors' => $publication->getData('authors'),
			'online_issn' => $journal->getData('onlineIssn'), //no se imprime
			'journal_title' => $journal->getLocalizedData('name'),
			'journal_issue' => $publication->getData('issueId'),
			'journal_logos_path' => $journalLogosPath,
			'locale_key' => $localeKey,
			'journal_thumbnail' => $journal->getLocalizedData('journalThumbnail'),
			'full_title' => $publication->getLocalizedFullTitle($localeKey),
			'license_url' => $publication->getData('licenseUrl'), //
			'article_title' => $publication->getLocalizedData('title'),
			'submission' => $submission,
			'date_submitted' => date('d/m/Y', strtotime($submission->getDateSubmitted())),
			'date_accepted' => date('d/m/Y', strtotime($acceptedDate)),
			'date_published' => str_replace('-', '/', $submission->getDatePublished()),
			'journal_data' => ($issue !== null && $issue->getIssueIdentification()) ? $issue->getIssueIdentification() : "", // Includes volume, number, year of a journal.
			'user_groups' => $userGroups,
			'contributors' => null,//$publication->getAuthorString($userGroups),
			'subject' => $publication->getLocalizedData('subject', $localeKey),
			'abstract_texts' => $publication->getData('abstract'), // Returns an array like this: ['es_ES' => 'Resumen', 'en_US' => 'Abstract']
			'translations_config' => Translations::getTranslations(),
			'keywords_texts' => $publication->getData('keywords'),
			'plugin_path' => $this->getPluginPath(),
			'html_string' => $htmlString,
			'journal_url' => $request->getBaseUrl() . '/' . $journal->getPath(),
			'titles' => $publication->getData('title'),
			'subtitles' => $publication->getData('subtitle'),
			'editorial' => $context->getLocalizedData('institution')
		];

		$path = __DIR__ . '/salida.html';
		file_put_contents($path, $htmlString);

		error_log('JATSParserPlugin::getMetadata() - metadata return');
		return $metadata;
	}
	

	/**
	 * @param $article Submission
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	private function pdfCreation(string $htmlString, Publication $publication, Request $request, string $localeKey): string {
		error_log('JATSParserPlugin::pdfCreation() called');

		$metadata = $this->getMetadata($publication, $localeKey, $request, $htmlString);
		$configuration = new Configuration($metadata);

		error_log('JATSParserPlugin::pdfCreation() - new Configuration');

		$templateName = 'TemplateOne';
		$templateStrategy = new TemplateStrategy($templateName, $configuration);

		error_log('JATSParserPlugin::pdfCreation() - new TemplateStrategy');
		error_log('JATSParserPlugin::pdfCreation() - return');
		return $templateStrategy->OutputPdf();
	}

	/**
	 * Add a property to the publication schema
	 *
	 * @param $hookName string `Schema::get::publication`
	 * @param $args [[
	 * 	@option object Publication schema
	 * ]]
	 */
	public function addToSchema($hookName, $args) {
		error_log('JATSParserPlugin::addToSchema() called');
		$schema = $args[0];
		$propId = '{
			"type": "integer",
			"multilingual": true,
			"apiSummary": true,
			"validation": [
				"nullable"
			]
		}';
		$propText = '{
			"type": "string",
			"multilingual": true,
			"apiSummary": true,
			"validation": [
				"nullable"
			]
		}';
		$schema->properties->{'jatsParser::fullTextFileId'} = json_decode($propId);
		$schema->properties->{'jatsParser::fullText'} = json_decode($propText);
		$schema->properties->{'jatsParser::citationTableData'} = json_decode($propText);
	}

	/**
	 * @param string $hookname
	 * @param array $args [string, TemplateManager]
	 */
	function publicationTemplateData(string $hookname, array $args): void {
		error_log('JATSParserPlugin::publicationTemplateData() called');
		/**
		 * @var $templateMgr TemplateManager
		 * @var $submission Submission
		 * @var $submissionFileDao SubmissionFileDAO
		 * @var $submissionFile SubmissionFile
		 */
		$templateMgr = $args[1];
		$request = $this->getRequest();
		$context = $request->getContext();
		$submission = $templateMgr->getTemplateVars('submission');
		$latestPublication = $submission->getLatestPublication();
		$latestPublicationApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $context->getData('urlPath'), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());

		$supportedSubmissionLocales = $context->getSupportedSubmissionLocales();

		/*
		*The method AppLocale::getAllLocales() has been replaced by PKP\facades\Locale::getLocales(),
		*but instead of returning the locale display name, it returns a LocaleMetadata instance, which holds extra information.
		*
		*/
		//$localeNames = AppLocale::getAllLocales();
		$localeNames = PKP\facades\Locale::getLocales();

		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedSubmissionLocales);

		/*
		import('lib.pkp.classes.submission.SubmissionFile'); // const
		$submissionFiles = Services::get('submissionFile')->getMany([
			'submissionIds' => [$submission->getId()],
			'fileStages' => [SUBMISSION_FILE_PRODUCTION_READY],
		]);
		*/

		$submissionFiles = Repo::submissionFile()
			->getCollector()
			->filterBySubmissionIds([$submission->getId()])
			->filterByFileStages([SUBMISSION_FILE_PRODUCTION_READY])
			->getMany(); // ← Devuelve LazyCollection

		$submissionFilesXML = array();
		foreach ($submissionFiles as $submissionFile) {
			if (in_array($submissionFile->getData('mimetype'), array("application/xml", "text/xml"))) {
				$submissionFilesXML[] = $submissionFile;
			}
		}

		$dispatcher = $request->getDispatcher();
		
		//$submissionProps = Services::get('submission')->getProperties($submission, array('stageId'), array('request' => $request));
		$submissionProps = ['stageId' => $submission->getData('stageId')];

		$currentPath = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'fullTextPreview', $submission->getId(), $submissionProps);
		if (!empty($submissionFilesXML)) {
			$msg = $templateMgr->smartyTranslate(array(
				'key' => 'plugins.generic.jatsParser.publication.jats.description',
				'params' => array("previewPath" => $currentPath)
			), $templateMgr);
		} else {
			$msg = $templateMgr->smartyTranslate(array(
				'key' => 'plugins.generic.jatsParser.publication.jats.descriptionEmpty'
			), $templateMgr);
		}

		$form = new PublicationJATSUploadForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionFilesXML, $msg);
		$state = $templateMgr->getTemplateVars('state');
		$state['components'][FORM_PUBLICATION_JATS_FULLTEXT] = $form->getConfig();
		$state['publicationFormIds'][] = FORM_PUBLICATION_JATS_FULLTEXT;
		$templateMgr->assign('state', $state);

		$templateMgr->display($this->getTemplateResource("workflowJatsFulltext.tpl"));
	}

	/**
	 * @param $hookName string
	 * @param $args array
	 * @brief Handle associated files of the full-text, only images are supported
	 */
	function loadFullTextAssocHandler($hookName, $args) {
		error_log('JATSParserPlugin::loadFullTextAssocHandler() called');
		$page = $args[0];
		$op = $args[1];

		if ($page == 'article' && $op == 'downloadFullTextAssoc') {
			error_log('JATSParserPlugin::loadFullTextAssocHandler() - loading FullTextArticleHandler');
			define('HANDLER_CLASS', 'FullTextArticleHandler');
			define('JATSPARSER_PLUGIN_NAME', $this->getName());
			require_once($this->getPluginPath() . '/FullTextArticleHandler.inc.php');
			return true;
		}
		return false;
	}

	/**
	 * @param string $hookname
	 * @param array $args [
	 *   Publication -> new publication
	 *   Publication
	 *   array parameters/publication properties to be saved
	 *   Request
	 * ]
	 * @return bool
	 */
	function editPublicationFullText(string $hookname, array $args) {
		error_log('JATSParserPlugin::editPublicationFullText() called');
		$newPublication = $args[0];
		$params = $args[2];
		if (!array_key_exists('jatsParser::fullTextFileId', $params)) return false;

		$localePare = $params['jatsParser::fullTextFileId'];
		foreach ($localePare as $localeKey => $fileId) {
			if (empty($fileId)) {
				$newPublication->setData('jatsParser::fullText', null, $localeKey);
				$newPublication->setData('jatsParser::fullTextFileId', null, $localeKey);
				continue;
			}
			//$submissionFile = Services::get('submissionFile')->get($fileId);
			$submissionFile = Repo::submissionFile()->get($fileId);
			$htmlDocument = $this->getFullTextFromJats($submissionFile);
			$newPublication->setData('jatsParser::fullText', $htmlDocument->saveAsHTML(), $localeKey);
		}

		return false;
	}

	/**
	 * @param Journal $context Journal
	 * @return string
	 * @brief Retrieve citation style format that should be supported by citeproc-php
	 * use own format defined in settings if set
	 * use CitationStyleLanguagePlugin if set
	 * use vancouver style otherwise
	 */
	function getCitationStyle(Journal $context): string {
		error_log('JATSParserPlugin::getCitationStyle() called');	
		$contextId = $context->getId();

		$citationStyle = $this->getSetting($contextId, 'citationStyle');

		if ($citationStyle) return $citationStyle;

		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
		$cslPluginSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'CitationStyleLanguagePlugin');

		if ($cslPluginSettings &&
			array_key_exists('enabled', $cslPluginSettings) &&
			$cslPluginSettings['enabled'] &&
			array_key_exists('primaryCitationStyle', $cslPluginSettings) &&
			$cslPrimaryCitStyle = $cslPluginSettings['primaryCitationStyle']
		) $citationStyle = $cslPrimaryCitStyle;

		if ($citationStyle) return $citationStyle;

		$lastCslKey = array_key_last(self::getSupportedCitationStyles());
		return self::getSupportedCitationStyles()[$lastCslKey]['id']; // vancouver
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief modify citationsRaw property based on parsed citations from JATS XML
	 */
	function editPublicationReferences(string $hookname, array $args) {
		error_log('JATSParserPlugin::editPublicationReferences() called');
		$newPublication = $args[0];
		$params = $args[2];
		if (!array_key_exists('jatsParser::references', $params)) return false;

		$fileId = $params['jatsParser::references'];
		if (!$fileId) return false;

		//$submissionFile = Services::get('submissionFile')->get($fileId);
		$submissionFile = Repo::submissionFile()->get($fileId);

		$htmlDocument = $this->getFullTextFromJats($submissionFile);

		$request = $this->getRequest();
		$context = $request->getContext();

		// Get citations style, define default if not set
		$citationStyle = $this->getCitationStyle($context);

		$lang = str_replace('_', '-', $submissionFile->getSubmissionLocale());
		$htmlDocument->setReferences($citationStyle, $lang, false);

		$this->_importCitations($htmlDocument, $newPublication);

		return false;
	}

	function createPdfGalley(string $hookname, array $args) {
	
	error_log('JATSParserPlugin::createPdfGalley() called');
		
	$newPublication = $args[0]; /* @var $newPublication Publication */
	$params = $args[2];
	$request = $args[3];

	if (!array_key_exists('jatsParser::pdfGalley', $params)) return false;
	if (!$this->getSetting($request->getContext()->getId(), 'convertToPdf')) return false;

	$localePare = $params['jatsParser::pdfGalley'];

	foreach ($localePare as $localeKey => $createPdf) {
		$fullText = $newPublication->getData('jatsParser::fullText', $localeKey);
		if (empty($fullText)) continue;
		if (!$createPdf) continue;

		// Set real path to images, attached to the original JATS XML file
		$jatsFileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
		$jatsSubmissionFile = Repo::submissionFile()->get($jatsFileId);

		if ($jatsSubmissionFile) {
			error_log('fullText: ' . $fullText);
			$fullText = $this->_setSupplImgPath($jatsSubmissionFile, $fullText);
		}

		// Set references y footnotes
		$fullText = $this->_setReferences($newPublication, $localeKey, $fullText);
		$fullText = $this->_setFootnotes($newPublication, $localeKey, $fullText);

		
		// Convertir a PDF
		$pdf = $this->pdfCreation($fullText, $newPublication, $request, $localeKey);

		// Crear galley
		$galley = $this->createGalley($localeKey, $newPublication);


		// Obtener el galley usando Repo
		$galley = Repo::galley()
			->getCollector()
			->filterByPublicationIds([$newPublication->getId()])
			->getMany()
			->first(function($g) use ($galley) {
				return $g->getBestGalleyId() === $galley;
			});
	
		if (!$galley) continue;
			
		// Crear archivo de sumisión del PDF
		$submissionFile = $this->_setPdfSubmissionFile($pdf, $newPublication, $galley);

		if ($submissionFile) {
			// Not working, Repo::galley()->edit() does not accept fileId
			Repo::galley()->edit($galley, [
				'submissionFileId' => $submissionFile->getId(),
			]);	
		} else {
			Repo::galley()->delete($galley);
		}

			}

			return false;
		}


	/**
	 * @param string $galleyLocale
	 * @param Publication $publication
	 * @return int
	 * @brief create an empty galley
	 */
	function createGalley(string $galleyLocale, Publication $publication): int {
		error_log('JATSParserPlugin::createGalley() called');

		//$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		$articleGalley = Repo::galley()->newDataObject();
		$articleGalley->setLocale($galleyLocale);
		$articleGalley->setData('publicationId', $publication->getId());
		$articleGalley->setLabel(__('plugins.generic.jatsParser.publication.galley.pdf.label'));
		$articleGalley = Repo::galley()->add($articleGalley);
		return $articleGalley;
	}

	/**
	 * @param string $pdfBinaryString output of the TCPDF, binary string
	 * @param Publication $publication publication associated with a submission file
	 * @brief creates a new PDF submission file
	 */
	private function _setPdfSubmissionFile(string $pdfBinaryString, Publication $publication, Galley $galley) {
		
		//$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$submission = Repo::submission()->get($publication->getData('submissionId'));

		$request = $this->getRequest();

		// Create a temporary file
		$tmpFile = tempnam(sys_get_temp_dir(), 'jatsParser');
		file_put_contents($tmpFile, $pdfBinaryString);

		// Set main Submission File data
		//$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		//$submissionDir = Services::get('submissionFile')->getSubmissionDir($submission->getData('contextId'), $submission->getId());
		$submissionFile = Repo::submissionFile();
		$submissionDir = $submissionFile->getSubmissionDir($submission->getData('contextId'), $submission->getId());

		$fileId = Services::get('file')->add(
			$tmpFile,
			$submissionDir . DIRECTORY_SEPARATOR . uniqid() . '.pdf'
		);
		
		//$jatsFile = Services::get('submissionFile')->get($jatsFileId);
		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $galley->getLocale());
		$jatsFile = $submissionFile->get($jatsFileId);

		$name = [];
		foreach ($jatsFile->getData('name') as $locale => $sourceName) {
			$name[$locale] = pathinfo($sourceName)['filename'] . '.pdf';
		}

		$genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
		$genre = $genreDao->getByKey('SUBMISSION', $submission->getData('contextId'));

		$submissionFile = $submissionFile->newDataObject();
		$submissionFile->setAllData(
			[
				'fileId' => $fileId,
				'assocType' => ASSOC_TYPE_GALLEY,
				'assocId' => $galley->getId(),
				'fileStage' => SUBMISSION_FILE_PROOF,
				'mimetype' => 'application/pdf',
				'locale' => 'uk',
				'genreId' => $genre->getId(),
				'name' => $name,
				'submissionId' => $submission->getId(),
			]);
		//$submissionFile = Services::get('submissionFile')->add($submissionFile, $request);
		$submissionFileId = Repo::submissionFile()->add($submissionFile, $request);
		$submissionFile = Repo::submissionFile()->get($submissionFileId);


		//$submissionFile = Repo::submissionFile()->get($submissionFileId);
		unlink($tmpFile); // remove temporary file
		error_log('JATSParserPlugin::_setPdfSubmissionFile() - returning submissionFile');
		return $submissionFile;
	}

	/**
	 * @param Publication $publication
	 * @param string $locale
	 * @param string $htmlString
	 * @return string
	 * @brief set references for PDF galley
	 */
	private function _setReferences(Publication $publication, string $locale, string $htmlString): string {

		error_log('JATSParserPlugin::_setReferences() called');

		$rawCitations = $publication->getData('citationsRaw'); //References
		if (empty($rawCitations)) return $htmlString;

		// Use OJS raw citations tokenizer
		$citationTokenizer = new CitationListTokenizerFilter();
		$citationStrings = $citationTokenizer->execute($rawCitations);
		
		$numberedCitations = Configuration::getNumberedReferences();
		$context = Application::get()->getRequest()->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'jatsparserplugin'); /* @var $plugin JATSParserPlugin */
		$citationStyle = $plugin->getSetting($context->getId(), 'citationStyle');
		
		if (!is_array($citationStrings) || empty($citationStrings)) return $htmlString;
		$htmlString .= "\n";
		
		// Add container with semantic class instead of inline styles
		$htmlString .= "\n<div class=\"references-section\">";
		$htmlString .= '<h2>' . __('plugins.generic.jatsParser.article.references.title') . '</h2>';
		
		// Add container for the references with citation style as data attribute
		$containerTag = in_array($citationStyle, $numberedCitations) ? 'ol' : 'div';
		$htmlString .= '<' . $containerTag . ' id="references" class="citation-list" data-style="' . $citationStyle . '">';
		$htmlString .= "\n";
		
		foreach ($citationStrings as $citationString) {
			// Format the citation string, applying the URL formatting
			$formattedCitation = $this->_formatUrlsInText($citationString);
			
			$htmlString .= "\t";
			// Apply semantic class to the list item
			$htmlString .= '<li class="citation-item">' . $formattedCitation . '</li>';
			$htmlString .= "<br/>\n";
		}
		$htmlString .= '</' . $containerTag . '>';
		
		// Close the container
		$htmlString .= '</div>';

		error_log('JATSParserPlugin::_setReferences() - return');
		return $htmlString;
	}

	/**
	 * @param string $text => The text of the reference to be formatted.
	 * @return string
	 * @brief Detect and format URLs in the given reference text with a specific style.
	 */
	private function _formatUrlsInText(string $text): string {
		// Regular expression to detect URLs that start with http://, https://, or ftp://
		$urlPattern = '/(https?|ftp):\/\/[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';
		
		// Detect URLs that start with www. too
		$wwwPattern = '/(?<![\w.])www\.[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';
		
		// Search and replace URLs with semantic classes instead of inline styles
		$text = preg_replace_callback($urlPattern, function($matches) {
			return '<span class="citation-url">' . $matches[0] . '</span>';
		}, $text);
		
		// Search and replace URLs that start with www. with semantic classes
		$text = preg_replace_callback($wwwPattern, function($matches) {
			return '<span class="citation-url">' . $matches[0] . '</span>';
		}, $text);
		
		return $text;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @return HTMLDocument
	 * @brief retrieves PHP DOM representation of the article's full-text
	 */
	public function getFullTextFromJats (SubmissionFile $submissionFile): HTMLDocument {
		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();
		$htmlDocument = new HTMLDocument(new Document($fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path')));
		return $htmlDocument;
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief Displays full-text on article landing page
	 */
	function displayFullText(string $hookname, array $args) {
		error_log('JATSParserPlugin::displayFullText() called');
		$templateMgr =& $args[1];
		$output =& $args[2];
		$publication = $templateMgr->getTemplateVars('publication');
		$submission = $templateMgr->getTemplateVars('article');
		$fullTexts = $publication->getData('jatsParser::fullText');

		$submissionFileId = 0;
		$submissionFile = null;

		$request = $this->getRequest();
		$html = null;

		if (empty($fullTexts)) return false;
		$currentLocale = PKP\facades\Locale::getLocale();
		if (array_key_exists($currentLocale, $fullTexts)) {	
			$html = $fullTexts[$currentLocale];

			$submissionFileId = $publication->getData('jatsParser::fullTextFileId', $currentLocale);
			//$submissionFile = Services::get('submissionFile')->get($submissionFileId);
			$submissionFile = Repo::submissionFile()->get($submissionFileId);
			error_log('JATSParserPlugin::displayFullText() - submissionFileId: ' . $submissionFileId);
		} else {
			error_log('JATSParserPlugin::displayFullText() - currentLocale not found in fullTexts: ' . $currentLocale);
			$locales = PKP\facades\Locale::getLocales();
			$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocale');
			if (count($fullTexts) > 1) {
				$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocales');
			}

			$html = '<p>' . $msg;
			foreach ($fullTexts as $localeKey => $fullText) {
				$html .= ' <a href="' . $request->url(null, 'user', 'setLocale', $localeKey) . '">' . $locales[$localeKey] . '</a>';
				if ($fullText !== end($fullTexts)) {
					$html .= ', ';
				} else {
					$html .= '.';
				}
			}
			$html .= '</p>';
		}

		if (is_null($html)) return false;

		if ($submissionFileId && $submissionFile) {
			$html = $this->_setSupplImgPath($submissionFile, $html);
		}

		$templateMgr->assign('fullText', $html);
		$output .= $templateMgr->fetch($this->getTemplateResource('articleMainView.tpl'));
		error_log('JATSParserPlugin::displayFullText() - output');
		return false;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @param string $htmlString
	 * @return string
	 * @brief Substitute path to attached images for full-text HTML
	 */
	function _setSupplImgPath(SubmissionFile $submissionFile, string $htmlString): string {

		error_log('JATSParserPlugin::_setSupplImgPath() called');

		$dependentFilesIterator = Repo::submissionFile()
			->getCollector()
			->filterBySubmissionIds([$submissionFile->getData('submissionId')])
			//->filterByFileStages([SUBMISSION_FILE_DEPENDENT])
			->getMany()
			->filter(function($file) use ($submissionFile) {
				return $file->getData('assocType') === ASSOC_TYPE_SUBMISSION_FILE &&
					$file->getData('assocId') === $submissionFile->getId();
			});


		$request = $this->getRequest();
		$imageFiles = [];

		$privateFileManager = new PrivateFileManager();
		
		$genreDao = DAORegistry::getDAO('GenreDAO');
		
		foreach ($dependentFilesIterator as $dependentFile) {

			$genre = $genreDao->getById($dependentFile->getData('genreId'));
			if ($genre->getCategory() !== GENRE_CATEGORY_ARTWORK) continue; // only art works are supported
			if (!in_array($dependentFile->getData('mimetype'), self::getSupportedSupplFileTypes())) continue; // check if MIME type is supported

			error_log('JATSParserPlugin::_setSupplImgPath() - dependentFile: ' . $dependentFile->getData('fileId'));
			$submissionId = $submissionFile->getData('submissionId');

			switch ($request->getRequestedOp()) {
				case 'view':
					error_log('JATSParserPlugin::_setSupplImgPath() Request View');
					$filePath = $request->url(null, 'article', 'downloadFullTextAssoc', array($submissionId, $dependentFile->getData('assocId'), $dependentFile->getData('fileId')));
					error_log('JATSParserPlugin::_setSupplImgPath() - image path: ' . $filePath);
					break;
				case 'editPublication':
					error_log('JATSParserPlugin::_setSupplImgPath() Request editPublication');
					// API Handler cannot process $op, $path or $anchor in url()
					$image = file_get_contents($privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path'));
					error_log('JATSParserPlugin::_setSupplImgPath() - image path: ' . $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path'));
					$imageBase64 = base64_encode($image);
					$filePath = '@' . $imageBase64; // Format, supported by TCPDF
					break;
			}

			$imageFileNames = array_values($dependentFile->getData('name')); // localized
			foreach ($imageFileNames as $imageFileName) {
				if (empty($imageFileName)) continue;
				if (array_key_exists($imageFileName, $imageFiles)) continue;
				$imageFiles[$imageFileName] = $filePath;
			}
		}
		

		if (empty($imageFiles)) return  $htmlString;

		// Solution from HtmlArticleGalleyPlugin::_getHTMLContents
		foreach ($imageFiles as $originalFileName => $filePath) {
			$pattern = preg_quote(rawurlencode($originalFileName));

			$htmlString = preg_replace(
				'/([Ss][Rr][Cc]|[Hh][Rr][Ee][Ff]|[Dd][Aa][Tt][Aa])\s*=\s*"([^"]*' . $pattern . ')"/',
				'\1="' . $filePath . '"',
				$htmlString
			);
		}


		error_log('JATSParserPlugin::_setSupplImgPath() return 200');
		return $htmlString;
	}

	/**
	 * @return array
	 * @brief get the list of types of files that are dependent from an original JATS XML (from which full-text was generated) and are accessible to public
	 */
	public static function getSupportedSupplFileTypes() {
		return [
			'image/png',
			'image/jpeg'
		];
	}

	public static function getSupportedCitationStyles() {
		return [
			[
				'id' => 'acm-sig-proceedings',
				'title' => 'plugins.generic.jatsParser.style.acm-sig-proceedings',
			],
			[
				'id' => 'acs-nano',
				'title' => 'plugins.generic.jatsParser.style.acs-nano',
			],
			[
				'id' => 'apa',
				'title' => 'plugins.generic.jatsParser.style.apa',
			],
			[
				'id' => 'associacao-brasileira-de-normas-tecnicas',
				'title' => 'plugins.generic.jatsParser.style.associacao-brasileira-de-normas-tecnicas',
			],
			[
				'id' => 'chicago-author-date',
				'title' => 'plugins.generic.jatsParser.style.chicago-author-date',
			],
			[
				'id' => 'harvard-cite-them-right',
				'title' => 'plugins.generic.jatsParser.style.harvard-cite-them-right',
			],
			[
				'id' => 'ieee',
				'title' => 'plugins.generic.jatsParser.style.ieee',
			],
			[
				'id' => 'modern-language-association',
				'title' => 'plugins.generic.jatsParser.style.modern-language-association',
			],
			[
				'id' => 'turabian-fullnote-bibliography',
				'title' => 'plugins.generic.jatsParser.style.turabian-fullnote-bibliography',
			],
			[
				'id' => 'vancouver',
				'title' => 'plugins.generic.jatsParser.style.vancouver',
			],
		];
	}

	/**
	 * @param string $hookname
	 * @param array $args
	 * @return bool
	 * @brief theme-specific styles for galley and article landing page
	 */
	function themeSpecificStyles(string $hookname, array $args) {
		error_log('JATSParserPlugin::themeSpecificStyles() called');
		$templateMgr = $args[0];
		$template = $args[1];

		if ($template !== "frontend/pages/article.tpl") return false;

		$request = $this->getRequest();
		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

		$themePlugins = PluginRegistry::getPlugins('themes');
		foreach ($themePlugins as $themePlugin) {
			if ($themePlugin->isActive()) {
				$parentTheme = $themePlugin->parent;
				// Chances are that child theme of a Default also need this styling
				if ($themePlugin->getName() == "defaultthemeplugin" || ($parentTheme && $parentTheme->getName() == "defaultthemeplugin")) {
					$templateMgr->addStyleSheet('jatsParserThemeStyles', $baseUrl . '/resources/styles/default/article.css');
				}
			}
		}

		return false;
	}

	/**
	 * @return void
	 * @brief iterate through all submissions and add full-text from  galleys
	 */
	public function importGalleys() {
		error_log('JATSParserPlugin::importGalleys() called');

		//$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFileDao = Repo::submissionFile()->dao; /* @var $submissionFileDao SubmissionFileDAO */
		
		
		$request = $this->getRequest();
		$context = $request->getContext();
		$user = $request->getUser();
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$fileManager = new PrivateFileManager();

		$submissions = Services::get('submission')->getMany([
			'contextId' => $context->getId(),
			'stageIds' => [
				WORKFLOW_STAGE_ID_PRODUCTION
			]
		]);

		foreach ($submissions as $submission) {
			$publication = $submission->getCurrentPublication();
			$galleys = $publication->getData('galleys');

			if (empty($galleys)) continue;

			foreach ($galleys as $galley) {
				if (!in_array($galley->getFileType(), array("application/xml", "text/xml"))) continue;

				$galleyLocale = $galley->getLocale();
				$localizedFullTextFileSetting = $publication->getData('jatsParser::fullTextFileId', $galleyLocale);
				if ($localizedFullTextFileSetting) continue;

				$submissionFile = $galley->getFile();
				/** @var $submissionFile SubmissionFile */
				$document = new Document($fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'));
				if (empty($document->getArticleSections())) continue;

				// Copy galley as a production ready submission file
				$submissionDir = Services::get('submissionFile')->getSubmissionDir($request->getContext()->getId(), $submission->getId());
				$fileId = Services::get('file')->add(
					$fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'),
					$submissionDir . '/' . uniqid() . '.xml'
				);

				$newSubmissionFile = $submissionFileDao->newDataObject();
				$newSubmissionFile->setAllData(
					[
						'fileId' => $fileId,
						'uploaderUserId' => $user->getId(),
						'fileStage' => SUBMISSION_FILE_PRODUCTION_READY,
						'submissionId' => $submission->getId(),
						'genreId' => $submissionFile->getData('genreId'),
						'name' => $submissionFile->getData('name'),
					],
				);
				$newSubmissionFile = Services::get('submissionFile')->add($newSubmissionFile, $request);

				// copy and attach dependent files, only images are supported
				$assocFiles = Services::get('submissionFile')->getMany(
					[
						'assocTypes' => [ASSOC_TYPE_SUBMISSION_FILE],
						'assocIds' => [$submissionFile->getId()],
						'submissionIds' => [$submission->getId()],
						'fileStages' => [SUBMISSION_FILE_DEPENDENT],
						'includeDependentFiles' => true,
					]
				);
				foreach ($assocFiles as $assocFile) {
					/** @var $assocFile SubmissionFile */
					if (in_array($assocFile->getData('mimetype'), $this->getSupportedSupplFileTypes())) {
						$newAssocFileId = Services::get('file')->add(
							$fileManager->getBasePath() . DIRECTORY_SEPARATOR . $assocFile->getData('path'),
							$submissionDir . '/' . uniqid() . '.' . $fileManager->parseFileExtension($assocFile->getData('path'))
						);

						$assocSubmissionFile = $submissionFileDao->newDataObject();
						$assocSubmissionFile->setAllData([
							'fileId' => $newAssocFileId,
							'assocId' => $newSubmissionFile->getId(),
							'assocType' => ASSOC_TYPE_SUBMISSION_FILE,
							'uploaderUserId' => $user->getId(),
							'fileStage' =>  SUBMISSION_FILE_DEPENDENT,
							'submissionId' => $submission->getId(),
							'genreId' => $assocFile->getData('genreId'),
							'name' => $assocFile->getData('name'),
							'caption' => $assocFile->getData('caption'),
							'copyrightOwner' => $assocFile->getData('copyrightOwner'),
                            'credit' => $assocFile->getData('credit'),
                            'terms' =>$assocFile->getData('terms'),
						]);
						Services::get('submissionFile')->add($assocSubmissionFile, $request);
					}
				}

				$htmlDocument = new HTMLDocument($document);
				$htmlString = $htmlDocument->saveAsHTML();
				$publication->setData('jatsParser::fullTextFileId', $newSubmissionFile->getId(), $galleyLocale);
				$publication->setData('jatsParser::fullText', $htmlString, $galleyLocale);
				$publicationDao->updateObject($publication);
			}
		}
	}

	/**
	 * @param $hookName string Form::config::before
	 * @param $form FormComponent The form object
	 */
	public function addCitationsFormFields(string $hookName, FormComponent $form): void {
		error_log('JATSParserPlugin::addCitationsFormFields() called');
		if ($form->id !== 'citations' || !empty($form->errors)) return;

		$path = parse_url($form->action)['path'];
		if (!$path) return;

		$args = explode('/', $path);
		$publicationId = 0;
		if ($key = array_search('publications', $args)) {
			if (array_key_exists($key+1, $args)) {
				$publicationId = intval($args[$key+1]);
			}
		}

		if (!$publicationId) return;

		error_log('JATSParserPlugin::addCitationsFormFields() called for publicationId: ' . $publicationId);

		//$publication = Services::get('publication')->get($publicationId);
		$publication = Repo::publication()
			->get($publicationId);

		if (!$publication) return;

		$submissionFileIds = array_unique($publication->getData('jatsParser::fullTextFileId') ?? []);
		if (empty($submissionFileIds)) return;

		$submissionFiles = [];
		foreach ($submissionFileIds as $submissionFileId) {
			$submissionFile = Repo::submissionFile()->get($submissionFileId);
			if ($submissionFile) {
				$submissionFiles[] = $submissionFile;
			}
		}


		if (empty($submissionFiles)) return;

		$options = [];
		foreach ($submissionFiles as $submissionFile) {
			$options[] = [
				'value' => $submissionFile->getId(),
				'label' => $submissionFile->getLocalizedData('name'),
			];
		}

		$options[] = [
			'value' => null,
			'label' => __('common.default'),
		];

		$form->addField(new \PKP\components\forms\FieldOptions('jatsParser::references', [
			'label' => __('plugins.generic.jatsParser.publication.jats.references.label'),
			'description' => __('plugins.generic.jatsParser.publication.jats.references.description'),
			'type' => 'radio',
			'options' => $options,
			'value' => null
		]));

	}

	/**
	 * @param Publication $publication
	 * @param string $locale
	 * @param string $htmlString
	 * @return string
	 * @brief set footnotes for PDF galley
	 */
	private function _setFootnotes(Publication $publication, string $locale, string $htmlString): string {

		error_log('JATSParserPlugin::_setFootnotes() called');

		// Get the JATS file ID for this locale
		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $locale);
		if (!$jatsFileId) return $htmlString;
		
		//$submissionFile = Services::get('submissionFile')->get($jatsFileId);
		$submissionFile = Repo::submissionFile()->get($jatsFileId);
		error_log('JATSParserPlugin::_setFootnotes() - submissionFile');
		
		if (!$submissionFile) return $htmlString;
		
		// Get the path to the JATS XML file
		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();
		$jatsFilePath = $fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path');
		
		// Load the JATS XML document
		$dom = new DOMDocument();
		$dom->load($jatsFilePath);
		$xpath = new DOMXPath($dom);
		
		// Get all footnotes from the fn-group in the back section
		$footnotes = [];
		$fnGroups = $xpath->query('//back/fn-group/fn');
		
		if ($fnGroups->length === 0) {
			return $htmlString; // No footnotes found
		}
		
		// Add footnotes container with semantic class instead of inline styles
		$htmlString .= "\n<div class=\"footnotes-container\">";
		$htmlString .= '<h2>' . __('plugins.generic.jatsParser.article.footnotes.title') . '</h2>';
		
		// Process each footnote
		foreach ($fnGroups as $fn) {
			$fnId = $fn->getAttribute('id');
			$label = '';
			
			// Get the footnote label
			$labelNodes = $xpath->query('.//label', $fn);
			if ($labelNodes->length > 0) {
				$label = $labelNodes->item(0)->nodeValue;
			}
			
			// Get the footnote content
			$content = '';
			$pNodes = $xpath->query('.//p', $fn);
			if ($pNodes->length > 0) {
				foreach ($pNodes as $p) {
					// Process xrefs in the paragraph before getting HTML content
					$xrefs = $xpath->query('.//xref', $p);
					foreach ($xrefs as $xref) {
						// Get xref attributes
						$xrefId = $xref->getAttribute('id');
						$rid = $xref->getAttribute('rid');
						$refType = $xref->getAttribute('ref-type');
						
						// Create a new anchor element to replace the xref
						$anchor = $dom->createElement('a');
						$anchor->setAttribute('id', $xrefId);
						$anchor->setAttribute('href', '#' . $rid);
						$anchor->setAttribute('data-ref-type', $refType);
						$anchor->setAttribute('class', 'citation-link');
						
						// Copy the text content
						$anchor->nodeValue = $xref->nodeValue;
						
						// Replace xref with anchor
						$xref->parentNode->replaceChild($anchor, $xref);
					}
					
					// Get HTML content of the modified paragraph
					$contentFragment = $dom->saveHTML($p);
					// Remove the paragraph tags to get just the inner content
					$content .= preg_replace('/<\/?p[^>]*>/', '', $contentFragment);
				}
			}
			
			// Format the footnote using semantic classes instead of inline styles
			$htmlString .= '<div class="footnote-item" id="fn-' . htmlspecialchars($fnId) . '">';
			$htmlString .= '<span class="footnote-label">' . htmlspecialchars($label) . ' </span>';
			$htmlString .= '<span class="footnote-content">' . $content . '</span>';
			$htmlString .= '</div>';
		}
		
		$htmlString .= '</div>';
		
		return $htmlString;
	}

	private function _importCitations(HTMLDocument $htmlDocument, Publication $newPublication): void {
		$refs = $htmlDocument->getRawReferences();
		$publicationId = $newPublication->getId();
		$citationDao = DAORegistry::getDAO('CitationDAO'); /** @var $citationDao CitationDAO */

		$citationDao->deleteByPublicationId($publicationId);
		$rawCitations = '';

		foreach ($refs as $key => $ref) {
			$rawCitations .= $ref . "\n";
		}

		$newPublication->setData('citationsRaw', $rawCitations);
	}

}
