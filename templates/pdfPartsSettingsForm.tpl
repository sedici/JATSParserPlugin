{block name="page"}
    <form class="pkp_form" id="jatsParserPartsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="pdfPartsSettings" save=true}" enctype="multipart/form-data" style="max-width: 900px; margin: 2rem auto; font-family: Arial, sans-serif; background-color: #ffffff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="jatsParserSettingsFormNotification"}

        {fbvFormArea id="filePartsArea"}
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; margin-top: 10px;">
                <h3 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">
                    {translate key="plugins.generic.jatsParser.pdf.selected.template.title"} {$selectedTemplate|escape}
                </h3>

                <a href="{$go_back_url}" style="
                    display: inline-block; padding: 0.6rem 1.2rem; background-color: #1075b9; color: #ffffff; font-weight: 600;
                    font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; text-decoration: none;
                ">
                    {translate key="plugins.generic.jatsParser.pdf.parts_table.cancel"}
                </a>
            </div>


            {if $filesInformation}

                {capture name=images}
                    <div style="margin-top: 1rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-weight:700;">{translate key="plugins.generic.jatsParser.pdf.basic_config"}</h4>
                        <table style="width: 100%; border-collapse: collapse; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <thead>
                                <tr style="background-color: #f9fafb; text-align: left;">
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.artifact"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.local.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.plugin.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.file.actions"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$filesInformation key=partName item=fileInfo}
                                    {assign var=ext value=$fileInfo.filename|lower|regex_replace:"/.*\.([^.]+)$/":"$1"}
                                    {if $ext == 'png' || $ext == 'jpg' || $ext == 'jpeg' || $ext == 'svg' || $ext == 'tiff'}
                                        <tr style="border-top: 1px solid #e5e7eb;">
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; font-weight: 500;"><a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadPart" template=$selectedTemplate partPath=$fileInfo.using partFile=$fileInfo.filename}" style="text-decoration: none; color: #03949eff;">{$partName|escape|upper}</a> <span style="color: #8a8484ff;">({$fileInfo.filename})</span>
                                                {if !$fileInfo.public}
                                                    <span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">(custom)</span>
                                                {/if}
                                                <br>
                                                <img style="border: 10px solid transparent; padding: 5px; max-width: 200px; max-height: 200px;" src="{$fileInfo.url|escape}">
                                            </td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if !$fileInfo.public}<span style="font-size:1.25rem;color:#dc2626;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if $fileInfo.public}<span style="font-size:1.25rem;color:#16a34a;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #6b7280;">{if !$fileInfo.public}<a onclick="return confirm('Are you sure you want to delete {$fileInfo.filename}?');" href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetPart" partName=$fileInfo.filename template=$selectedTemplate}" style="text-decoration: none; padding: 0.6rem 1.2rem; background-color: #aa0707ff; color: #ffffff; font-weight: 600; font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;">{translate key="plugins.generic.jatsParser.pdf.reset"}</a>{else}<div style="display:flex;gap:0.5rem;"><input type="file" accept="image/png, image/jpeg, image/jpg, .tiff, .svg" name="{$partName|escape}" /><input type="hidden" name="partName[]" value="{$partName|escape}" /></div>{/if}</td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/capture}

                {capture name=css}
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-weight:700;">{translate key="plugins.generic.jatsParser.pdf.intermediate_config"}</h4>
                        <table style="width: 100%; border-collapse: collapse; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <thead>
                                <tr style="background-color: #f9fafb; text-align: left;">
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.artifact"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.local.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.plugin.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.file.actions"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$filesInformation key=partName item=fileInfo}
                                    {assign var=ext value=$fileInfo.filename|lower|regex_replace:"/.*\.([^.]+)$/":"$1"}
                                    {if $ext == 'css'}
                                        <tr style="border-top: 1px solid #e5e7eb;">
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; font-weight: 500;"><a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadPart" template=$selectedTemplate partPath=$fileInfo.using partFile=$fileInfo.filename}" style="text-decoration: none; color: #03949eff;">{$partName|escape|upper}</a> <span style="color: #8a8484ff;">({$fileInfo.filename})</span>
                                                {if !$fileInfo.public}
                                                    <span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">(custom)</span>
                                                {/if}
                                            </td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if !$fileInfo.public}<span style="font-size:1.25rem;color:#dc2626;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if $fileInfo.public}<span style="font-size:1.25rem;color:#16a34a;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #6b7280;">{if !$fileInfo.public}<a onclick="return confirm('Are you sure you want to delete {$fileInfo.filename}?');" href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetPart" partName=$fileInfo.filename template=$selectedTemplate}" style="text-decoration: none; padding: 0.6rem 1.2rem; background-color: #aa0707ff; color: #ffffff; font-weight: 600; font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;">{translate key="plugins.generic.jatsParser.pdf.reset"}</a>{else}<div style="display:flex;gap:0.5rem;"><input type="file" accept=".css" name="{$partName|escape}" /><input type="hidden" name="partName[]" value="{$partName|escape}" /></div>{/if}</td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/capture}

                {capture name=tpls}
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin:0 0 0.5rem 0; font-weight:700;">{translate key="plugins.generic.jatsParser.pdf.advanced_config"}</h4>
                        <table style="width: 100%; border-collapse: collapse; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                            <thead>
                                <tr style="background-color: #f9fafb; text-align: left;">
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.artifact"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.local.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937; text-align: center;">{translate key="plugins.generic.jatsParser.pdf.plugin.file"}</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; font-size: 0.875rem; color: #1f2937;">{translate key="plugins.generic.jatsParser.pdf.file.actions"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$filesInformation key=partName item=fileInfo}
                                    {assign var=ext value=$fileInfo.filename|lower|regex_replace:"/.*\.([^.]+)$/":"$1"}
                                    {if $ext == 'tpl'}
                                        <tr style="border-top: 1px solid #e5e7eb;">
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151; font-weight: 500;"><a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadPart" template=$selectedTemplate partPath=$fileInfo.using partFile=$fileInfo.filename}" style="text-decoration: none; color: #03949eff;">{$partName|escape|upper}</a> <span style="color: #8a8484ff;">({$fileInfo.filename})</span>  
                                                {if !$fileInfo.public}
                                                    <span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">(custom)</span>
                                                {/if}
                                            </td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if !$fileInfo.public}<span style="font-size:1.25rem;color:#dc2626;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; text-align: center; font-size: 1rem;">{if $fileInfo.public}<span style="font-size:1.25rem;color:#16a34a;">✓</span>{else}<span style="font-size:1.25rem;color:#9ca3af;">✗</span>{/if}</td>
                                            <td style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #6b7280;">{if !$fileInfo.public}<a onclick="return confirm('Are you sure you want to delete {$fileInfo.filename}?');" href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetPart" partName=$fileInfo.filename template=$selectedTemplate}" style="text-decoration: none; padding: 0.6rem 1.2rem; background-color: #aa0707ff; color: #ffffff; font-weight: 600; font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;">{translate key="plugins.generic.jatsParser.pdf.reset"}</a>{else}<div style="display:flex;gap:0.5rem;"><input type="file" accept=".tpl" name="{$partName|escape}" /><input type="hidden" name="partName[]" value="{$partName|escape}" /></div>{/if}</td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/capture}

                {$smarty.capture.images}
                {$smarty.capture.css}
                {$smarty.capture.tpls}

            {else}
                <div role="alert" style="margin-top: 1rem; padding: 1rem; background-color: #fee2e2; color: #b91c1c; border-radius: 6px;">
                    <h4 style="margin: 0 0 0.25rem 0; font-weight: 700;">Error</h4>
                    <p style="margin: 0;">No se pudieron cargar los archivos.</p>
                </div>
            {/if}
            
        {/fbvFormArea}
        
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-top:2rem;">
            <div style="display:flex; gap:0.75rem;">
                <button type="submit" style="
                    padding: 0.6rem 1.2rem; background-color: #10b981; color: #ffffff; font-weight: 600;
                    font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;
                ">{translate key="plugins.generic.jatsParser.pdf.parts_table.save"}</button>

                <a onclick="return confirm('{translate key="plugins.generic.jatsParser.pdf.parts_table.reset_all_confirm"}');" href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="resetAllParts" template=$selectedTemplate}" style="
                    display: inline-block; padding: 0.6rem 1.2rem; background-color: #aa0707ff; color: #ffffff; font-weight: 600;
                    font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; text-decoration: none;
                ">
                    {translate key="plugins.generic.jatsParser.pdf.parts_table.reset_all"}
                </a>
            </div>

            <div style="display:flex; gap:0.5rem;">
                <button style="
                    padding: 0.6rem 1.2rem; background-color: #7510b9ff; color: #ffffff; font-weight: 600;
                    font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;
                ">
                    <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadCurrentTemplate" template=$selectedTemplate}" style="text-decoration: none; color: white;">{translate key="plugins.generic.jatsParser.pdf.parts_table.download_current"}</a>
                </button>

                <button style="
                    padding: 0.6rem 1.2rem; background-color: #10b93aff; font-weight: 600;
                    font-size: 0.95rem; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;
                ">
                    <a href="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="downloadOriginalTemplate" template=$selectedTemplate}" style="text-decoration: none; color: white;">{translate key="plugins.generic.jatsParser.pdf.parts_table.download_original"}</a>
                </button>
            </div>
        </div>
    </form>
{/block}
