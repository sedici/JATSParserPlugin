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

		// 2. Group: Citations Assignment
		$this->addGroup([
			'id' => 'citations',
			'label' => __('plugins.generic.jatsParser.publication.jats.group.citations'),
			'description' => __('plugins.generic.jatsParser.publication.jats.group.citations.description'),
		]);

		// 3. Group: HTML Output
		$this->addGroup([
			'id' => 'htmlOutput',
			'label' => __('plugins.generic.jatsParser.publication.jats.group.htmlOutput'),
			'description' => __('plugins.generic.jatsParser.publication.jats.group.htmlOutput.description'),
		]);

		// 4. Group: PDF Galley (if enabled in settings)
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
		
			// SECTION 2: Citations Table
			$locale_key = $context->getPrimaryLocale();
			$selectedFileId = isset($values[$locale_key]) ? $values[$locale_key] : null;
			if (empty($selectedFileId)) {
				// Check if any other locale has a selected XML file
				foreach ($values as $loc => $fId) {
					if (!empty($fId)) {
						$selectedFileId = $fId;
						$locale_key = $loc;
						break;
					}
				}
			}

			if (empty($selectedFileId)) {
				// No XML selected/saved yet
				$noXmlNotice = '
				<div class="pkp_notification" style="margin: 0 0 10px 0; font-weight: normal; text-transform: none;">
					<div class="notifyInfo">
						<span class="title">' . __('common.notice') . '</span>
						<span class="description">' . __('plugins.generic.jatsParser.publication.jats.citations.noXmlSelected') . '</span>
					</div>
				</div>';

				$this->addField(new FieldHTML("citationTableEmptyNotice", [
					'description' => $noXmlNotice,
					'groupId' => 'citations',
				]));
			} else {
				$supportedCitationStyles = Configuration::getSupportedCustomCitationStyles();
				if ($supportedCitationStyles && in_array(strtolower($citationStyle), $supportedCitationStyles)) {
					$fileMgr = new PrivateFileManager();
					$selectedFile = isset($submissionFilesById[$selectedFileId]) ? $submissionFilesById[$selectedFileId] : null;
					$relativeFilePath = $selectedFile ? $selectedFile->getData('path') : null;

					if ($relativeFilePath) {
						$selectedFileName = $selectedFile ? ($selectedFile->getData('name', $locale_key) ?: $selectedFile->getLocalizedData('name')) : null;
						if (empty($selectedFileName) && $selectedFile) {
							$selectedFileName = $selectedFile->getData('originalFileName');
						}
						if (empty($selectedFileName)) {
							$selectedFileName = __('common.none');
						}

						$absolutePath = $fileMgr->getBasePath() . DIRECTORY_SEPARATOR . $relativeFilePath;
						$customPublicationSettingsDao = new CustomPublicationSettingsDAO();
						$customCitationData = $customPublicationSettingsDao->getSetting($publication->getId(), 'jatsParser::citationTableData', $locale_key);

						$tableHTML = new TableHTML($citationStyle, $absolutePath, $customCitationData, $publication, $locale_key);
						$tableContent = $tableHTML->getHtml();

						$hasCustomCitations = !empty($customCitationData['fileId']);
						$statusBadge = $hasCustomCitations
							? '<span style="display: inline-block; background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; margin-left: 8px;">✓ ' . __('plugins.generic.jatsParser.publication.jats.citations.statusConfigured') . '</span>'
							: '';

						$cardHtml = '
						<div style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 4px; padding: 16px 20px; margin-bottom: 10px;">
							<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
								<div>
									<span style="font-weight: 700; color: #333; font-size: 0.95rem;">' . __('plugins.generic.jatsParser.publication.jats.citations.cardTitle') . '</span>
									<span style="display: inline-block; background-color: #eef5fa; color: #006798; border: 1px solid #cce2f0; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; margin-left: 8px;">'
										. __('plugins.generic.jatsParser.publication.jats.citations.styleLabel') . ': ' . strtoupper(htmlspecialchars($citationStyle)) .
									'</span>'
									. $statusBadge . '
								</div>
							</div>
							<p style="margin: 0 0 14px 0; color: #555; font-size: 0.88rem; line-height: 1.45;">'
								. __('plugins.generic.jatsParser.publication.jats.citations.cardDescription') .
							'</p>
							<div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">'
								. $tableContent . '
								<div class="citation-selected-xml-badge" style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #d0d5dd; border-radius: 4px; padding: 6px 14px; font-size: 0.85rem; color: #344054; box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);" title="' . htmlspecialchars($selectedFileName) . '">
									<span class="fa fa-file-code-o" style="color: #006798; font-size: 1.05rem;" aria-hidden="true"></span>
									<span style="color: #475467; font-weight: 500;">' . __('plugins.generic.jatsParser.publication.jats.citations.selectedXmlLabel') . ':</span>
									<strong class="citation-selected-xml-name" style="color: #101828; font-weight: 600; max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($selectedFileName) . '</strong>
								</div>
							</div>
						</div>';

						$this->addField(new FieldHTML("citationTable", [
							'description' => $cardHtml,
							'groupId' => 'citations',
						]));
					}
				} else {
					$notSupportedNotice = '
					<div class="pkp_notification" style="margin: 0 0 10px 0; font-weight: normal; text-transform: none;">
						<div class="notifyWarning">
							<span class="title">' . __('common.notice') . '</span>
							<span class="description">' . __('plugins.generic.jatsParser.publication.jats.citations.styleNotSupported', ['style' => htmlspecialchars($citationStyle)]) . '</span>
						</div>
					</div>';

					$this->addField(new FieldHTML("citationTableNotSupported", [
						'description' => $notSupportedNotice,
						'groupId' => 'citations',
					]));
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
