<tab id="pdfSettingsTab" label="{translate key="plugins.generic.jatsParser.pdf.settings.tab"}">

    <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">
        {translate key="plugins.generic.jatsParser.pdf.selected.template.title"} {$selectedTemplate|escape}
    </h3>

    {if $filesInformation}
    <div style="margin-top: 1rem;">
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden;">
        <thead>
            <tr style="border-bottom: 2px solid #d1d5db; background-color: #f3f4f6;">
            <th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.artifact"}</th>
            <th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937; border-right: 1px solid #e5e7eb; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.plugin.file"}</th>
            <th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.local.file"}</th>
            <th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.file.actions"}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$filesInformation key=partName item=fileInfo}
            <tr style="border-bottom: 1px solid #e5e7eb;">
                
                <td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; font-weight: 500; border-right: 1px solid #e5e7eb;">
                {$partName|escape|upper}
                {if !$fileInfo.public}
                    <span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">({translate key="plugins.generic.jatsParser.pdf.custom.file"})</span>
                {/if}
                </td>
                
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; border-right: 1px solid #e5e7eb; text-align: center;">
				{if $fileInfo.public}
					<span style="font-size: 1.25rem; font-weight: 700; color: #16a34a;" aria-label="Usando archivo público">✓</span>
				{else}
					<span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo público">✗</span>
				{/if}
				</td>
				
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; text-align: center;">
				{if !$fileInfo.public}
					<span style="font-size: 1.25rem; font-weight: 700; color: #dc2626;" aria-label="Usando archivo privado">✓</span>
				{else}
					<span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo privado">✗</span>
				{/if}
				</td>

                <td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #6b7280;">
                {if !$fileInfo.public}
                    <a href="{call_hook name="Template::Settings::website::url" plugin=$plugin op="resetPart" partName=$partName|escape}" style="color: #dc2626; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer; font-size: 0.875rem;">
                        {translate key="plugins.generic.jatsParser.pdf.reset"}
                    </a>
                {else}
                    <form method="post" enctype="multipart/form-data" action="{call_hook name="Template::Settings::website::url" plugin=$plugin op="uploadPart"}" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="hidden" name="partName" value="{$partName|escape}" />
                        <input type="file" name="uploadedFile" style="font-size: 0.75rem;" />
                        <input type="submit" value="{translate key='plugins.generic.jatsParser.pdf.upload.file'}" style="color: #2563eb; text-decoration: underline; background: none; border: none; padding: 0 0 0 1rem; cursor: pointer; font-size: 0.875rem;" />
                    </form>
                {/if}
                </td>
                
            </tr>
            {/foreach}
        </tbody>
        </table>
        
        {* Botón para descargar toda la plantilla *}
        <a href="{call_hook name="Template::Settings::website::url" plugin=$plugin op="downloadTemplate"}" style="
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background-color: #2563eb; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        ">{translate key="plugins.generic.jatsParser.pdf.download.template"}</a>
        
    </div>
    {else}
    <div style="padding: 1rem; background-color: #fee2e2; border: 1px solid #f87171; color: #b91c1c; border-radius: 4px;" role="alert">
        <p style="font-weight: 700;">{translate key="common.error"}</p>
        <p>{translate key="plugins.generic.jatsParser.pdf.error.loading"}</p>
    </div>
    {/if}

</tab>