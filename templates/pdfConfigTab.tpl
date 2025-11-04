<tab id="pdfSettingsTab" label="{translate key="plugins.generic.jatsParser.pdf.settings.tab"}">

	<h1>{$selectedTemplate}</h1>

	{if $filesInformation}
	<div style="margin-top: 1rem;">
		<table style="width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; border-radius: 4px; overflow: hidden;">
		<thead>
			<tr style="border-bottom: 2px solid #d1d5db; background-color: #f3f4f6;">
			<th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937;">Parte</th>
			<th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937; border-right: 1px solid #e5e7eb; text-align: center;">Local</th>
			<th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937; text-align: center;">Privado</th>
			<th style="padding: 1rem 1.25rem; vertical-align: middle; font-weight: 600; text-transform: uppercase; color: #1f2937;">Acciones</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$filesInformation key=partName item=fileInfo}
			<tr style="border-bottom: 1px solid #e5e7eb;">
				
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; font-weight: 500; border-right: 1px solid #e5e7eb;">
				{$partName|escape}
				{if $fileInfo.private}
					<span style="font-size: 0.75rem; color: #b45309; font-weight: 400; margin-left: 0.5rem;">(Personalizado)</span>
				{/if}
				</td>
				
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; border-right: 1px solid #e5e7eb; text-align: center;">
				{if !$fileInfo.private}
					<span style="font-size: 1.25rem; font-weight: 700; color: #16a34a;" aria-label="Usando archivo público">✓</span>
				{else}
					<span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo público">✗</span>
				{/if}
				</td>
				
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #374151; text-align: center;">
				{if $fileInfo.private}
					<span style="font-size: 1.25rem; font-weight: 700; color: #dc2626;" aria-label="Usando archivo privado">✓</span>
				{else}
					<span style="font-size: 1.25rem; font-weight: 700; color: #9ca3af;" aria-label="No usando archivo privado">✗</span>
				{/if}
				</td>
				
				<td style="padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #6b7280;">
				<button style="color: #2563eb; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer; font-size: 0.875rem;" title="Acción 1 {$partName|escape} ({$fileInfo.filename|escape})">
					Acción 1
				</button>
				<button style="color: #2563eb; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer; font-size: 0.875rem;" title="Acción 1 {$partName|escape} ({$fileInfo.filename|escape})">
					Acción 2
				</button>
				<button style="color: #2563eb; text-decoration: underline; background: none; border: none; padding: 0; cursor: pointer; font-size: 0.875rem;" title="Acción 1 {$partName|escape} ({$fileInfo.filename|escape})">
					Acción 3
				</button>
				</td>
				
			</tr>
			{/foreach}
		</tbody>
		</table>
	</div>
	{else}
	<div style="padding: 1rem; background-color: #fee2e2; border: 1px solid #f87171; color: #b91c1c; border-radius: 4px;" role="alert">
		<p style="font-weight: 700;">Error de Carga</p>
		<p>No se pudo cargar la información de las partes de la plantilla.</p>
	</div>
	{/if}

</tab>
