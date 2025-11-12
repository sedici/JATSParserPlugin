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
                                        {call name="getMetadata" pre="{$translations.{$locale_key}.volume} " search={$issue_volume}}
                                        {call name="getMetadata" pre="{$translations.{$locale_key}.number} " search={$issue_number}}
                                        {call name="getMetadata" pre="(" search={$issue_year} post=")"}
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
                                        {call name="getMetadata" pre="{$translations.{$locale_key}.received}: " search={$date_submitted}}
                                        {call name="getMetadata" pre=" - {$translations.{$locale_key}.accepted}: " seach={$date_accepted}}
                                        {call name="getMetadata" pre=" - {$translations.{$locale_key}.published}: " search={$date_published}}
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
            {call name="getMetadata" pre="<h3 class='frontpage-article-title'>" search={$article_title} post="</h3>"}
            {call name="getMetadata" pre="<h4 class='frontpage-article-subtitle'>" search={$subtitles.{$locale_key}} post="</h4>"}

            {foreach from=$lang_keys item=key name=keys}
                {if $locale_key != $key}
                    <h5 class="frontpage-article-lang-title">
                        {call name="getMetadata" pre="<span>" search={$titles.{$key}} post="</span>."}
                        {call name="getMetadata" pre="<span>" search={$subtitles.{$key}} post="</span>"}
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
                                        {call name="getMetadata" search={$author.givenName.{$article_locale_key}}}
                                        {call name="getMetadata" search={$author.familyName.{$article_locale_key}}}
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="frontpage-author-contact">
                            {call name="getLinkedMetadata" preOne="<a href='mailto:'" preTwo="class='frontpage-anchor'>" search=$author.email post="</a>"}
                        </div>
                        {call name="getMetadata" pre="<div class='frontpage-author-affiliation'>" search={$author.affiliation.{$article_locale_key}} post="</div>"}
                    </div>
                {/foreach}
            </div>

            <hr class="frontpage-author-hr">

            <div class="frontpage-abstract-section">
                <span>
                    {call name="getMetadata" pre="<span class='frontpage-abstract-title'> {$translations.{$locale_key}.abstract} | </span> <span class='frontpage-abstract-text'>" search={$abstract_texts.{$locale_key}} post="</span>"}
                </span>
            </div>
            <div class="frontpage-keywords-container">
                {call name="isMetadataSet" display="<span class='frontpage-keywords-label'> {$translations.{$locale_key}.keywords} | </span>" search="{$keywords_texts.{$locale_key}}"}
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.{$locale_key} item=keyword name=keywords}
                        {call name="getMetadata" pre="<span class='frontpage-keyword'>" search={$keyword} post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}
                    {/foreach}
                </span>
            </div>

            {foreach from=$lang_keys item=key name=keys}
                {if $locale_key != $key}
                    <div class="frontpage-abstract-section">
                        <span>
                            {call name="getMetadata" pre="<span class='frontpage-abstract-title'> {$translations.{$key}.abstract} | </span> <span class='frontpage-abstract-text'>" search={$abstract_texts.{$key}} post="</span>"}
                        </span>
                    </div>
                    <div class="frontpage-keywords-container">
                        {call name="isMetadataSet" display="<span class='frontpage-keywords-label'> {$translations.{$key}.keywords} | </span>" search="{$keywords_texts.{$key}}"}
                        <span class="frontpage-keywords-list">
                            {foreach from=$keywords_texts.{$key} item=keyword name=keywords}
                                {call name="getMetadata" pre="<span class='frontpage-keyword'>" search={$keyword} post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}
                            {/foreach}
                        </span>
                    </div>
                {/if}
            {/foreach}

            <table class="frontpage-license-info" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="width: 40px; vertical-align: middle;">
                        {call name="getDoubleMetadata"
                            preOne="<a href='" searchOne={$license_url} postOne="'class='frontpage-anchor'>"
                            preTwo="<img src='" searchTwo={$license_logo} postTwo="'class='frontpage-license-image'></a>"
                        }
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div class="frontpage-license-text">
                            {call name="getDoubleMetadata"
                                preOne="<a href='" searchOne={$license_url} postOne="' class='frontpage-anchor'>"
                                preTwo="" searchTwo="{$translations.{$locale_key}.license_text}" postTwo='</a>'
                            }
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>