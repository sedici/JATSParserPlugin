{include file={$baseFunctions}}

<style>
    {include file={$frontpage_css}}

    {if isset($custom_css) && $custom_css}
        {include file={$custom_css}}
    {/if}
</style>

<div class="frontpage-body">
    <div class="frontpage-container">
        <header class="frontpage-journal-header">
            <table class="frontpage-logo-info-table">
                <tr>
                    <td class="frontpage-logo-cell">
                        <div class="frontpage-journal-logos">
                            {call name="getImage" 
                                pre="<img src='"
                                search={$journal_logo}
                                post="' class='frontpage-journal-logo'>"
                            }
                        </div>
                    </td>
                    <td class="frontpage-journal-info-cell">
                        <div class="frontpage-journal-info">
                            <table class="frontpage-journal-info-table">
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {call name="getMetadata" search={$journal_title}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {if isset($translations.$locale_key.volume)}{call name="getMetadata" pre="{$translations.$locale_key.volume} " search={$issue_volume} post=", "}{/if}
                                        {if isset($translations.$locale_key.number)}{call name="getMetadata" pre="{$translations.$locale_key.number} " search={$issue_number} post=", "}{/if}
                                        {call name="getMetadata" search={$publication_pages} post=", "}
                                        {call name="getMetadata" search={$section_title} post=", "}
                                        {call name="getMetadata" search={$issue_year}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        <div class='frontpage-anchor'>
                                            {call name="getLinkedMetadata" preOne="<a href='https://doi.org/" preTwo="'class='frontpage-anchor'>https://doi.org/" search={$doi} post="</a>"}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        <div class="frontpage-anchor">
                                            {call name="getMetadata" pre="<span class='frontpage-issn'>ISSN " search={$online_issn} post="</span>"} 
                                            {if isset($online_issn) && $online_issn && isset($journal_url) && $journal_url}
                                                <span class="frontpage-separator"> | </span>
                                            {/if}
                                            {call name="getLinkedMetadata" preOne="<a href='" preTwo="'class='frontpage-anchor'>" search={$journal_url} post="</a>"}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {if isset($translations.$locale_key.received)}{call name="getMetadata" pre="{$translations.$locale_key.received}: " search={$date_submitted}}{/if}
                                        {if isset($translations.$locale_key.accepted)}{call name="getMetadata" pre=" - {$translations.$locale_key.accepted}: " search={$date_accepted}}{/if}
                                        {if isset($translations.$locale_key.published)}{call name="getMetadata" pre=" - {$translations.$locale_key.published}: " search={$date_published}}{/if}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="frontpage-header-table">
                <tr>
                    <td class="frontpage-header-hr-cell">
                        <hr class="frontpage-hr">
                    </td>
                    <td class="frontpage-institution-logo-cell">
                        {call name='getImage' pre="<img src='" search={$institution_logo} post="' class='frontpage-institution-logo'>"}
                    </td>
                </tr>
            </table>
        </header>

        <div class="frontpage-article-body">
            {call name="getMetadata" pre="<h3 class='frontpage-article-title'>" search={$full_title} post="</h3>"}
            {if isset($subtitles.$locale_key)}
                {call name="getMetadata" pre="<h4 class='frontpage-article-subtitle'>" search=$subtitles.$locale_key post="</h4>"}
            {/if}

            {foreach from=$lang_keys item=key name=keys}
                {if $locale_key != $key && ( (isset($titles.$key) && $titles.$key) || (isset($subtitles.$key) && $subtitles.$key) )}
                    <h5 class="frontpage-article-lang-title">
                        {if isset($prefixes.$key)}{call name="getMetadata" pre="<span>" search=$prefixes.$key post=" </span>"}{/if}
                        {if isset($titles.$key)}{call name="getMetadata" pre="<span>" search=$titles.$key post="</span>."}{/if}
                        {if isset($subtitles.$key)}{call name="getMetadata" pre="<span>" search=$subtitles.$key post="</span>"}{/if}
                    </h5>
                {/if}
            {/foreach}

            <div class="frontpage-author-info">
                {foreach from=$authors item=author}
                    <div class="frontpage-author-separation">
                        <table style="border-collapse: collapse;">
                            <tr>
                                <td style="padding: 0; vertical-align: middle;">
                                    {call name="getDoubleMetadata"
                                        preOne="<a class='frontpage-anchor' href='" searchOne="{$author.orcid}" postOne="'>"
                                        preTwo="<img src='" searchTwo="{$orcid_logo.white_bg}" postTwo="' class='frontpage-orcid-logo'></a>"
                                    }
                                </td>
                                <td style="padding: 0; vertical-align: middle;">
                                    <div class="frontpage-author-name-colored">
                                        {if isset($author.givenName.$article_locale_key)}{call name="getMetadata" search=$author.givenName.$article_locale_key}{/if}
                                        {if isset($author.familyName.$article_locale_key)}{call name="getMetadata" search=$author.familyName.$article_locale_key}{/if}
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="frontpage-author-contact">
                            {call name="getLinkedMetadata" preOne="<a href='mailto:'" preTwo="class='frontpage-anchor'>" search=$author.email post="</a>"}
                        </div>
                        {if isset($author.affiliation.$article_locale_key)}{call name="getMetadata" pre="<div class='frontpage-author-affiliation'>" search=$author.affiliation.$article_locale_key post="</div>"}{/if}
                    </div>
                {/foreach}
            </div>

            <hr class="frontpage-author-hr">

            {if isset($abstract_texts.$locale_key)}
            <div class="frontpage-abstract-section">
                <span>
                    {call name="getMetadata" pre="<span class='frontpage-abstract-title'> {$translations.$locale_key.abstract} | </span> <span class='frontpage-abstract-text'>" search=$abstract_texts.$locale_key post="</span>"}
                </span>
            </div>
            {/if}
            {if isset($keywords_texts.$locale_key)}
            <div class="frontpage-keywords-container">
                {call name="isMetadataSet" display="<span class='frontpage-keywords-label'> {$translations.$locale_key.keywords} | </span>" search=$keywords_texts.$locale_key}
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.$locale_key item=keyword name=keywords}
                        {call name="getMetadata" pre="<span class='frontpage-keyword'>" search=$keyword post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}
                    {/foreach}
                </span>
            </div>
            {/if}

            {foreach from=$lang_keys item=key name=keys}
                {if $locale_key != $key}
                    {if isset($abstract_texts.$key)}
                    <div class="frontpage-abstract-section">
                        <span>
                            {call name="getMetadata" pre="<span class='frontpage-abstract-title'> {$translations.$key.abstract} | </span> <span class='frontpage-abstract-text'>" search=$abstract_texts.$key post="</span>"}
                        </span>
                    </div>
                    {/if}
                    {if isset($keywords_texts.$key)}
                    <div class="frontpage-keywords-container">
                        {call name="isMetadataSet" display="<span class='frontpage-keywords-label'> {$translations.$key.keywords} | </span>" search=$keywords_texts.$key}
                        <span class="frontpage-keywords-list">
                            {foreach from=$keywords_texts.$key item=keyword name=keywords}
                                {call name="getMetadata" pre="<span class='frontpage-keyword'>" search=$keyword post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}
                            {/foreach}
                        </span>
                    </div>
                    {/if}
                {/if}
            {/foreach}

            <table class="frontpage-license-info" style="width: 100%; border-collapse: collapse; margin-top: 0px;">
                <tr>
                    <td style="width: 40px; vertical-align: middle;">
                        {call name="getDoubleMetadata"
                            preOne="<a href='" searchOne={$license_url} postOne="'class='frontpage-anchor'>"
                            preTwo="<img src='" searchTwo={$license_logo} postTwo="'class='frontpage-license-image'></a>"
                        }
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div class="frontpage-license-text">
                            {if isset($translations.$locale_key.license_text)}
                            {call name="getDoubleMetadata"
                                preOne="<a href='" searchOne={$license_url} postOne="' class='frontpage-anchor'>"
                                preTwo="" searchTwo="{$translations.$locale_key.license_text}" postTwo='</a>'
                            }
                            {/if}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>