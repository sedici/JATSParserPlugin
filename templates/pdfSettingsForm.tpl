<script>
  $(function() {ldelim}
		$('#jatsParserSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<form class="pkp_form" id="jatsParserPdfSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="pdfSettings" save=true}" enctype="multipart/form-data">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

	{fbvFormArea id="templateForm" title="Configuración de plantillas PDF"}
			{fbvFormSection list=true}
					{fbvElement type="select"
							id="selectedTemplate"
							label="Plantilla de Conversión JATS a PDF"
							required="true"
							value=$selectedTemplateValue|escape
							from=$templates
							translate=false
							size=$templates|@count
					}
			{/fbvFormSection}
	{/fbvFormArea}

		{fbvFormArea id="marginsForm" title="Configuración de márgenes"}
			{fbvFormSection list=true}
				{fbvElement type="text" id="pdfTopMargin" name="pdfTopMargin" label="top margin"}
				{fbvElement type="text" id="pdfBottomMargin" name="pdfBottomMargin" label="bottom margin"}
				{fbvElement type="text" id="pdfLeftMargin" name="pdfLeftMargin" label="left margin"}
				{fbvElement type="text" id="pdfRightMargin" name="pdfRightMargin" label="right margin"}
			{/fbvFormSection}
		{/fbvFormArea}

    {fbvFormArea id="file" title="Subida de archivos a modo de test"}
			{fbvFormSection for="fileSection" list=true description="Acá debería mandarte a leer la doc para los nombres de los archivos"}
				{fbvElement type="file" id="fileInput" name="fileInput" label="asd"}
			{/fbvFormSection}
    {/fbvFormArea}

	{fbvFormButtons}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>