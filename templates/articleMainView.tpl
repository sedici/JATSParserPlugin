{**
 * plugins/generic/jatsParser/templates/articleMainView.tpl
 *
 * Copyright (c) 2017-2020 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 * @brief Page for displaying JATS XML galley as HTML on article landing page
 *}

<div class="jatsParser__article-fulltext{if $hasFullTextForLocale} jatsParser__article-fulltext--has-content{/if}" id="jatsParserFullText">
	{$fullText}
</div>

{* Plugin stylesheet injected only when full-text is active for the current locale *}
{if $jatsParserPluginUrl && $hasFullTextForLocale}
<link rel="stylesheet" href="{$jatsParserPluginUrl}/resources/styles/preview.css">
{/if}
