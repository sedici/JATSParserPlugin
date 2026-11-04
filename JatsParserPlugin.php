<?php

namespace APP\plugins\generic\jatsParser;

/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.php
 *
 * Copyright (c) 2017-2026 Vitalii Bezsheiko / SEDICI
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserPlugin
 * @ingroup plugins_generic_jatsParser
 *
 */

require_once __DIR__ . '/JATSParser/vendor/autoload.php';
require_once __DIR__ . '/JATSParser/src/JATSParser/PDF/PDFConfig/Configuration.php';
require_once __DIR__ . '/JATSParser/src/JATSParser/PDF/PDFConfig/Translations.php';
require_once __DIR__ . '/classes/JATSParserDocument.php';
require_once __DIR__ . '/classes/components/forms/PublicationJATSUploadForm.php';
require_once __DIR__ . '/classes/daos/CustomPublicationSettingsDAO.php';
require_once __DIR__ . '/classes/Processors/ReferencesProcessor.php';

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\journal\Section;
use APP\notification\NotificationManager;
use APP\publication\Publication;
use APP\template\TemplateManager;
use DOMDocument;
use DOMXPath;
use JATSParser\Body\Document;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;
use JATSParser\PDF\PDFConfig\Configuration;
use JATSParser\PDF\PDFConfig\Translations;
use JATSParser\TemplateHandler\EPUB\EPUBOutputStrategy;
use JATSParser\TemplateHandler\HTML\HTMLOutputStrategy;
use JATSParser\TemplateHandler\PDF\PDFCreationService;
use JATSParser\TemplateHandler\PDF\PDFOutputStrategy;
use PKP\citation\CitationListTokenizerFilter;
use PKP\components\forms\FormComponent;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\decision\Decision;
use PKP\facades\Locale as FacadesLocale;
use PKP\file\PKPPublicFileManager;
use PKP\file\PrivateFileManager;
use PKP\galley\Galley;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\submission\Genre;
use PKP\submissionFile\SubmissionFile;

if (!defined('CREATE_PDF_QUERY')) {
	define("CREATE_PDF_QUERY", "download=pdf");
}

class JatsParserPlugin extends GenericPlugin
{

	function register($category, $path, $mainContextId = null)
	{
		if (parent::register($category, $path, $mainContextId)) {
			$contextId = ($mainContextId === null) ? $this->getCurrentContextId() : $mainContextId;
			if ($this->getEnabled($contextId)) {
				Hook::add('Schema::get::publication', [$this, 'addToSchema']);
				Hook::add('LoadHandler', [$this, 'loadFullTextAssocHandler']);
				Hook::add('Publication::edit', [$this, 'editPublicationFullText']);
				Hook::add('Templates::Article::Main', [$this, 'displayFullText']);
				Hook::add('TemplateManager::display', [$this, 'themeSpecificStyles']);
				Hook::add('TemplateManager::display', [$this, 'workflowCustomAssets']);
				Hook::add('Form::config::before', [$this, 'addCitationsFormFields']);
				Hook::add('Publication::edit', [$this, 'editPublicationReferences']);
				Hook::add('Publication::edit', [$this, 'createAllGalleys']);
				Hook::add('SubmissionFile::delete::before', [$this, 'onDeleteSubmissionFile']);
				Hook::add('Publication::edit', [$this, 'createEpubGalley']);
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
		require_once __DIR__ . '/JatsParserSettingsForm.php';
		require_once __DIR__ . '/JatsParserPdfSettingsForm.php';
		require_once __DIR__ . '/JatsParserPartsForm.php';

		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
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
				$form = new JatsParserPartsForm($this, $context->getId());

				$part = $request->getUserVar('partName');
				$template = $request->getUserVar('template');
				$fileManager = new PrivateFileManager();
				$path = $fileManager->getBasePath() . "/journals/" . $context->getId() . "/jatsParser_templates/$template/";

				$fileName = basename((string) $part);
				$targetPath = $path . $fileName;
				
				unlink($targetPath);

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
				$templateMgr->assign('jatsParserPlugin', $this);

				$form->display($request);

				return true;
			case 'resetAllParts':
				$context = $request->getContext();
				$form = new JatsParserPartsForm($this, $context->getId());

				$template = $request->getUserVar('template');
				$fileManager = new PrivateFileManager();
				$path = $fileManager->getBasePath() . "/journals/" . $context->getId() . "/jatsParser_templates/$template/";

				if (is_dir($path)) {
					$files = array_diff(scandir($path), array('.', '..'));
					foreach ($files as $file) {
						if (is_file("$path/$file")) {
							unlink("$path/$file");
						}
					}
				}

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);
				$templateMgr->assign('jatsParserPlugin', $this);

				$form->display($request);

				return true;
			case 'downloadCurrentTemplate':
				$context = $request->getContext();
				$form = new JatsParserPartsForm($this, $context->getId());
				$fileManager = new PrivateFileManager();

				$template = $request->getuserVar('template');

				$parts = PDFCreationService::getTemplatePartsAndLocation($template, $this, $fileManager, $context->getId());

				$zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jats_template_' . uniqid() . '.zip';
				$zip = new ZipArchive();
				$res = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
				if ($res !== true) {
					file_put_contents(__DIR__ . "/error.txt", "No se pudo crear el archivo ZIP: $res\n");
				}

				foreach ($parts as $part) {
					$using = $part['using'] . "/$template/";
					$filename = $part['filename'];

					$full = $using . $filename;
					$zip->addFile($full, $filename);
				}

				$zip->close();

				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="' . basename($template) . '-modified.zip"');
				header('Content-Length: ' . filesize($zipPath));
				readfile($zipPath);
				@unlink($zipPath);

				return true;
			case 'downloadOriginalTemplate':
				$context = $request->getContext();
				$form = new JatsParserPartsForm($this, $context->getId());
				$fileManager = new PrivateFileManager();

				$template = $request->getuserVar('template');

				$parts = PDFCreationService::getTemplatePartsAndLocation($template, $this, $fileManager, $context->getId());

				$zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jats_template_' . uniqid() . '.zip';
				$zip = new ZipArchive();
				$res = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
				if ($res !== true) {
					file_put_contents(__DIR__ . "/error.txt", "No se pudo crear el archivo ZIP: $res\n");
				}

				foreach ($parts as $part) {
					$using = $this->getPluginPath() . "/templates/SUMARC/$template/";
					$filename = $part['filename'];

					$full = $using . $filename;
					$zip->addFile($full, $filename);
				}

				$zip->close();

				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="' . basename($template) . '-original.zip"');
				header('Content-Length: ' . filesize($zipPath));
				readfile($zipPath);
				@unlink($zipPath);

				return true;
			case 'downloadPart':
				$context = $request->getContext();
				$form = new JatsParserPartsForm($this, $context->getId());

				$template = $request->getuserVar('template');
				$part = $request->getuserVar('partFile');
				$path = $request->getuserVar('partPath');

				$file = "$path/$template/$part";

				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="' . basename($part));
				header('Content-Length: ' . filesize($file));
				readfile($file);

				return true;

		}
		return parent::manage($args, $request);
	}

	//Get an array of OJS metadata to be used in the PDF generation
	private function getMetadata($publication, $localeKey, $request, $htmlString)
	{

		//$submission = Services::get('submission')->get($publication->getData('submissionId')); /* @var $submission Submission */
		$submission = Repo::submission()->get($publication->getData('submissionId'));
		$context = $request->getContext() ?: Application::getContextDAO()->getById($submission->getData('contextId'));
		$journal = $context;

		$issueIdentification = "";
		if ($publication->getData('issueId')) {
			$issue = Repo::issue()->get($publication->getData('issueId'));
			$issueIdentification = $issue->getIssueIdentification();
			$issueVolume = $issue->getData('volume');
			$issueNumber = $issue->getData('number');
			$issueYear = $issue->getData('year');
		}

		$section = Repo::section()->get($publication->getData('sectionId'));
		$userGroups = \PKP\userGroup\UserGroup::withContextIds([$journal->getId()])
			->get()
			->all();


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

		//Obtener la fecha de aceptación del envío si se saltea la etapa de revisión (ej: QuickSubmit)
		if (!$acceptedDate) {
			$acceptedDate = $submission->getData('dateSubmitted');
		}

		$licenseUrl = !empty($publication->getData('licenseUrl')) ? $publication->getData('licenseUrl') : $journal->getData('licenseUrl');
		if ($licenseUrl && !str_ends_with($licenseUrl, "/")) {
			$licenseUrl .= "/";
		}

		$date = $publication->getData('datePublished') ?: $submission->getData('dateSubmitted');
		$datePublished = $date ? date('d/m/Y', strtotime($date)) : '';

		$authors = array_values(iterator_to_array($publication->getData('authors')));
		$simplifiedAuthors = array_map(function ($author) {
			$data = $author->_data;
			if (!isset($data['orcid'])) {
				$data['orcid'] = '';
			}
			return $data; // Extrae solo el contenido de '_data', así es más sencillo el acceso desde todos lados
		}, $authors);

		$rawKeywords = $publication->getData('keywords') ?: [];
		$keywordsTexts = [];
		foreach ($rawKeywords as $locale => $kwList) {
			$keywordsTexts[$locale] = array_map(function ($kw) {
				if (is_array($kw)) {
					return $kw['name'] ?? '';
				}
				if (is_object($kw)) {
					return $kw->name ?? '';
				}
				return (string) $kw;
			}, (array) $kwList);
		}

		$metadata = [
			'publication_pages' => $publication->getData('pages'),
			'section_title' => $section?->getLocalizedTitle(),
			'citation_style' => $this->getCitationStyle($context),
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
			'date_submitted' => $submission->getData('dateSubmitted') ? date('d/m/Y', strtotime($submission->getData('dateSubmitted'))) : '',
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
			'keywords_texts' => $keywordsTexts,
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
		$context = $request ? $request->getContext() : null;
		$contextId = $context ? $context->getId() : 1;

		$ojsConfiguration = [
			'margin_top' => $this->getSetting($contextId, 'pdfTopMargin'),
			'margin_bottom' => $this->getSetting($contextId, 'pdfBottomMargin'),
			'margin_left' => $this->getSetting($contextId, 'pdfLeftMargin'),
			'margin_right' => $this->getSetting($contextId, 'pdfRightMargin'),
			'selected_template' => $this->getSetting($contextId, 'selectedTemplate'),
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
		$context = $request ? $request->getContext() : null;
		$journalId = $context ? $context->getId() : 1;

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
		$journalId = $request->getContext() ? $request->getContext()->getId() : 1;

		$outputStrategy = PDFOutputStrategy::class; # Lo que hablamos fue que esto quede así hasta que se necesite hace un selector de estrategias, trabajo para otra persona
		# Pero, esencialmente, sería un selector que te devuelve el FQCN de la estrategia a usar, en este caso PdfOutputStrategy::class retorna algo del estilo JATSParser\TemplateHandler\PDF\PdfOutputStrategy
		# Nótese que la estrategia a usar debe guardarse en la DB ya que es una configuración que se mantiene, no se selecciona a la hora de escupir el PDF sino desde la config del plugin en OJS. Atte: Leito

		# file_put_contents(__DIR__ . "/htmlTest.html", $htmloutput::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration));
		return $outputStrategy::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration);
	}

	/**
	 * @brief Generates and persists the enriched HTML for the publication
	 */
	private function htmlCreation(string $htmlString, Publication $publication, Request $request, string $localeKey, int $fileId): string
	{
		$metadata = $this->getMetadata($publication, $localeKey, $request, $htmlString);
		$ojsConfiguration = $this->getConfiguration($request);
		$configuration = new Configuration($metadata);
		$fileMgr = new PrivateFileManager();
		$journalId = $request->getContext() ? $request->getContext()->getId() : 1;

		# $htmloutput = HTMLOutputStrategy::class; # Sorpresa sorpresa, adapté la estrategia de salida de los PDFs para generar una salida en HTML, es probable que haya que meter algo de mano para que termine de ser funcional, pero el desarrollo está prácticamente hecho. Todo el procesamiento interno ya estaría acomdoado 👍 

		$html = HTMLOutputStrategy::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration);

		// Me quedo solo con lo que esté dentro del tag <html> del HTML, descartando lo demás
		// Buscamos el inicio desde el principio
		$startPos = stripos($html, '<html');

		if ($startPos !== false) {
			$openTagEnd = strpos($html, '>', $startPos);

			// Buscamos el cierre DESDE EL FINAL hacia atrás (Muy eficiente si está al final)
			$endPos = strripos($html, '</html>');

			if ($openTagEnd !== false && $endPos !== false && $endPos > $openTagEnd) {
				$html = substr($html, $openTagEnd + 1, $endPos - ($openTagEnd + 1));
			}
		}

		$publication->setData('jatsParser::fullText', $html, $localeKey);
		return $html;
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
		$baseProp = ['multilingual' => true, 'apiSummary' => true, 'validation' => ['nullable']];

		$schema->properties->{'jatsParser::fullTextFileId'} = (object) array_merge($baseProp, ['type' => 'integer']);
		$schema->properties->{'jatsParser::fullText'} = (object) array_merge($baseProp, ['type' => 'string']);
		$schema->properties->{'jatsParser::citationTableData'} = (object) array_merge($baseProp, ['type' => 'string']);
		$schema->properties->{'jatsParser::generateHtml'} = (object) array_merge($baseProp, [
			'type' => 'array',
			'items' => (object) ['type' => 'boolean']
		]);
		$schema->properties->{'jatsParser::deleteHtml'} = (object) array_merge($baseProp, ['type' => 'boolean']);
		$schema->properties->{'jatsParser::pdfGalley'} = (object) array_merge($baseProp, [
			'type' => 'array',
			'items' => (object) ['type' => 'boolean']
		]);
		$schema->properties->{'jatsParser::epubGalley'} = (object) array_merge($baseProp, [
			'type' => 'array',
			'items' => (object) ['type' => 'boolean']
		]);
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
		$localeNames = \PKP\facades\Locale::getLocales();

		$locales = array_map(function ($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => isset($localeNames[$localeKey]) ? $localeNames[$localeKey]->getDisplayName() : $localeKey];
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
		$pluginBaseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
		$templateMgr->assign('jatsParserPluginUrl', $pluginBaseUrl);
		$templateMgr->assign('jatsPublicationApiUrl', $latestPublicationApiUrl);

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
			define('HANDLER_CLASS', 'APP\plugins\generic\jatsParser\FullTextArticleHandler');
			define('JATSPARSER_PLUGIN_NAME', $this->getName());
			require_once($this->getPluginPath() . '/FullTextArticleHandler.php');
			return true;
		}

		if ($page === 'jatsParserWorkflow') {
			$handler = &$args[3];
			require_once($this->getPluginPath() . '/JatsParserWorkflowHandler.php');
			$handler = new JatsParserWorkflowHandler($this);
			return true;
		}

		return false;
	}

	/**
	 * Inject custom assets for OJS 3.5 workflow
	 */
	public function workflowCustomAssets(string $hookname, array $args): void
	{
		$templateMgr = $args[0];
		$template = $args[1];

		if (!in_array($template, ['dashboard/editors.tpl', 'workflow/workflow.tpl'])) return;

		$request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context) return;

		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();

		$templateMgr->addStyleSheet(
			'jatsParserCitationCss',
			$baseUrl . '/app/citationTable.css',
			['contexts' => ['backend']]
		);

		$templateMgr->addJavaScript(
			'jatsParserCitationJs',
			$baseUrl . '/app/citationTable.js',
			[
				'contexts' => ['backend'],
				'priority' => \PKP\template\PKPTemplateManager::STYLE_SEQUENCE_LAST,
			]
		);

		$templateMgr->addJavaScript(
			'jatsParserWorkflowJs',
			$baseUrl . '/js/jatsParserWorkflow.js',
			[
				'contexts' => ['backend'],
				'priority' => \PKP\template\PKPTemplateManager::STYLE_SEQUENCE_LAST,
			]
		);

		$formConfigUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'jatsParserWorkflow', 'formConfig');

		$templateMgr->setConstants([
			'jatsParserConfig' => [
				'formConfigUrl' => $formConfigUrl,
				'tabLabel' => __('plugins.generic.jatsParser.publication.jats.fulltext'),
				'deleteConfirmTitle' => __('plugins.generic.jatsParser.publication.jats.html.deleteConfirmTitle'),
				'deleteConfirmText' => __('plugins.generic.jatsParser.publication.jats.html.deleteConfirmText'),
				'deleteBtn' => __('plugins.generic.jatsParser.publication.jats.html.deleteBtn'),
				'cancel' => __('common.cancel'),
				'notice' => __('common.notice'),
				'error' => __('common.error'),
				'deletedSuccess' => __('plugins.generic.jatsParser.publication.jats.html.deletedSuccess'),
			]
		]);

		$configPayload = json_encode([
			'formConfigUrl' => $formConfigUrl,
			'tabLabel' => __('plugins.generic.jatsParser.publication.jats.fulltext'),
			'deleteConfirmTitle' => __('plugins.generic.jatsParser.publication.jats.html.deleteConfirmTitle'),
			'deleteConfirmText' => __('plugins.generic.jatsParser.publication.jats.html.deleteConfirmText'),
			'deleteBtn' => __('plugins.generic.jatsParser.publication.jats.html.deleteBtn'),
			'cancel' => __('common.cancel'),
			'notice' => __('common.notice'),
			'error' => __('common.error'),
			'deletedSuccess' => __('plugins.generic.jatsParser.publication.jats.html.deletedSuccess'),
		]);

		$templateMgr->addJavaScript(
			'jatsParserConfigJs',
			'window.jatsParserConfig = ' . $configPayload . '; window.pkp = window.pkp || {}; pkp.const = pkp.const || {}; pkp.const.jatsParserConfig = window.jatsParserConfig;',
			[
				'contexts' => ['backend'],
				'inline' => true,
				'priority' => \PKP\template\PKPTemplateManager::STYLE_SEQUENCE_CORE,
			]
		);
	}

	/**
	 * Helper to determine if a form checkbox or array of checkboxes was checked
	 *
	 * @param mixed $value
	 * @return bool
	 */
	private function isCheckboxChecked($value): bool
	{
		if (is_array($value)) {
			return in_array(true, $value, true) || in_array(1, $value) || in_array('true', $value);
		}
		return !empty($value);
	}

	/**
	 * Parse JATS XML and enrich HTML with images, references, footnotes and navigation
	 *
	 * @param SubmissionFile $submissionFile
	 * @param Publication $publication
	 * @param string $localeKey
	 * @param bool $isBase64Images true for HTML web (base64 data URIs), false for local server paths (PDF)
	 * @return string
	 */
	private function buildEnrichedHtml($submissionFile, $publication, string $localeKey, bool $isBase64Images): string
	{
		$privateFileManager = new PrivateFileManager();
		$jatsFilePath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path');

		$htmlDocument = $this->getFullTextFromJats($submissionFile);
		$htmlString = $htmlDocument->saveAsHTML();

		$htmlString = $this->_setSupplImgPath($submissionFile, $htmlString, $isBase64Images);
		$htmlString = $this->_setReferences($publication, $localeKey, $htmlString, $jatsFilePath);
		$htmlString = $this->_setFootnotes($publication, $localeKey, $htmlString);
		return \JATSParser\TemplateHandler\HTML\HTMLProcessingService::injectFootnoteNavigation($htmlString);
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

		// 1. Handle HTML deletion request
		if (array_key_exists('jatsParser::deleteHtml', $params)) {
			if (is_array($params['jatsParser::deleteHtml'])) {
				foreach ($params['jatsParser::deleteHtml'] as $localeKey => $shouldDelete) {
					if (!empty($shouldDelete)) {
						\Illuminate\Support\Facades\DB::table('publication_settings')
							->where('publication_id', '=', (int)$newPublication->getId())
							->where('setting_name', '=', 'jatsParser::fullText')
							->where('locale', '=', $localeKey)
							->delete();
						$newPublication->setData('jatsParser::fullText', null, $localeKey);
					}
				}
			} elseif (!empty($params['jatsParser::deleteHtml'])) {
				\Illuminate\Support\Facades\DB::table('publication_settings')
					->where('publication_id', '=', (int)$newPublication->getId())
					->where('setting_name', '=', 'jatsParser::fullText')
					->delete();
				$newPublication->setData('jatsParser::fullText', null);
			}
			$newPublication->setData('jatsParser::deleteHtml', null);
		}

		// 2. Handle XML file selection
		if (array_key_exists('jatsParser::fullTextFileId', $params) && is_array($params['jatsParser::fullTextFileId'])) {
			foreach ($params['jatsParser::fullTextFileId'] as $localeKey => $fileId) {
				if (empty($fileId)) {
					$newPublication->setData('jatsParser::fullTextFileId', null, $localeKey);
					\Illuminate\Support\Facades\DB::table('publication_settings')
						->where('publication_id', '=', (int)$newPublication->getId())
						->where('setting_name', '=', 'jatsParser::fullText')
						->where('locale', '=', $localeKey)
						->delete();
					$newPublication->setData('jatsParser::fullText', null, $localeKey);
				} else {
					$newPublication->setData('jatsParser::fullTextFileId', $fileId, $localeKey);
				}
			}
		}

		// Handle Citations Table Data saving
		if (array_key_exists('jatsParser::citationTableData', $params)) {
			require_once __DIR__ . '/classes/daos/CustomPublicationSettingsDAO.php';
			$customPublicationSettingsDao = new \CustomPublicationSettingsDAO();
			$citationParam = $params['jatsParser::citationTableData'];

			if (is_array($citationParam)) {
				foreach ($citationParam as $localeKey => $citationJson) {
					if (!empty($citationJson)) {
						$jsonString = is_array($citationJson) ? json_encode($citationJson) : $citationJson;
						$customPublicationSettingsDao->updateSetting($newPublication->getId(), 'jatsParser::citationTableData', $jsonString, $localeKey);
						$newPublication->setData('jatsParser::citationTableData', $jsonString, $localeKey);
					}
				}
			} elseif (is_string($citationParam) && !empty($citationParam)) {
				$localeKey = $newPublication->getData('locale') ?: 'es';
				$customPublicationSettingsDao->updateSetting($newPublication->getId(), 'jatsParser::citationTableData', $citationParam, $localeKey);
				$newPublication->setData('jatsParser::citationTableData', $citationParam, $localeKey);
			}
		}

		// 3. Handle HTML generation (independent of whether fullTextFileId was modified in this request)
		if (array_key_exists('jatsParser::generateHtml', $params) && is_array($params['jatsParser::generateHtml'])) {
			foreach ($params['jatsParser::generateHtml'] as $localeKey => $genVal) {
				if (!$this->isCheckboxChecked($genVal)) continue;

				$fileId = null;
				if (isset($params['jatsParser::fullTextFileId'][$localeKey])) {
					$fileId = $params['jatsParser::fullTextFileId'][$localeKey];
				}
				if (empty($fileId)) {
					$fileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
				}

				if (empty($fileId)) continue;

				$submissionFile = Repo::submissionFile()->get($fileId);
				if ($submissionFile) {
					$htmlString = $this->buildEnrichedHtml($submissionFile, $newPublication, $localeKey, true);

					// Generar y persistir el HTML con la plantilla SUMARC
					$request = Application::get()->getRequest();
					$html = $this->htmlCreation($htmlString, $newPublication, $request, $localeKey, $fileId);

					$newPublication->setData('jatsParser::fullText', $html, $localeKey);
				}
			}
			$newPublication->setData('jatsParser::generateHtml', null);
		}

		return false;
	}

	/**
	 * Hook callback for SubmissionFile::delete::before
	 * Clean up fullTextFileId, fullText, and citationTableData if the source XML file is deleted
	 *
	 * @param string $hookName
	 * @param array $args [$submissionFile]
	 * @return bool
	 */
	public function onDeleteSubmissionFile(string $hookName, array $args): bool
	{
		$submissionFile = $args[0] ?? null;
		if (!$submissionFile) {
			return false;
		}

		$fileId = (int) $submissionFile->getId();
		if (!$fileId) {
			return false;
		}

		// Find all publications referencing this fileId as jatsParser::fullTextFileId
		$affectedSettings = \Illuminate\Support\Facades\DB::table('publication_settings')
			->where('setting_name', '=', 'jatsParser::fullTextFileId')
			->where('setting_value', '=', (string) $fileId)
			->get();

		foreach ($affectedSettings as $setting) {
			$publicationId = (int) $setting->publication_id;
			$locale = (string) $setting->locale;

			\Illuminate\Support\Facades\DB::table('publication_settings')
				->where('publication_id', '=', $publicationId)
				->where(function ($q) use ($locale) {
					$q->where('locale', '=', $locale);
					if (!empty($locale)) {
						$q->orWhere('locale', '=', '');
					}
				})
				->whereIn('setting_name', [
					'jatsParser::fullTextFileId',
					'jatsParser::fullText',
					'jatsParser::citationTableData'
				])
				->delete();
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
	function getCitationStyle(?Journal $context = null): string
	{
		if (!$context) {
			$context = Application::get()->getRequest()->getContext() ?: Application::getContextDAO()->getById(1);
		}
		$contextId = $context ? $context->getId() : 1;

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
		$dateFormat = $this->getSetting($context->getId(), 'cslDateFormat');
		if (empty($dateFormat)) {
			$dateFormat = 'Y-m-d';
		}
		$htmlDocument->setReferences($citationStyle, $lang, false, $dateFormat);

		$this->_importCitations($htmlDocument, $newPublication);

		return false;
	}

	function createEpubGalley(string $hookname, array $args)
	{
		$newPublication = $args[0]; /* @var $newPublication Publication */
		$params = $args[2];
		$request = $args[3];
		if (!array_key_exists('jatsParser::epubGalley', $params)) return false;
		$localePare = $params['jatsParser::epubGalley'];

		foreach ($localePare as $localeKey => $createEpub) {
		
			if (!$this->isCheckboxChecked($createEpub)) continue;
			
			// Obtener el ID del archivo XML para este idioma
			$jatsFileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
			if (empty($jatsFileId) && isset($params['jatsParser::fullTextFileId'][$localeKey])) {
				$jatsFileId = $params['jatsParser::fullTextFileId'][$localeKey];
			}
			if (empty($jatsFileId)) continue;
			
			$jatsSubmissionFile = Repo::submissionFile()->get($jatsFileId);
			if (!$jatsSubmissionFile) continue;
			
			// Parsear y enriquecer el HTML para compilar el EPUB
			$fullTextHtml = $this->buildEnrichedHtml($jatsSubmissionFile, $newPublication, $localeKey, false);
			
			// Generar EPUB (delegado a epubCreation)
			$epub = $this->epubCreation($fullTextHtml, $newPublication, $request, $localeKey, $jatsFileId);
			if (empty($epub)) continue;
			
			// Crear la galerada EPUB
			$epubGalleyId = $this->createGalley($localeKey, $newPublication, 'plugins.generic.jatsParser.publication.galley.epub.label');
			
			// Obtener el objeto Galley usando Repo::galley()->get()
			$epubGalley = Repo::galley()->get($epubGalleyId);

			if ($epubGalley) {
				// Crear y asociar el archivo físico del EPUB
				$epubSubmissionFile = $this->_setEpubSubmissionFile($epub, $newPublication, $epubGalley);
				if ($epubSubmissionFile) {
					Repo::galley()->edit($epubGalley, [
						'submissionFileId' => $epubSubmissionFile->getId(),
					]);
				} else {
					Repo::galley()->delete($epubGalley);
				}
			}
		}
		// Limpiar flag temporal
		$newPublication->setData('jatsParser::epubGalley', null);
		return false;
	}

	function epubCreation(string $htmlString, Publication $publication, Request $request, string $localeKey, int $fileId) {
		$metadata = $this->getMetadata($publication, $localeKey, $request, $htmlString);
		$ojsConfiguration = $this->getConfiguration($request);
		$configuration = new Configuration($metadata);
		$fileMgr = new PrivateFileManager();
		$journalId = $request->getContext() ? $request->getContext()->getId() : 1;

		$outputStrategy = EPUBOutputStrategy::class;
		return $outputStrategy::generateOutput($this, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration);
	}

	/**
	 * @param string $epubBinaryString binario del EPUB
	 * @param Publication $publication
	 * @param Galley $galley
	 * @return SubmissionFile|null
	 * @brief Crea un nuevo submission file para el EPUB en OJS
	 */
	private function _setEpubSubmissionFile(string $epubBinaryString, Publication $publication, Galley $galley)
	{
		$submission = Repo::submission()->get($publication->getData('submissionId'));
		$request = $this->getRequest();
		// Crear archivo temporal
		$tmpFile = tempnam(sys_get_temp_dir(), 'jatsParser_epub');
		file_put_contents($tmpFile, $epubBinaryString);
		// Obtener directorio de almacenamiento del submission
		$submissionFileRepo = Repo::submissionFile();
		$submissionDir = $submissionFileRepo->getSubmissionDir($submission->getData('contextId'), $submission->getId());
		// Guardar archivo en el sistema de archivos de OJS con extensión .epub
		$fileId = app()->get('file')->add(
			$tmpFile,
			$submissionDir . DIRECTORY_SEPARATOR . uniqid() . '.epub'
		);
		// Obtener el nombre base a partir del XML
		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $galley->getLocale());
		if (empty($jatsFileId)) {
			$allJatsFileIds = $publication->getData('jatsParser::fullTextFileId');
			if (is_array($allJatsFileIds)) {
				$jatsFileId = $allJatsFileIds[$galley->getLocale()] ?? reset($allJatsFileIds);
			} elseif (is_numeric($allJatsFileIds)) {
				$jatsFileId = (int) $allJatsFileIds;
			}
		}
		if (empty($jatsFileId)) {
			unlink($tmpFile);
			return null;
		}
		$jatsFile = $submissionFileRepo->get($jatsFileId);
		if (!$jatsFile) {
			unlink($tmpFile);
			return null;
		}
		$name = [];
		foreach ($jatsFile->getData('name') as $locale => $sourceName) {
			$name[$locale] = pathinfo($sourceName)['filename'] . '.epub';
		}
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genre = $genreDao->getByKey('SUBMISSION', $submission->getData('contextId'));
		// Crear el registro SubmissionFile en OJS 3.5
		$submissionFile = $submissionFileRepo->newDataObject();
		$submissionFile->setAllData([
			'fileId' => $fileId,
			'assocType' => Application::ASSOC_TYPE_GALLEY,
			'assocId' => $galley->getId(),
			'fileStage' => SubmissionFile::SUBMISSION_FILE_PROOF,
			'mimetype' => 'application/epub+zip',
			'locale' => $galley->getLocale(),
			'genreId' => $genre->getId(),
			'name' => $name,
			'submissionId' => $submission->getId(),
		]);
		$submissionFileId = Repo::submissionFile()->add($submissionFile);
		$submissionFile = Repo::submissionFile()->get($submissionFileId);
		unlink($tmpFile); // Limpiar archivo temporal
		return $submissionFile;
	}

	function createAllGalleys(string $hookname, array $args)
	{
		$newPublication = $args[0]; /* @var $newPublication Publication */
		$params = $args[2];
		$request = $args[3];

		if (!array_key_exists('jatsParser::pdfGalley', $params)) return false;
		$contextId = ($request && $request->getContext()) ? $request->getContext()->getId() : 1;
		if (!$this->getSetting($contextId, 'convertToPdf')) return false;

		$localePare = $params['jatsParser::pdfGalley'];
		foreach ($localePare as $localeKey => $createPdf) {
			if (!$this->isCheckboxChecked($createPdf)) continue;

			// Obtener el ID del archivo XML para este idioma
			$jatsFileId = $newPublication->getData('jatsParser::fullTextFileId', $localeKey);
			if (empty($jatsFileId) && isset($params['jatsParser::fullTextFileId'][$localeKey])) {
				$jatsFileId = $params['jatsParser::fullTextFileId'][$localeKey];
			}
			if (empty($jatsFileId)) continue;

			$jatsSubmissionFile = Repo::submissionFile()->get($jatsFileId);
			if (!$jatsSubmissionFile) continue;

			// Parsear y enriquecer el HTML directamente en memoria para compilar el PDF de forma desacoplada
			$fullTextPdf = $this->buildEnrichedHtml($jatsSubmissionFile, $newPublication, $localeKey, false);

			// Compilar PDF directamente (sin exigir ni tocar el HTML de la base de datos)
			$pdf = $this->pdfCreation($fullTextPdf, $newPublication, $request, $localeKey, $jatsFileId);

			// --- Crear o actualizar galerada para PDF ---
			$pdfGalleyId = $this->createGalley($localeKey, $newPublication, 'plugins.generic.jatsParser.publication.galley.pdf.label');

			// Obtener el galley PDF usando Repo::galley()->get()
			$pdfGalley = Repo::galley()->get($pdfGalleyId);

			if ($pdfGalley) {
				// Crear archivo de sumisión del PDF
				$pdfSubmissionFile = $this->_setPdfSubmissionFile($pdf, $newPublication, $pdfGalley);
				if ($pdfSubmissionFile) {
					Repo::galley()->edit($pdfGalley, [
						'submissionFileId' => $pdfSubmissionFile->getId(),
					]);
				} else {
					Repo::galley()->delete($pdfGalley);
				}
			}
		}

		$newPublication->setData('jatsParser::pdfGalley', null);

		return false;
	}

	/**
	 * @param string $galleyLocale
	 * @param Publication $publication
	 * @return int
	 * @brief create an empty galley
	 */
	function createGalley(string $galleyLocale, Publication $publication, $translationLabelKey): int
	{
		$articleGalley = Repo::galley()->newDataObject();
		$articleGalley->setLocale($galleyLocale);
		$articleGalley->setData('publicationId', $publication->getId());
		$articleGalley->setLabel(__($translationLabelKey));
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

		$submission = Repo::submission()->get($publication->getData('submissionId'));

		$request = $this->getRequest();

		// Create a temporary file
		$tmpFile = tempnam(sys_get_temp_dir(), 'jatsParser');
		file_put_contents($tmpFile, $pdfBinaryString);

		// Set main Submission File data
		$submissionFile = Repo::submissionFile();
		$submissionDir = $submissionFile->getSubmissionDir($submission->getData('contextId'), $submission->getId());

		$fileId = app()->get('file')->add(
			$tmpFile,
			$submissionDir . DIRECTORY_SEPARATOR . uniqid() . '.pdf'
		);

		$jatsFileId = $publication->getData('jatsParser::fullTextFileId', $galley->getLocale());
		if (empty($jatsFileId)) {
			$allJatsFileIds = $publication->getData('jatsParser::fullTextFileId');
			if (is_array($allJatsFileIds)) {
				$jatsFileId = $allJatsFileIds[$galley->getLocale()] ?? reset($allJatsFileIds);
			} elseif (is_numeric($allJatsFileIds)) {
				$jatsFileId = (int) $allJatsFileIds;
			}
		}
		if (empty($jatsFileId)) {
			unlink($tmpFile);
			return null;
		}
		$jatsFile = $submissionFile->get($jatsFileId);
		if (!$jatsFile) {
			unlink($tmpFile);
			return null;
		}

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
				'assocType' => Application::ASSOC_TYPE_GALLEY,
				'assocId' => $galley->getId(),
				'fileStage' => SubmissionFile::SUBMISSION_FILE_PROOF,
				'mimetype' => 'application/pdf',
				'locale' => $galley->getLocale(),
				'genreId' => $genre->getId(),
				'name' => $name,
				'submissionId' => $submission->getId(),
			]
		);
		$submissionFileId = Repo::submissionFile()->add($submissionFile);
		$submissionFile = Repo::submissionFile()->get($submissionFileId);
		unlink($tmpFile);
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
		if (!$context) {
			$submission = Repo::submission()->get($publication->getData('submissionId'));
			$context = Application::getContextDAO()->getById($submission ? $submission->getData('contextId') : 1);
		}
		$citationStyle = $this->getCitationStyle($context);

		//Obtain xml jats file
		// Create a JATSDocument instance
		$jatsDocument = new JATSDocument($jatsPath);

		// Get the references from the JATS document
		$references = $jatsDocument->getReferences();

		// Create an HTML document to handle formatting
		$htmlDoc = new \JATSParser\HTML\Document($jatsDocument);
		// Set the references with the desired citation style

		//$locale_key = $context->getPrimaryLocale();
		$formattedLocaleKey = str_replace('_', '-', $locale);
		// $citationStyle ya fue obtenido arriba con getCitationStyle(), no es necesario releerlo.
		$dateFormat = $this->getSetting($context->getId(), 'cslDateFormat');
		if (empty($dateFormat)) {
			$dateFormat = 'Y-m-d';
		}

		$htmlDoc->setReferences($citationStyle, $formattedLocaleKey, false, $dateFormat);

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
			
			// Flecha para retornar a la cita en el texto (usa enlace real para activar :target)
			$arrow = ' <a href="#citation_' . $id . '" class="return-arrow" title="' . __('common.return') . '">&#8593;</a>';
			
			// Apply semantic class to the list item
			$htmlString .= '<li class="citation-item" id="' . $id . '">' . $formattedCitation . $arrow . '</li>';
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
		$currentLocale = \PKP\facades\Locale::getLocale();
		$hasFullTextForLocale = false;
		if (array_key_exists($currentLocale, $fullTexts) && !empty($fullTexts[$currentLocale])) {
			$html = $fullTexts[$currentLocale];

			$submissionFileId = $publication->getData('jatsParser::fullTextFileId', $currentLocale);
			//$submissionFile = Services::get('submissionFile')->get($submissionFileId);
			$submissionFile = Repo::submissionFile()->get($submissionFileId);
			$hasFullTextForLocale = true;
		} else {
			$locales = \PKP\facades\Locale::getLocales();
			$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocale');
			if (count($fullTexts) > 1) {
				$msg = __('plugins.generic.jatsParser.article.fulltext.availableLocales');
			}

			$html = '<p class="jatsParser__available-locales">' . $msg;
			foreach ($fullTexts as $localeKey => $fullText) {
				$html .= ' <a href="' . $request->url(null, 'user', 'setLocale', $localeKey) . '">' . $locales[$localeKey]->getDisplayName() . '</a>';
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
			$html = $this->_setSupplImgPath($submissionFile, $html, true);
		}

		$templateMgr->assign('fullText', $html);
		$templateMgr->assign('hasFullTextForLocale', $hasFullTextForLocale);
		// Provide the plugin base URL so the template can load plugin assets
		$baseUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
		$templateMgr->assign('jatsParserPluginUrl', $baseUrl);
		$output .= $templateMgr->fetch($this->getTemplateResource('articleMainView.tpl'));
		return false;
	}

	/**
	 * @param SubmissionFile $submissionFile
	 * @param string $htmlString
	 * @return string
	 * @brief Substitute path to attached images for full-text HTML
	 */
	function _setSupplImgPath(SubmissionFile $submissionFile, string $htmlString, bool $useBase64 = false): string
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
			if ($genre->getCategory() !== Genre::GENRE_CATEGORY_ARTWORK) continue; // only art works are supported
			if (!in_array($dependentFile->getData('mimetype'), self::getSupportedSupplFileTypes())) continue; // check if MIME type is supported

			$submissionId = $submissionFile->getData('submissionId');

			$imgPath = $privateFileManager->getBasePath() . DIRECTORY_SEPARATOR . $dependentFile->getData('path');

			if ($useBase64) {
				if (file_exists($imgPath)) {
					$image = file_get_contents($imgPath);
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$mimeType = finfo_file($finfo, $imgPath);
					finfo_close($finfo);
					$imageBase64 = base64_encode($image);
					$filePath = 'data:' . $mimeType . ';base64,' . $imageBase64; 
				} else {
					$filePath = ''; // Provide fallback if file doesn't exist
				}
			} else {
				$filePath = $imgPath;
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

		$submissions = Repo::submission()
			->getCollector()
			->filterByContextIds([$context->getId()])
			->filterByStageIds([PKPApplication::WORKFLOW_STAGE_ID_PRODUCTION])
			->getMany();

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
				$submissionDir = Repo::submissionFile()->getSubmissionDir($request->getContext()->getId(), $submission->getId());
				$fileId = app()->get('file')->add(
					$fileManager->getBasePath() . DIRECTORY_SEPARATOR . $submissionFile->getData('path'),
					$submissionDir . '/' . uniqid() . '.xml'
				);

				$newSubmissionFile = $submissionFileDao->newDataObject();
				$newSubmissionFile->setAllData(
					[
						'fileId' => $fileId,
						'uploaderUserId' => $user->getId(),
						'fileStage' => SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY,
						'submissionId' => $submission->getId(),
						'genreId' => $submissionFile->getData('genreId'),
						'name' => $submissionFile->getData('name'),
					],
				);
				$newSubmissionFileId = Repo::submissionFile()->add($newSubmissionFile);
				$newSubmissionFile = Repo::submissionFile()->get($newSubmissionFileId);

				// copy and attach dependent files, only images are supported
				$assocFiles = Repo::submissionFile()->getCollector()
					->filterBySubmissionIds([$submission->getId()])
					->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
					->getMany()
					->filter(function($f) use ($submissionFile) {
						return $f->getData('assocType') === PKPApplication::ASSOC_TYPE_SUBMISSION_FILE &&
							$f->getData('assocId') == $submissionFile->getId();
					});

				foreach ($assocFiles as $assocFile) {
					/** @var $assocFile SubmissionFile */
					if (in_array($assocFile->getData('mimetype'), $this->getSupportedSupplFileTypes())) {
						$newAssocFileId = app()->get('file')->add(
							$fileManager->getBasePath() . DIRECTORY_SEPARATOR . $assocFile->getData('path'),
							$submissionDir . '/' . uniqid() . '.' . $fileManager->parseFileExtension($assocFile->getData('path'))
						);

						$assocSubmissionFile = $submissionFileDao->newDataObject();
						$assocSubmissionFile->setAllData([
							'fileId' => $newAssocFileId,
							'assocId' => $newSubmissionFile->getId(),
							'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION_FILE,
							'uploaderUserId' => $user->getId(),
							'fileStage' =>  SubmissionFile::SUBMISSION_FILE_DEPENDENT,
							'submissionId' => $submission->getId(),
							'genreId' => $assocFile->getData('genreId'),
							'name' => $assocFile->getData('name'),
							'caption' => $assocFile->getData('caption'),
							'copyrightOwner' => $assocFile->getData('copyrightOwner'),
							'credit' => $assocFile->getData('credit'),
							'terms' => $assocFile->getData('terms'),
						]);
						Repo::submissionFile()->add($assocSubmissionFile);
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
			$htmlString .= '<div class="footnote-item" id="' . htmlspecialchars($fnId) . '">';
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

if (!class_exists('JatsParserPlugin', false)) {
	class_alias(\APP\plugins\generic\jatsParser\JatsParserPlugin::class, 'JatsParserPlugin');
}

