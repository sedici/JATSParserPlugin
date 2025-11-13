<script>
	$(function() {ldelim}
		$('#jatsParserPdfSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
</script>

<form class="pkp_form" id="jatsParserPdfSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="pdfSettings" save=true}" enctype="multipart/form-data">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

	{fbvFormArea id="templateForm" title="plugins.generic.jatsParser.template.config"}
		{fbvFormSection list=true}
			{foreach from=$templates item="templateItem"}
				{if empty($templateItem.id)}
					{assign var="checkedValue" value=$noneSelectedValue}
				{else}
					{assign var="checkedValue" value=$templateItem.id|compare:$selectedTemplateValue}
				{/if}

				{fbvElement type="radio" 
						id="template_{$templateItem.id|escape}" 
						name="selectedTemplate" 
						value=$templateItem.id|escape 
						checked=$checkedValue 
						label=$templateItem.title|escape}
			{/foreach}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="marginsForm" title="plugins.generic.jatsParser.margins.config"}
		{fbvFormSection list=true}
			{fbvElement type="text" id="pdfTopMargin" name="pdfTopMargin" label="plugins.generic.jatsParser.top.margin" value=$pdfTopMargin}
			{fbvElement type="text" id="pdfBottomMargin" name="pdfBottomMargin" label="plugins.generic.jatsParser.bottom.margin" value=$pdfBottomMargin}
			{fbvElement type="text" id="pdfLeftMargin" name="pdfLeftMargin" label="plugins.generic.jatsParser.left.margin" value=$pdfLeftMargin}
			{fbvElement type="text" id="pdfRightMargin" name="pdfRightMargin" label="plugins.generic.jatsParser.right.margin" value=$pdfRightMargin}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>