<?php

require_once __DIR__ . "/TableHTML.php";
require_once __dir__ . '/../../daos/CustomPublicationSettingsDAO.inc.php';

import('lib.pkp.classes.file.PrivateFileManager');

use JATSParser\PDF\PDFConfig\Configuration;
use PKP\components\forms\TableHTML;
use PKP\components\forms\FieldHTML;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;
use JATSParser\Body\Document as JATSDocument;
use JATSParser\HTML\Document as HTMLDocument;

define("FORM_PUBLICATION_JATS_FULLTEXT", "jatsUpload");

class PublicationJATSUploadForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_PUBLICATION_JATS_FULLTEXT;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $publication \Publication publication to change settings for
	 * @param $submissionFiles array of SubmissionFile with xml type
	 * @param $msg string field description
	 */
	public function __construct($action, $locales, $publication, $submissionFiles, $msg) {
		/**
		 * @var $submissionFile SubmissionFile
		 */
		$this->action = $action;
		$this->successMessage = __('plugins.generic.jatsParser.publication.jats.fulltext.success');
		$this->locales = $locales;

		$options = [];
		$generateHtmlOptions = [];
		$pdfOptions = [];
		$submissionFilesById = []; // Array to store submission files by ID for easy lookup later
		
		foreach ($locales as $value) {
			$locale = $value['key'];
			$lang = [];
			if (empty($submissionFiles)) break;
			foreach ($submissionFiles as $submissionFile) {
				$subName = $submissionFile->getData('name', $locale);
				if (empty($subName)) {
					$subName = $submissionFile->getLocalizedData('name');
				}
				$lang[] = array(
					'value' => $submissionFile->getId(),
					'label' => $subName
				);

				// Store submission file by ID for later lookup
				$submissionFilesById[$submissionFile->getId()] = $submissionFile;
			}

			$lang[] = array(
				'value' => null,
				'label' => __('common.default')
			);

			$options[$locale] = $lang;

			$generateHtmlOptions[$locale][] = array(
				'value' => true,
				'label' => __('plugins.generic.jatsParser.publication.jats.html.checkboxLabel')
			);

			$pdfOptions[$locale][] = array(
				'value' => true,
				'label' => __('plugins.generic.jatsParser.publication.jats.pdf.checkboxLabel')
			);
		}

		// Update the values so the proper option is selected on the form initiation if full-text isn't selected for the specific locale
		$values = $publication->getData('jatsParser::fullTextFileId');
		$emptyValues = array_fill_keys(array_keys($options), null);
		empty($values) ? $values = $emptyValues : $values = array_merge($emptyValues, $values);

		$plugin = PluginRegistry::getPlugin('generic', 'jatsparserplugin'); /* @var $plugin JATSParserPlugin */
		$context = Application::get()->getRequest()->getContext();
		$convertToPdf = $plugin->getSetting($context->getId(), 'convertToPdf');
		$citationStyle = $plugin->getSetting($context->getId(), 'citationStyle');

		// 1. Group: Source XML
		$this->addGroup([
			'id' => 'sourceXml',
			'label' => __('plugins.generic.jatsParser.publication.jats.group.sourceXml'),
			'description' => __('plugins.generic.jatsParser.publication.jats.group.sourceXml.description'),
		]);

		// 2. Group: HTML Output
		$this->addGroup([
			'id' => 'htmlOutput',
			'label' => __('plugins.generic.jatsParser.publication.jats.group.htmlOutput'),
			'description' => __('plugins.generic.jatsParser.publication.jats.group.htmlOutput.description'),
		]);

		// 3. Group: PDF Galley (if enabled in settings)
		if ($convertToPdf) {
			$this->addGroup([
				'id' => 'pdfOutput',
				'label' => __('plugins.generic.jatsParser.publication.jats.group.pdfOutput'),
				'description' => __('plugins.generic.jatsParser.publication.jats.group.pdfOutput.description'),
			]);
		}

		if (!empty($options)) {
			// SECTION 1: Source XML selection
			$this->addField(new FieldOptions('jatsParser::fullTextFileId', [
				'label' => __('plugins.generic.jatsParser.publication.jats.label'),
				'description' => $msg,
				'isMultilingual' => true,
				'groupId' => 'sourceXml',
				'type' => 'radio',
				'options' => $options,
				'value' => $values,
			]));
		
			$supportedCitationStyles = Configuration::getSupportedCustomCitationStyles();

			// Checking if citation style is supported
			if ($supportedCitationStyles && in_array(strtolower($citationStyle), $supportedCitationStyles)) {
				$fileMgr = new PrivateFileManager();
				
				// Get the current selected file ID for the primary locale
				$locale_key = $context->getPrimaryLocale();
				$selectedFileId = isset($values[$locale_key]) ? $values[$locale_key] : null;
				
				// Get the correct submission file and its path based on the selected file ID
				$relativeFilePath = null;
				if ($selectedFileId && isset($submissionFilesById[$selectedFileId])) {
					$selectedFile = $submissionFilesById[$selectedFileId];
					$relativeFilePath = $selectedFile->getData('path');
				} else if (!empty($submissionFiles)) {
					// Fallback to the first file if no selection
					$firstFile = reset($submissionFiles);
					$relativeFilePath = $firstFile->getData('path');
				}
				
				if ($relativeFilePath) {
					$absolutePath = $fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $relativeFilePath;
					
					$customPublicationSettingsDao = new CustomPublicationSettingsDAO();
					$customCitationData = $customPublicationSettingsDao->getSetting($publication->getId(), 'jatsParser::citationTableData', $locale_key);

					$tableHTML = new TableHTML($citationStyle, $absolutePath, $customCitationData, $publication, $locale_key);
					$html = $tableHTML->getHtml();

					$this->addField(new FieldHTML("citationTable", array(
						'label' => __('plugins.generic.jatsParser.publication.jats.citationStyle.label'),
						'description' => $html,
						'groupId' => 'sourceXml',
					)));
				}
			}

			// SECTION 2: HTML Output
			$existingFullText = $publication->getData('jatsParser::fullText');
			$generateHtmlOptions = [];

			foreach ($locales as $value) {
				$localeKey = $value['key'];
				$localeName = $value['label'];

				$htmlContent = null;
				if (is_array($existingFullText)) {
					$htmlContent = $existingFullText[$localeKey] ?? null;
				} else if (is_string($existingFullText) && $localeKey === $context->getPrimaryLocale()) {
					$htmlContent = $existingFullText;
				}

				$labelHtml = '<span style="font-weight: 600; display: inline-block; vertical-align: middle;">' . __('plugins.generic.jatsParser.publication.jats.html.checkboxLabel') . '</span>';

				if (!empty($htmlContent)) {
					$labelHtml .= '
					<div class="pkp_notification" style="margin-top: 12px; margin-bottom: 8px; font-weight: normal; text-transform: none; display: block;">
						<div class="notifyWarning">
							<span class="title">' . __('plugins.generic.jatsParser.publication.jats.html.activeTitle') . ' (' . htmlspecialchars($localeName) . ')</span>
							<span class="description">' . __('plugins.generic.jatsParser.publication.jats.html.alertOverwrite') . '</span>
						</div>
					</div>
					<div style="margin-top: 6px; margin-bottom: 4px; display: block;">
						<button type="button" class="pkpButton pkpButton--isWarnable jatsDeleteHtmlBtn" data-locale="' . htmlspecialchars($localeKey) . '" data-localename="' . htmlspecialchars($localeName) . '">
							' . __('plugins.generic.jatsParser.publication.jats.html.deleteBtn') . ' (' . htmlspecialchars($localeName) . ')
						</button>
					</div>';
				}

				$generateHtmlOptions[$localeKey] = [
					[
						'value' => true,
						'label' => $labelHtml
					]
				];
			}

			$generateHtmlValues = array_fill_keys(array_keys($options), []);
			$this->addField(new FieldOptions('jatsParser::generateHtml', [
				'label' => __('plugins.generic.jatsParser.publication.jats.html.label'),
				'description' => __('plugins.generic.jatsParser.publication.jats.html.description'),
				'type' => 'checkbox',
				'isMultilingual' => true,
				'groupId' => 'htmlOutput',
				'options' => $generateHtmlOptions,
				'value' => $generateHtmlValues,
			]));

			// SECTION 3: PDF Galley
			if ($convertToPdf) {
				$pdfGalleyValues = array_fill_keys(array_keys($options), []);
				$this->addField(new FieldOptions('jatsParser::pdfGalley', [
					'label' => __('plugins.generic.jatsParser.publication.jats.pdf.label'),
					'description' => __('plugins.generic.jatsParser.publication.jats.pdf.description'),
					'type' => 'checkbox',
					'isMultilingual' => true,
					'groupId' => 'pdfOutput',
					'options' => $pdfOptions,
					'value' => $pdfGalleyValues,
				]));
			}
		} else {
			$this->addField(new FieldHTML("addProductionReadyFiles", array(
				'description' => $msg,
				'groupId' => 'sourceXml',
			)));
		}
	}
}
