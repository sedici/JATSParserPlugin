{block name="page"}
    <form class="pkp_form" id="jatsParserPartsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="pdfPartsSettings" save=true}" enctype="multipart/form-data">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

        {fbvFormArea id="filePartsArea"}
            
            <h3 class="pkp_form_title">
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
                                    
                                    {* CELDAS ORIGINALES *}
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
                                            <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetPart" partName=$fileInfo.filename template=$selectedTemplate}" class="pkp_link pkp_link_reset">
                                                {translate key="plugins.generic.jatsParser.pdf.reset"}
                                            </a>
                                        {else}
                                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                <input type="file" name="{$partName|escape}" style="font-size: 0.75rem;" />
                                                <input type="hidden" name="partName[]" value="{$partName|escape}" />
                                            </div>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    
                    <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadTemplate"}" class="pkp_button pkp_button_download" style="margin-top: 1.5rem;">
                        {translate key="plugins.generic.jatsParser.pdf.download.template"}
                    </a>
                    
                </div>
                
            {else}
                <div class="pkp_notification pkp_notification_error" role="alert" style="margin-top: 1rem;">
                    <h4>{translate key="common.error"}</h4>
                    <p>{translate key="plugins.generic.jatsParser.pdf.error.loading"}</p>
                </div>
            {/if}
            
        {/fbvFormArea}
        
        {fbvFormButtons}
    </form>
{/block}