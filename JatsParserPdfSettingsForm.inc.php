<?php
import('lib.pkp.classes.form.Form');

import('lib.pkp.classes.file.PrivateFileManager');
import('lib.pkp.classes.form.validation.FormValidatorPost');
import('lib.pkp.classes.form.validation.FormValidatorCSRF');
import('lib.pkp.classes.form.validation.FormValidatorRegExp');

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
		$this->addCheck(new FormValidatorRegExp($this, 'pdfTopMargin', 'optional', 'plugins.generic.jatsparser.pdfsettings.margin.error', '/^[0-9]+$/')); # En OJS no existe un type number, así que se verifica con este Regex
		$this->addCheck(new FormValidatorRegExp($this, 'pdfBottomMargin', 'optional', 'plugins.generic.jatsparser.pdfsettings.margin.error', '/^[0-9]+$/'));
		$this->addCheck(new FormValidatorRegExp($this, 'pdfLeftMargin', 'optional', 'plugins.generic.jatsparser.pdfsettings.margin.error', '/^[0-9]+$/'));
		$this->addCheck(new FormValidatorRegExp($this, 'pdfRightMargin', 'optional', 'plugins.generic.jatsparser.pdfsettings.margin.error', '/^[0-9]+$/'));
	}

	function initData()
	{
		$contextId = $this->_journalId;
		$plugin = $this->_plugin;

		$top = $plugin->getSetting($contextId, 'pdfTopMargin') ? $plugin->getSetting($contextId, 'pdfTopMargin') : 25;
		$bottom = $plugin->getSetting($contextId, 'pdfBottomMargin') ? $plugin->getSetting($contextId, 'pdfBottomMargin') : 30;
		$left = $plugin->getSetting($contextId, 'pdfLeftMargin') ? $plugin->getSetting($contextId, 'pdfLeftMargin') : 15;
		$right = $plugin->getSetting($contextId, 'pdfRightMargin') ? $plugin->getSetting($contextId, 'pdfRightMargin') : 15;
		$selectedTemplate = $plugin->getSetting($contextId, 'selectedTemplate') ? $plugin->getSetting($contextId, 'selectedTemplate') : 'plugins.generic.jatsParser.pdf.empty.template';

		# Esto lo hice de esta manera porque sino no funcionaba, se ve que no le copaba demasiado que lo haga in-line o usando ??

		$this->setData('pdfTopMargin', $top);
		$this->setData('pdfBottomMargin', $bottom);
		$this->setData('pdfLeftMargin', $left);
		$this->setData('pdfRightMargin', $right);
		$this->setData('selectedTemplate', $selectedTemplate);
	}

	function readInputData()
	{
		$this->readUserVars(array('fileInput', 'pdfTopMargin', 'pdfBottomMargin', 'pdfLeftMargin', 'pdfRightMargin', 'selectedTemplate'));
	}

	function fetch($request, $template = null, $display = false)
	{
		$templates = $this->_plugin->getAvailablePdfTemplates($request);
		$selectedValue = $this->getData('selectedTemplate');

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'pluginName' => $this->_plugin->getName(),
			'templates' => $templates,
			'selectedTemplateValue' => $selectedValue,
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
		$plugin->updateSetting($contextId, 'pdfTopMargin', $this->getData('pdfTopMargin'));
		$plugin->updateSetting($contextId, 'pdfRightMargin', $this->getData('pdfRightMargin'));
		$plugin->updateSetting($contextId, 'pdfLeftMargin', $this->getData('pdfLeftMargin'));
		$plugin->updateSetting($contextId, 'pdfBottomMargin', $this->getData('pdfBottomMargin'));
		$plugin->updateSetting($contextId, 'selectedTemplate', $this->getData('selectedTemplate'));

		#parent::execute(...$functionArgs);
	}
}
