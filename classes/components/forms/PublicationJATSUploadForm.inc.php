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

		if (!empty($options)) {
			// SECTION 1: Source XML selection
			$this->addField(new FieldOptions('jatsParser::fullTextFileId', [
				'label' => __('plugins.generic.jatsParser.publication.jats.group.sourceXml'),
				'description' => $msg,
				'isMultilingual' => true,
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

			$stage2Title = __('plugins.generic.jatsParser.publication.jats.group.citations');
			$stage2Description = __('plugins.generic.jatsParser.publication.jats.citations.cardDescription');

			if (empty($selectedFileId)) {
				// No XML selected/saved yet
				$cardHtml = '
				<div class="jats-citation-stage-card">
					<div class="jats-citation-stage-title">
						' . $stage2Title . '
					</div>
					<div class="jats-citation-stage-description">
						' . $stage2Description . '
					</div>
					<div class="pkp_notification" style="margin: 0; font-weight: normal; text-transform: none;">
						<div class="notifyInfo">
							<span class="title">' . __('common.notice') . '</span>
							<span class="description">' . __('plugins.generic.jatsParser.publication.jats.citations.noXmlSelected') . '</span>
						</div>
					</div>
				</div>';

				$this->addField(new FieldHTML("citationTableEmptyNotice", [
					'description' => $cardHtml,
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

						$tableHTML = new TableHTML($citationStyle, $absolutePath, $customCitationData, $publication, $locale_key, $selectedFileName, $selectedFileId);
						$tableContent = $tableHTML->getHtml();

						$hasCitationsInXml = $tableHTML->hasCitations();
						$xmlBadgeHeader = '<span class="citation-selected-xml-badge" data-current-xml="' . htmlspecialchars($selectedFileName) . '" style="display: inline-flex; align-items: center; gap: 6px; background-color: #ffffff; color: #344054; border: 1px solid #d0d5dd; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 500;" title="' . htmlspecialchars($selectedFileName) . '">
							<span class="fa fa-file-code-o" style="color: #006798; font-size: 0.85rem;" aria-hidden="true"></span>
							<strong class="citation-selected-xml-name" style="color: #101828; font-weight: 600; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($selectedFileName) . '</strong>
						</span>';

						if (!$hasCitationsInXml) {
							// El XML no contiene citas ni referencias para mapear
							$innerBody = '
							<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
								<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
									' . $xmlBadgeHeader . '
									<span style="display: inline-block; background-color: #eef5fa; color: #006798; border: 1px solid #cce2f0; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">'
										. __('plugins.generic.jatsParser.publication.jats.citations.styleLabel') . ': ' . strtoupper(htmlspecialchars($citationStyle)) .
									'</span>
								</div>
							</div>
							<div class="pkp_notification" style="margin: 8px 0 0 0; font-weight: normal; text-transform: none;">
								<div class="notifyInfo">
									<span class="title">' . __('common.notice') . '</span>
									<span class="description">' . __('plugins.generic.jatsParser.publication.jats.citations.noCitationsInXml', ['file' => htmlspecialchars($selectedFileName)]) . '</span>
								</div>
							</div>';
						} else {
							$innerBody = '
							<div class="jats-citation-stage-row">
								' . $tableContent . '
								<div class="jats-citation-stage-badges">
									' . $xmlBadgeHeader . '
									<span class="jats-citation-style-badge">'
										. __('plugins.generic.jatsParser.publication.jats.citations.styleLabel') . ': ' . strtoupper(htmlspecialchars($citationStyle)) .
									'</span>
								</div>
							</div>
							<div id="citationUnsavedXmlAlert" style="display: none; margin-top: 10px; font-size: 0.85rem; color: #9c4221; background: #fffaf0; border: 1px solid #fbd38d; border-radius: 4px; padding: 8px 12px; align-items: center; gap: 8px;">
								<span class="fa fa-info-circle" style="font-size: 1rem;"></span>
								<span id="citationUnsavedXmlAlertText"></span>
							</div>';
						}

						$cardHtml = '
						<div class="jats-citation-stage-card">
							<div class="jats-citation-stage-title">
								' . $stage2Title . '
							</div>
							<div class="jats-citation-stage-description">
								' . $stage2Description . '
							</div>
							' . $innerBody . '
						</div>';

						$this->addField(new FieldHTML("citationTable", [
							'description' => $cardHtml,
						]));
					}
				} else {
					$notSupportedNotice = '
					<div class="jats-citation-stage-card">
						<div class="jats-citation-stage-title">
							' . $stage2Title . '
						</div>
						<div class="pkp_notification" style="margin: 0; font-weight: normal; text-transform: none;">
							<div class="notifyWarning">
								<span class="title">' . __('common.notice') . '</span>
								<span class="description">' . __('plugins.generic.jatsParser.publication.jats.citations.styleNotSupported', ['style' => htmlspecialchars($citationStyle)]) . '</span>
							</div>
						</div>
					</div>';

					$this->addField(new FieldHTML("citationTableNotSupported", [
						'description' => $notSupportedNotice,
					]));
				}
			}

			// SECTION 3: HTML Output
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

				$labelHtml = '<span style="display: inline-block; vertical-align: middle;">' . __('plugins.generic.jatsParser.publication.jats.html.checkboxLabel') . '</span>';

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
				'label' => __('plugins.generic.jatsParser.publication.jats.group.htmlOutput'),
				'description' => __('plugins.generic.jatsParser.publication.jats.html.description'),
				'type' => 'checkbox',
				'isMultilingual' => true,
				'options' => $generateHtmlOptions,
				'value' => $generateHtmlValues,
			]));

			// SECTION 4: PDF Galley
			if ($convertToPdf) {
				$pdfGalleyValues = array_fill_keys(array_keys($options), []);
				$this->addField(new FieldOptions('jatsParser::pdfGalley', [
					'label' => __('plugins.generic.jatsParser.publication.jats.group.pdfOutput'),
					'description' => __('plugins.generic.jatsParser.publication.jats.pdf.description'),
					'type' => 'checkbox',
					'isMultilingual' => true,
					'options' => $pdfOptions,
					'value' => $pdfGalleyValues,
				]));
			}
		} else {
			$this->addField(new FieldHTML("addProductionReadyFiles", array(
				'description' => $msg,
			)));
		}
	}
}
