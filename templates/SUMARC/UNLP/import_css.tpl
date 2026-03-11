<style>
  {*
    {include file={$body_css}}
    {include file={$frontpage_css}}
    {include file={$references_css}}
    {include file={$footnotes_css}}
  *}
  
  {include file={$header_css}}
  {include file={$footer_css}}

  {*
    NO se puede hacer el include del body, references, footnotes y frontpage acá debido a que muchas cosas son traídas desde JATSParser,
    el cual NO usa clases, por lo tanto está lleno de tags genéricos que pisarían el estilo de otras partes. Si se hace el include acá adentro no habría drama,
    pero hasta que no se arregle lo mencionado arriba, no hay forma de hacerlo sin pisar estilos de otras partes
  *}

  {if isset($custom_css) && $custom_css}
    {include file={$custom_css}}
  {/if}
</style>