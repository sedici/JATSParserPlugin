<?php
import('lib.pkp.classes.form.Form');

use APP\core\Application;
use PKP\core\JSONMessage;
use PKP\file\PrivateFileManager;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;

class JatsParserPdfSettingsForm extends Form
{

	var $_journalId;
	var $_plugin;

	function __construct($plugin, $journalId)
	{
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('pdfSettingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	function initData()
	{
		$contextId = $this->_journalId;
		$plugin = $this->_plugin;

		$this->setData('pdfTopMargin', $plugin->getSetting($contextId, 'pdfTopMargin'));
		$this->setData('pdfBottomMargin', $plugin->getSetting($contextId, 'pdfBottomMargin'));
		$this->setData('pdfLeftMargin', $plugin->getSetting($contextId, 'pdfLeftMargin'));
		$this->setData('pdfRightMargin', $plugin->getSetting($contextId, 'pdfRightMargin'));
		$this->setData('selectedTemplate', $plugin->getSetting($contextId, 'selectedTemplate'));
	}

	function readInputData()
	{
		$this->readUserVars(array('fileInput', 'pdfTopMargin', 'pdfBottomMargin', 'pdfLeftMargin', 'pdfRightMargin', 'selectedTemplate'));
	}

	function fetch($request, $template = null, $display = false)
	{
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'pluginName' => $this->_plugin->getName(),
			'templates' => $this->_plugin->getAvailablePdfTemplates($request),
		]);

		return parent::fetch($request, $template, $display);
	}

	function execute(...$functionArgs)
	{
		if (!$this->validate()) {
			return false;
		}

		$plugin = $this->_plugin;
		$contextId = $this->_journalId;

		# Configuración de márgenes
		$plugin->updateSettings($contextId, 'pdfTopMargin', $this->getData('pdfTopMargin'));
		$plugin->updateSettings($contextId, 'pdfRightMargin', $this->getData('pdfRightMargin'));
		$plugin->updateSettings($contextId, 'pdfLeftMargin', $this->getData('pdfLeftMargin'));
		$plugin->updateSettings($contextId, 'pdfBottomMargin', $this->getData('pdfBottomMargin'));
		$plugin->updateSettings($contextId, 'selectedTemplate', $this->getData('selectedTemplate'));

		# Subir archivos para las templates
		$inputName = 'fileInput';
		$status = "";

		if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {

			$selectedTemplate = "UNLP";
			$fileManager = new PrivateFileManager();
			$templateDir = $fileManager->getBasePath() . "/journals/$contextId/jatsParser_templates/$selectedTemplate/";

			if (!file_exists($templateDir)) { # Si no existe el dir. de la template a la hora de subir el archivo, lo creo
				mkdir($templateDir, 0751, true);
			}

			$fileInfo = $_FILES[$inputName];

			$status .= 'Archivo subido encontrado. Info: ' . print_r($fileInfo, true);
			$fileName = basename($fileInfo['name']);
			$targetFilePath = $templateDir . $fileName;

			if (move_uploaded_file($fileInfo['tmp_name'], $targetFilePath)) {
				$status .= "Archivo guardado con éxito en: " . $targetFilePath;
			} else {
				$status .= 'Error al mover el archivo subido.';
			}
		} elseif (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
			$status .= 'Error de subida de archivo. Código: ' . $_FILES[$inputName]['error'];
		}

		file_put_contents(__DIR__ . "/test.txt", $status);

		#parent::execute(...$functionArgs);
	}
}
