{block name="page"}
    <form class="pkp_form" id="jatsParserPartsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="pdfPartsSettings" save=true}" enctype="multipart/form-data" style="max-width: 900px; margin: 2rem auto; font-family: Arial, sans-serif; background-color: #ffffff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

        {fbvFormArea id="filePartsArea"}
            
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">
                {translate key="plugins.generic.jatsParser.pdf.selected.template.title"} {$selectedTemplate|escape}
            </h3>

            {if $filesInformation}
                
                <div style="margin-top: 1rem;">
                    <table style="width: 100%; border-collapse: collapse; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                        <thead>
                            <tr style="background-color: #f9fafb; text-align: left;">
                                <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-transform: uppercase;">{translate key="plugins.generic.jatsParser.pdf.artifact"}</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-transform: uppercase; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.plugin.file"}</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-transform: uppercase; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.local.file"}</th>
                                <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-transform: uppercase;">{translate key="plugins.generic.jatsParser.pdf.file.actions"}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$filesInformation key=partName item=fileInfo}
                                <tr style="border-top: 1px solid #e5e7eb;">
                                    
                                    <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; font-weight: 500;">
                                        {$partName|escape|upper}
                                        {if !$fileInfo.public}
                                            <span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">({translate key="plugins.generic.jatsParser.pdf.custom.file"})</span>
                                        {/if}
                                    </td>
                                    
                                    <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">
                                        {if $fileInfo.public}
                                            <span style="font-size: 1.25rem; font-weight: 700; color: #16a34a;" aria-label="Usando archivo público">✓</span>
                                        {else}
                                            <span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo público">✗</span>
                                        {/if}
                                    </td>
                                    
                                    <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">
                                        {if !$fileInfo.public}
                                            <span style="font-size: 1.25rem; font-weight: 700; color: #dc2626;" aria-label="Usando archivo privado">✓</span>
                                        {else}
                                            <span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo privado">✗</span>
                                        {/if}
                                    </td>

                                    <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #6b7280;">
                                        {if !$fileInfo.public}
                                            <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetPart" partName=$fileInfo.filename template=$selectedTemplate}" style="color: #ef4444; text-decoration: none; font-weight: 500; padding: 0.25rem 0.5rem; border-radius: 4px; background-color: #fee2e2;">{translate key="plugins.generic.jatsParser.pdf.reset"}</a>
                                        {else}
                                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                <input type="file" name="{$partName|escape}" style="font-size: 0.875rem; padding: 0.25rem;" />
                                                <input type="hidden" name="partName[]" value="{$partName|escape}" />
                                            </div>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    
                    <!-- <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadTemplate"}" style="display: inline-block; margin-top: 1.5rem; padding: 0.5rem 1rem; background-color: #2563eb; color: #ffffff; font-weight: 600; border-radius: 6px; text-decoration: none; font-size: 0.875rem;">{translate key="plugins.generic.jatsParser.pdf.download.template"}</a> -->
                    
                </div>
                
            {else}
                <div role="alert" style="margin-top: 1rem; padding: 1rem; background-color: #fee2e2; color: #b91c1c; border-radius: 6px;">
                    <h4 style="margin: 0 0 0.25rem 0; font-weight: 700;">{translate key="common.error"}</h4>
                    <p style="margin: 0;">{translate key="plugins.generic.jatsParser.pdf.error.loading"}</p>
                </div>
            {/if}
            
        {/fbvFormArea}
        
        <button type="submit" style="
            margin-top: 2rem; padding: 0.6rem 1.2rem; background-color: #10b981; color: #ffffff; font-weight: 600;
            font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;
        ">Ok</button>
    </form>
{/block}
