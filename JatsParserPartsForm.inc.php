<?php
import('lib.pkp.classes.form.Form');

use JATSParser\TemplateHandler\PDF\PDFCreationService;
use PKP\file\PrivateFileManager;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorRegExp;

class JatsParserPartsForm extends Form
{

	var $_journalId;
	var $_plugin;
	var $validationErrors = [];

	function __construct($plugin, $journalId)
	{
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('pdfPartsSettingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	    function fetch($request, $template = null, $display = false)
    {
        $plugin = $this->_plugin;
        $fileManager = new PrivateFileManager();
        $config = $this->_plugin->getConfiguration($request);
        $parts = PDFCreationService::getTemplatePartsAndLocation($config['selected_template'], $plugin, $fileManager, $this->_journalId, $request);

		$templateMgr = TemplateManager::getManager($request);

        $context = $request->getContext();
        $contextPath = $context->getPath();

		$contextBaseUrl = $request->getBaseUrl();

        if ($contextPath && strpos($contextBaseUrl, '/' . $contextPath) === false) {
             $contextBaseUrl .= '/index.php/' . $contextPath;
        }

        $baseUrl = $contextBaseUrl . '/management';
        
		$goBackUrl = $baseUrl . '/settings/website#plugins';
        
        $templateMgr->assign([
            'plugin' => $plugin,
            'pluginName' => $plugin->getName(),
            'go_back_url' => $goBackUrl,
            'selectedTemplate' => $config['selected_template'],
            'filesInformation' => $parts,
            'validationErrors' => $this->validationErrors,
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

		$selectedTemplate = $plugin->getSetting($contextId, 'selectedTemplate') ? $plugin->getSetting($contextId, 'selectedTemplate') : 'plugins.generic.jatsParser.pdf.empty.template';

		$fileManager = new PrivateFileManager();
		$parts = PDFCreationService::getTemplatePartsAndLocation($selectedTemplate, $this->_plugin, $fileManager, $this->_journalId);
		$status = "";

		foreach ($parts as $key => $value) {
			$inputName = $key;

			if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
				$fileManager = new PrivateFileManager();
				$templateDir = $fileManager->getBasePath() . "/journals/$contextId/jatsParser_templates/$selectedTemplate/";

				if (!file_exists($templateDir)) { # Si no existe el dir. de la template a la hora de subir el archivo, lo creo
					mkdir($templateDir, 0751, true);
				}

				$fileInfo = $_FILES[$inputName];

				$status .= 'Archivo subido encontrado. Info: ' . print_r($fileInfo, true);
				$fileName = $value['filename'];
				$targetFilePath = $templateDir . $fileName;

				if (move_uploaded_file($fileInfo['tmp_name'], $targetFilePath)) {
					if (pathinfo($fileName, PATHINFO_EXTENSION) === 'tpl') {
						$smarty = new \Smarty();
						$security = new \Smarty_Security($smarty);
						$security->php_functions = array('isset');
						$security->static_classes = array(null);
						$security->php_modifiers = array('escape', 'count', 'date_format', 'replace', 'trim'); 
						$security->allow_php_templates = false;
						$security->allow_constants = false;
						$security->allow_super_globals = false;
						$security->allow_php_tag = false;
						
						$smarty->enableSecurity($security);
						$security->secure_dir[] = dirname($targetFilePath);
						
						try {
							$template = $smarty->createTemplate('file:' . $targetFilePath);
							$template->compileTemplateSource();
							$status .= "File saved and validated successfully at: " . $targetFilePath . "\n";
						} catch (\Exception $e) {
							@unlink($targetFilePath);
							
							// Sanitizar el mensaje de error para evitar divulgar la ruta privada
							$safeErrorMessage = str_replace('file:' . $targetFilePath, $fileName, $e->getMessage());
							
							$errorMsg = 'Security error in uploaded template (' . $fileName . '): ' . $safeErrorMessage;
							$status .= $errorMsg . "\n";
							$this->validationErrors[] = $errorMsg;
						}
					} else {
						$status .= "Archivo guardado con éxito en: " . $targetFilePath;
					}
				} else {
					$status .= 'Error al mover el archivo subido.';
				}
			} elseif (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
				$status .= 'Error de subida de archivo. Código: ' . $_FILES[$inputName]['error'];
			}
		}

		#file_put_contents(__DIR__ . "/test.txt", $status); # Esto debería cambiarse por un manejo de errores como la gente

		#parent::execute(...$functionArgs);
	}
}
