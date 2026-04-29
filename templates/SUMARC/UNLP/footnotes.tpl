{include file={$baseFunctions}}

<style>
  {include file={$footnotes_css}}

  {if isset($custom_css) && $custom_css}
    {include file={$custom_css}}
  {/if}
</style>

<h2 class="footnotes-title">{if isset($translations.$locale_key.footnotes)}{$translations.$locale_key.footnotes}{/if}</h2>
