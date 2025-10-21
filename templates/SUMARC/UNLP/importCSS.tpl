<style>
  {*
    {include file={$body}}
    {include file={$frontpage}}
    {include file={$references}}
    {include file={$footnotes}}
  *}
  
  {include file={$header}}
  {include file={$footer}}

  {*
    NO se puede hacer el include del body, references, footnotes y frontpage acá debido a que muchas cosas son traídas desde JATSParser,
    el cual NO usa clases, por lo tanto está lleno de tags genéricos que pisarían el estilo de otras partes. Si se hace el include acá adentro no habría drama,
    pero hasta que no se arregle lo mencionado arriba, no hay forma de hacerlo sin pisar estilos de otras partes
  *}

  {if isset($extraCSS) && $extraCSS}
    {include file={$extraCSS}}
  {/if}
</style>