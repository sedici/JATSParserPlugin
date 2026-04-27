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
                            {if isset($journal_logo)}{call name="getImage" 
                                pre="<img src='"
                                search=$journal_logo
                                post="' class='frontpage-journal-logo'>"
                            }{/if}
                        </div>
                    </td>
                    <td class="frontpage-journal-info-cell">
                        <div class="frontpage-journal-info">
                            <table class="frontpage-journal-info-table">
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {if isset($journal_title)}{call name="getMetadata" search=$journal_title}{/if}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {if isset($issue_volume)}{call name="getMetadata" pre="{$translations.$locale_key.volume} " search=$issue_volume post=", "}{/if}
                                        {if isset($issue_number)}{call name="getMetadata" pre="{$translations.$locale_key.number} " search=$issue_number post=", "}{/if}
                                        {if isset($publication_pages)}{call name="getMetadata" search=$publication_pages post=", "}{/if}
                                        {if isset($section_title)}{call name="getMetadata" search=$section_title post=", "}{/if}
                                        {if isset($issue_year)}{call name="getMetadata" search=$issue_year}{/if}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        <div class='frontpage-anchor'>
                                            {if isset($doi)}{call name="getLinkedMetadata" preOne="<a href='https://doi.org/" preTwo="'class='frontpage-anchor'>https://doi.org/" search=$doi post="</a>"}{/if}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        <div class="frontpage-anchor">
                                            {if isset($online_issn)}{call name="getMetadata" pre="<span class='frontpage-issn'>ISSN " search=$online_issn post="</span>"}{/if} 
                                            {if isset($online_issn) && $online_issn && isset($journal_url) && $journal_url}
                                                <span class="frontpage-separator"> | </span>
                                            {/if}
                                            {if isset($journal_url)}{call name="getLinkedMetadata" preOne="<a href='" preTwo="'class='frontpage-anchor'>" search=$journal_url post="</a>"}{/if}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="frontpage-journal-info-line">
                                        {if isset($date_submitted)}{call name="getMetadata" pre="{$translations.$locale_key.received}: " search=$date_submitted}{/if}
                                        {if isset($date_accepted)}{call name="getMetadata" pre=" - {$translations.$locale_key.accepted}: " search=$date_accepted}{/if}
                                        {if isset($date_published)}{call name="getMetadata" pre=" - {$translations.$locale_key.published}: " search=$date_published}{/if}
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
                        {if isset($institution_logo)}{call name='getImage' pre="<img src='" search=$institution_logo post="' class='frontpage-institution-logo'>"}{/if}
                    </td>
                </tr>
            </table>
        </header>

        <div class="frontpage-article-body">
            {if isset($article_title)}{call name="getMetadata" pre="<h3 class='frontpage-article-title'>" search=$article_title post="</h3>"}{/if}
            {if isset($subtitles.$locale_key)}
                {call name="getMetadata" pre="<h4 class='frontpage-article-subtitle'>" search=$subtitles.$locale_key post="</h4>"}
            {/if}

            {foreach from=$lang_keys item=key name=keys}
                {if $locale_key != $key && ( (isset($titles.$key) && $titles.$key) || (isset($subtitles.$key) && $subtitles.$key) )}
                    <h5 class="frontpage-article-lang-title">
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
                                    {if isset($author.orcid)}
                                    {call name="getDoubleMetadata"
                                        preOne="<a class='frontpage-anchor' href='" searchOne=$author.orcid postOne="'>"
                                        preTwo="<img src='" searchTwo=$orcid_logo.white_bg postTwo="' class='frontpage-orcid-logo'></a>"
                                    }
                                    {/if}
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
                            {if isset($author.email)}{call name="getLinkedMetadata" preOne="<a href='mailto:'" preTwo="class='frontpage-anchor'>" search=$author.email post="</a>"}{/if}
                        </div>
                        {if isset($author.affiliation.$article_locale_key)}{call name="getMetadata" pre="<div class='frontpage-author-affiliation'>" search=$author.affiliation.$article_locale_key post="</div>"}{/if}
                    </div>
                {/foreach}
            </div>

            <hr class="frontpage-author-hr">

            <div class="frontpage-abstract-section">
                <span>
                    {if isset($abstract_texts.$locale_key)}{call name="getMetadata" pre="<span class='frontpage-abstract-title'> {$translations.$locale_key.abstract} | </span> <span class='frontpage-abstract-text'>" search=$abstract_texts.$locale_key post="</span>"}{/if}
                </span>
            </div>
            {if isset($keywords_texts.$locale_key)}
            <div class="frontpage-keywords-container">
                {call name="isMetadataSet" display="<span class='frontpage-keywords-label'> {$translations.$locale_key.keywords} | </span>" search=$keywords_texts.$locale_key}
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.$locale_key item=keyword name=keywords}
                        {if isset($keyword)}{call name="getMetadata" pre="<span class='frontpage-keyword'>" search=$keyword post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}{/if}
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
                                {if isset($keyword)}{call name="getMetadata" pre="<span class='frontpage-keyword'>" search=$keyword post="</span>{if !$smarty.foreach.keywords.last}, {/if}"}{/if}
                            {/foreach}
                        </span>
                    </div>
                    {/if}
                {/if}
            {/foreach}

            <table class="frontpage-license-info" style="width: 100%; border-collapse: collapse; margin-top: 0px;">
                <tr>
                    <td style="width: 40px; vertical-align: middle;">
                        {if isset($license_url) && isset($license_logo)}
                        {call name="getDoubleMetadata"
                            preOne="<a href='" searchOne=$license_url postOne="'class='frontpage-anchor'>"
                            preTwo="<img src='" searchTwo=$license_logo postTwo="'class='frontpage-license-image'></a>"
                        }
                        {/if}
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div class="frontpage-license-text">
                            {if isset($license_url) && isset($translations.$locale_key.license_text)}
                            {call name="getDoubleMetadata"
                                preOne="<a href='" searchOne=$license_url postOne="' class='frontpage-anchor'>"
                                preTwo="" searchTwo=$translations.$locale_key.license_text postTwo='</a>'
                            }
                            {/if}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>