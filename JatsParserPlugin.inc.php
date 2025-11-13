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

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.jatsParser.classes.JATSParserDocument');
import('plugins.generic.jatsParser.classes.components.forms.PublicationJATSUploadForm');
import('lib.pkp.classes.citation.Citation');
import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.file.PKPPublicFileManager');
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.RedirectAction');

use PKP\decision\Decision;
use PKP\citation\CitationListTokenizerFilter;
use JATSParser\PDF\PDFConfig\Translations;
use JATSParser\PDF\PDFConfig\Configuration;
use JATSParser\Body\Document;
use JATSParser\HTML\Document as HTMLDocument;
use \PKP\components\forms\FormComponent;
use APP\facades\Repo;
use PKP\core\JSONMessage;
use JATSParser\Body\Document as JATSDocument;
use PKP\components\forms\Processors\ReferencesProcessor;

use APP\core\Request;
use APP\notification\NotificationManager;
use JATSParser\TemplateHandler\HTML\HTMLOutputStrategy;
use JATSParser\TemplateHandler\PDF\PDFCreationService;
use JATSParser\TemplateHandler\PDF\PDFOutputStrategy;
use PKP\locale\Locale;
use PKP\galley\Galley;

use PKP\db\DAORegistry;
use PKP\facades\Locale as FacadesLocale;
use PKP\file\PrivateFileManager;

define("CREATE_PDF_QUERY", "download=pdf");

class JatsParserPlugin extends GenericPlugin
{

	function register($category, $path, $mainContextId = null)
	{
		if (parent::register($category, $path, $mainContextId)) {

			if ($this->getEnabled()) {
				HookRegistry::add('Template::Workflow::Publication', array($this, 'publicationTemplateData'));
				HookRegistry::add('Schema::get::publication', array($this, 'addToSchema'));
				HookRegistry::add('LoadHandler', array($this, 'loadFullTextAssocHandler'));
				HookRegistry::add('Publication::edit', array($this, 'editPublicationFullText'));
				HookRegistry::add('Templates::Article::Main', array($this, 'displayFullText'));
				HookRegistry::add('TemplateManager::display', array($this, 'themeSpecificStyles'));
				HookRegistry::add('Form::config::before', array($this, 'addCitationsFormFields'));
				HookRegistry::add('Publication::edit', array($this, 'editPublicationReferences'));
				HookRegistry::add('Publication::edit', array($this, 'createPdfGalley'));
			}

			return true;
		}
		return false;
	}

	public function setEnabled($enabled)
	{
		parent::setEnabled($enabled);

		if ($enabled) {
			$contextId = $this->getCurrentContextId();
			$fileManager = new PrivateFileManager();
			$path = $fileManager->getBasePath() . "/journals/$contextId/jatsParser_templates";

			if (!file_exists($path))
				mkdir($path, 0751, true);
		}
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName()
	{
		return __('plugins.generic.jatsParser.displayName');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription()
	{
		return __('plugins.generic.jatsParser.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb)
	{
		$router = $request->getRouter();
		return array_merge(
			$this->getEnabled() ? array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
				new LinkAction(
					'pdfSettings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'pdfSettings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('plugins.generic.jatsParser.pdf.settings.button'),
					null
				),
				new LinkAction(
					'pdfPartsSettings',
					// ** CAMBIO CLAVE 1: USAR RedirectAction **
					new RedirectAction(
						$router->url($request, null, null, 'manage', null, array('verb' => 'pdfPartsSettings', 'plugin' => $this->getName(), 'category' => 'generic'))
					),
					__('plugins.generic.jatsParser.pdf.settings.tab'),
					null
				),
			) : array(),
			parent::getActions($request, $verb)
		);
	}
	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request)
	{
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

			case 'pdfSettings':
				$context = $request->getContext();
				$this->import('JatsParserPdfSettingsForm');
				$form = new JatsParserPdfSettingsForm($this, $context->getId());
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
			case 'pdfPartsSettings':
				$context = $request->getContext();
				$this->import('JatsParserPartsForm');
				$form = new JatsParserPartsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$router = $request->getRouter();
						$router->url($request, null, null, 'manage', null, array('verb' => 'pdfPartsSettings', 'plugin' => $this->getName(), 'category' => 'generic'));
					}
				}

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
				$templateMgr->assign('jatsParserPlugin', $this);

				$form->display($request);

				return true;
			case 'resetPart':
				$context = $request->getContext();
				$this->import('JatsParserPartsForm');
				$form = new JatsParserPartsForm($this, $context->getId());

				$part = $request->getUserVar('partName');
				$template = $request->getUserVar('template');
				$fileManager = new PrivateFileManager();
				$path = $fileManager->getBasePath() . "/journals/" . $context->getId() . "/jatsParser_templates/$template/";

				$fileName = basename((string) $part);
				$targetPath = $path . $fileName;
				
				unlink($targetPath);

				file_put_contents(__DIR__ . "/testFile.txt", $targetPath);

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
				$templateMgr->assign('jatsParserPlugin', $this);

				$form->display($request);

				return true;
		}
		return parent::manage($args, $request);
	}

	//Get an array of OJS metadata to be used in the PDF generation
	private function getMetadata($publication, $localeKey, $request, $htmlString)
	{

		//$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$submission = Repo::submission()->get($publication->getData('submissionId'));
		$context = $request->getContext(); /* @var $context Journal */
		$journal = $request->getContext();

		$issueIdentification = "";
		if ($publication->getData('issueId')) {
			$issue = Repo::issue()->get($publication->getData('issueId'));
			$issueIdentification = $issue->getIssueIdentification();
			$issueVolume = $issue->getData('volume');
			$issueNumber = $issue->getData('number');
			$issueYear = $issue->getData('year');
		}

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
			if ($decision->getData('stageId') === WORKFLOW_STAGE_ID_EXTERNAL_REVIEW && $decision->getData('decision') === Decision::ACCEPT) {
				$acceptedDate = $decision->getData('dateDecided'); // Get accepted date of accepted revision stage
				break;
			}

			if ($decision->getData('stageId') === WORKFLOW_STAGE_ID_EXTERNAL_REVIEW && $decision->getData('decision') === Decision::DECLINE) {
				$acceptedDate = $decision->getData('dateDecided');
				break; // Get accepted date of rejected revision stage
			}
		}

		//Obtener la fecha de aceptación del envío si se saltea la etapa de revisión
		if (!$acceptedDate) {
			$acceptedDate = $submission->getDateStatusModified();
		}

		$licenseUrl = !empty($publication->getData('licenseUrl')) ? $publication->getData('licenseUrl') : $journal->getData('licenseUrl');

		// Separar por "/"
		list($anio, $mes, $dia) = explode('/', str_replace('-', '/', $submission->getDatePublished()));
		// Reordenar como día/mes/año
		$datePublished = ($dia && $mes && $anio) ? "$dia/$mes/$anio" : '';

		$authors = array_values(iterator_to_array($publication->getData('authors')));
		$simplifiedAuthors = array_map(function ($author) {
			return $author->_data; // Extrae solo el contenido de '_data', así es más sencillo el acceso desde todos lados
		}, $authors);

		$metadata = [
			'publication_pages' => $publication->getData('pages'),
			'section_title' => $section?->getLocalizedTitle(),
			'citation_style' => $plugin->getSetting($context->getId(), 'citationStyle'),
			'publication_id' => $publication->getId(),
			'doi' => $publication->getDoi(),
			'journal_id' => $journal->getId(),
			'authors' => $simplifiedAuthors,
			'online_issn' => $journal->getData('onlineIssn'),
			'journal_title' => $journal->getLocalizedData('name'),
			'journal_issue' => $publication->getData('issueId'),
			'locale_key' => FacadesLocale::getLocale(),
			'article_locale_key' => $publication->getData('locale'),
			'journal_thumbnail' => $journal->getLocalizedData('journalThumbnail'),
			'full_title' => $publication->getLocalizedFullTitle($localeKey),
			'license_url' => $licenseUrl,
			'article_title' => $publication->getLocalizedData('title'),
			'submission' => $submission,
			'date_submitted' => date('d/m/Y', strtotime($submission->getDateSubmitted())),
			'date_accepted' => $acceptedDate ? date('d/m/Y', strtotime($acceptedDate)) : '',
			'date_published' => $datePublished,
			'journal_data' => $issueIdentification, // Includes volume, number, year of a journal.
			'issue_volume' => $issueVolume ?? '',
			'issue_number' => $issueNumber ?? '',
			'issue_year' => $issueYear ?? '',
			'user_groups' => $userGroups,
			'contributors' => null, //$publication->getAuthorString($userGroups),
			'subject' => $publication->getLocalizedData('subject', $localeKey),
			'abstract_texts' => $publication->getData('abstract'), // Returns an array like this: ['es' => 'Resumen', 'en' => 'Abstract']
			'translations' => Translations::getTranslations(),
			'keywords_texts' => $publication->getData('keywords'),
			'plugin_path' => $this->getPluginPath(),
			'journal_url' => $request->getBaseUrl() . '/' . $journal->getPath(),
			'titles' => $publication->getData('title'),
			'subtitles' => $publication->getData('subtitle'),
			'editorial' => $context->getLocalizedData('institution'),
			'prefixes' => $publication->getData('prefix'),
			'lang_keys' => $context->getSupportedLocales(), # Retorna todas las claves de idioma que estén configuradas en la revista: ['es', 'en']
		];

		return $metadata;
	}

	public function getConfiguration($request)
	{
		$context = $request->getContext();

		$ojsConfiguration = [
			'margin_top' => $this->getSetting($context->getId(), 'pdfTopMargin'),
			'margin_bottom' => $this->getSetting($context->getId(), 'pdfBottomMargin'),
			'margin_left' => $this->getSetting($context->getId(), 'pdfLeftMargin'),
			'margin_right' => $this->getSetting($context->getId(), 'pdfRightMargin'),
			'selected_template' => $this->getSetting($context->getId(), 'selectedTemplate'),
		];

		return $ojsConfiguration;
	}

	public function getAvailablePdfTemplates($request)
	{
		$path = __DIR__ . "/templates/SUMARC/";
		$items = scandir($path);

		$templatesDir = [];
		$templatesDir[] = [
			'id' => 'plugins.generic.jatsParser.pdf.empty.template',
			'title' => 'plugins.generic.jatsParser.pdf.empty.template'
		];

		$fileManager = new PrivateFileManager();
		$journalId = $request->getContext()->getId();

		foreach ($items as $item) {
			if ($item != '.' && $item != '..') { # Excluyo . y ..
				if (is_dir($path . '/' . $item)) {
					if (PDFCreationService::checkTemplateIntegrity($item, $this, $fileManager, $journalId)) {
						$templatesDir[] = [
							'id' => $item,
							'title' => $item,
						];
					}
				}
			}
		}

		return $templatesDir;
	}

	/**
	 * @param $article Submission
	 * @param $request PKPRequest
	 * @param $htmlDocument HTMLDocument
	 * @param $issue Issue
	 * @param
	 */
	private function pdfCreation(string $htmlString, Publication $publication, Request $request, string $localeKey, int $fileId): string
	{
		$metadata = $this->getMetadata($publication, $localeKey, $request, $htmlString);
		$ojsConfiguration = $this->getConfiguration($request);
		$configuration = new Configuration($metadata);
		$fileMgr = new PrivateFileManager();
		$journalId = $request->getContext()->getId();

		# $htmloutput = HTMLOutputStrategy::class; # Sorpresa sorpresa, adapté la estrategia de salida de los PDFs para generar una salida en HTML, es probable que haya que meter algo de mano para que termine de ser funcional, pero el desarrollo está prácticamente hecho. Todo el procesamiento interno ya estaría acomdoado 👍 

		$outputStrategy = PDFOutputStrategy::class; # Lo que hablamos fue que esto quede así hasta que se necesite hace un selector de estrategias, trabajo para otra persona
		# Pero, esencialmente, sería un selector que te devuelve el FQCN de la estrategia a usar, en este caso PdfOutputStrategy::class retorna algo del estilo JATSParser\TemplateHandler\PDF\PdfOutputStrategy
		# Nótese que la estrategia a usar debe guardarse en la DB ya que es una configuración que se mantiene, no se selecciona a la hora de escupir el PDF sino desde la config del plugin en OJS. Atte: Leito

		# file_put_contents(__DIR__ . "/htmlTest.html", $htmloutput::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration));
		return $outputStrategy::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration);
	}

	/**
	 * Add a property to the publication schema
	 *
	 * @param $hookName string `Schema::get::publication`
	 * @param $args [[
	 * 	@option object Publication schema
	 * ]]
	 */
	public function addToSchema($hookName, $args)
	{
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
	function publicationTemplateData(string $hookname, array $args): void
	{
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

		$locales = array_map(function ($localeKey) use ($localeNames) {
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
	function loadFullTextAssocHandler($hookName, $args)
	{
		$page = $args[0];
		$op = $args[1];

		if ($page == 'article' && $op == 'downloadFullTextAssoc') {
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
	function editPublicationFullText(string $hookname, array $args)
	{
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
	function getCitationStyle(Journal $context): string
	{
		$contextId = $context->getId();

		$citationStyle = $this->getSetting($contextId, 'citationStyle');

		if ($citationStyle) return $citationStyle;

		$pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');
		$cslPluginSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'CitationStyleLanguagePlugin');

		if (
			$cslPluginSettings &&
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
	function editPublicationReferences(string $hookname, array $args)
	{
		$newPublication = $args[0]; /* @var $newPublication Publication */
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

	function createPdfGalley(string $hookname, array $args)
	{

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
				import('lib.pkp.classes.file.PrivateFileManager');
				$fullText = $this->_setSupplImgPath($jatsSubmissionFile, $fullText);
				$privateFileManager = new PrivateFileManager();
				$jatsFilePath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $jatsSubmissionFile->getData('path');
			}

			// Set references y footnotes
			$fullText = $this->_setReferences($newPublication, $localeKey, $fullText, $jatsFilePath);
			$fullText = $this->_setFootnotes($newPublication, $localeKey, $fullText);

			// Convertir a PDF
			$pdf = $this->pdfCreation($fullText, $newPublication, $request, $localeKey, $jatsFileId);

			// Crear galley
			$galley = $this->createGalley($localeKey, $newPublication);


			// Obtener el galley usando Repo
			$galley = Repo::galley()
				->getCollector()
				->filterByPublicationIds([$newPublication->getId()])
				->getMany()
				->first(function ($g) use ($galley) {
					return $g->getBestGalleyId() === $galley;
				});

			if (!$galley) continue;

			// Crear archivo de sumisiÃ³n del PDF
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
	function createGalley(string $galleyLocale, Publication $publication): int
	{

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
	private function _setPdfSubmissionFile(string $pdfBinaryString, Publication $publication, Galley $galley)
	{

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

		$genreDao = DAORegistry::getDAO('GenreDAO');
		/** @var GenreDAO $genreDao */
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
			]
		);
		//$submissionFile = Services::get('submissionFile')->add($submissionFile, $request);
		$submissionFileId = Repo::submissionFile()->add($submissionFile, $request);
		$submissionFile = Repo::submissionFile()->get($submissionFileId);


		//$submissionFile = Repo::submissionFile()->get($submissionFileId);
		unlink($tmpFile); // remove temporary file
		return $submissionFile;
	}

	/**
	 * @param Publication $publication
	 * @param string $locale
	 * @param string $htmlString
	 * @return string
	 * @brief set references for PDF galley
	 */
	private function _setReferences(Publication $publication, string $locale, string $htmlString, $jatsPath): string
	{

		#$rawCitations = $publication->getData('citationsRaw'); //References
		#if (empty($rawCitations)) return $htmlString;

		// Use OJS raw citations tokenizer
		#$citationTokenizer = new CitationListTokenizerFilter();
		#$formattedRefs = $citationTokenizer->execute($rawCitations);

		$numberedCitations = Configuration::getNumberedReferences();
		$context = Application::get()->getRequest()->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'jatsparserplugin'); /* @var $plugin JATSParserPlugin */
		$citationStyle = $plugin->getSetting($context->getId(), 'citationStyle');

		//Obtain xml jats file
		// Create a JATSDocument instance
		$jatsDocument = new JATSDocument($jatsPath);

		// Get the references from the JATS document
		$references = $jatsDocument->getReferences();

		// Create an HTML document to handle formatting
		$htmlDoc = new \JATSParser\HTML\Document($jatsDocument);
		// Set the references with the desired citation style

		$locale_key = $context->getPrimaryLocale();
		$formattedLocaleKey = str_replace('_', '-', $locale_key);
		$citationStyle = $plugin->getSetting($context->getId(), 'citationStyle');

		$htmlDoc->setReferences($citationStyle, $formattedLocaleKey, false);

		// Get raw formatted references
		$formattedRefs = $htmlDoc->getRawReferences();

		$refsProcessor = new ReferencesProcessor($formattedRefs);
		$formattedRefs = $refsProcessor->getNumberedReferences();

		if (!is_array($formattedRefs) || empty($formattedRefs)) return $htmlString;
		$htmlString .= "\n";

		// Add container with semantic class instead of inline styles
		$htmlString .= "\n<div class=\"references-section\">";
		$htmlString .= '<h2>' . __('plugins.generic.jatsParser.article.references.title') . '</h2>';

		// Add container for the references with citation style as data attribute
		$containerTag = in_array($citationStyle, $numberedCitations) ? 'ol' : 'div';
		$htmlString .= '<' . $containerTag . ' id="references" class="citation-list" data-style="' . $citationStyle . '">';
		$htmlString .= "\n";

		foreach ($formattedRefs as $id => $reference) {
			// Format the citation string, applying the URL formatting
			$formattedCitation = $this->_formatUrlsInText($reference);

			$htmlString .= "\t";
			// Apply semantic class to the list item
			$htmlString .= '<li class="citation-item" id="' . $id . '">' . $formattedCitation . '</li>';
			$htmlString .= "<br/>\n";
		}
		$htmlString .= '</' . $containerTag . '>';

		// Close the container
		$htmlString .= '</div>';

		return $htmlString;
	}

	/**
	 * @param string $text => The text of the reference to be formatted.
	 * @return string
	 * @brief Detect and format URLs in the given reference text with a specific style.
	 */
	private function _formatUrlsInText(string $text): string
	{
		// Regular expression to detect URLs that start with http://, https://, or ftp://
		$urlPattern = '/(https?|ftp):\/\/[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';

		// Detect URLs that start with www. too
		$wwwPattern = '/(?<![\w.])www\.[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/';

		// Search and replace URLs with semantic classes instead of inline styles
		$text = preg_replace_callback($urlPattern, function ($matches) {
			return '<span class="citation-url">' . $matches[0] . '</span>';
		}, $text);

		// Search and replace URLs that start with www. with semantic classes
		$text = preg_replace_callback($wwwPattern, function ($matches) {
			return '<span class="citation-url">' . $matches[0] . '</span>';
		}, $text);

		return $text;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @return HTMLDocument
	 * @brief retrieves PHP DOM representation of the article's full-text
	 */
	public function getFullTextFromJats(SubmissionFile $submissionFile): HTMLDocument
	{
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
	function displayFullText(string $hookname, array $args)
	{
		$templateMgr = &$args[1];
		$output = &$args[2];
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
		} else {
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
		return false;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @param string $htmlString
	 * @return string
	 * @brief Substitute path to attached images for full-text HTML
	 */
	function _setSupplImgPath(SubmissionFile $submissionFile, string $htmlString): string
	{

		$dependentFilesIterator = Repo::submissionFile()
			->getCollector()
			->filterBySubmissionIds([$submissionFile->getData('submissionId')])
			//->filterByFileStages([SUBMISSION_FILE_DEPENDENT])
			->getMany()
			->filter(function ($file) use ($submissionFile) {
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

			$submissionId = $submissionFile->getData('submissionId');

			switch ($request->getRequestedOp()) {
				case 'view':
					$filePath = $request->url(null, 'article', 'downloadFullTextAssoc', array($submissionId, $dependentFile->getData('assocId'), $dependentFile->getData('fileId')));
					break;
				case 'editPublication':
					// API Handler cannot process $op, $path or $anchor in url()
					$imgPath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path');
					// $image = file_get_contents($imgPath);
					// error_log('JATSParserPlugin::_setSupplImgPath() - image path: ' . $imgPath);

					// $finfo = finfo_open(FILEINFO_MIME_TYPE);
					// $mimeType = finfo_file($finfo, $imgPath);
					// finfo_close($finfo);

					// #$imageBase64 = base64_encode($image);
					// #$filePath = 'data:' . $mimeType . ';base64,@' . $imageBase64; # Dejo todo esto comentado por si se desea volver a usar Base64
					$filePath = $imgPath;
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

		return $htmlString;
	}

	/**
	 * @return array
	 * @brief get the list of types of files that are dependent from an original JATS XML (from which full-text was generated) and are accessible to public
	 */
	public static function getSupportedSupplFileTypes()
	{
		return [
			'image/png',
			'image/jpeg'
		];
	}

	public static function getSupportedCitationStyles()
	{
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
	function themeSpecificStyles(string $hookname, array $args)
	{
		$templateMgr = $args[0];
		$template = $args[1];

		// Rutas absolutas para debug
		$cssPath = __DIR__ . '/app/citationTable.css';
		$jsPath = __DIR__ . '/app/citationTable.js';

		$templateMgr->addJavaScript(
			'citationTable',
			$jsPath,
		);

		$templateMgr->addStyleSheet(
			'citationTableCss',
			$cssPath,
		);
		////////////////////////////////////////////

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
	public function importGalleys()
	{

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
							'terms' => $assocFile->getData('terms'),
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
	public function addCitationsFormFields(string $hookName, FormComponent $form): void
	{
		if ($form->id !== 'citations' || !empty($form->errors)) return;

		$path = parse_url($form->action)['path'];
		if (!$path) return;

		$args = explode('/', $path);
		$publicationId = 0;
		if ($key = array_search('publications', $args)) {
			if (array_key_exists($key + 1, $args)) {
				$publicationId = intval($args[$key + 1]);
			}
		}

		if (!$publicationId) return;

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
	private function _setFootnotes(Publication $publication, string $locale, string $htmlString): string
	{

		// Get the JATS file ID for this locale
		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $locale);
		if (!$jatsFileId) return $htmlString;

		//$submissionFile = Services::get('submissionFile')->get($jatsFileId);
		$submissionFile = Repo::submissionFile()->get($jatsFileId);

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

	private function _importCitations(HTMLDocument $htmlDocument, Publication $newPublication): void
	{
		$refs = $htmlDocument->getRawReferences();
		$publicationId = $newPublication->getId();
		$citationDao = DAORegistry::getDAO('CitationDAO');
		/** @var $citationDao CitationDAO */

		$citationDao->deleteByPublicationId($publicationId);
		$rawCitations = '';

		foreach ($refs as $key => $ref) {
			$ref = str_replace(['<i>', '</i>'], '', $ref);
			$rawCitations .= $ref . "\n\n";
		}

		$newPublication->setData('citationsRaw', $rawCitations);
	}
}
