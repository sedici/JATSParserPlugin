{include file='./baseFunctions.tpl'}

<style>
    .frontpage-body {
        font-family: 'Philosopher', sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        background-color: #ffffff;
        color: #000;
    }

    .frontpage-container {
        max-width: 800px;
        box-sizing: border-box;
        position: relative;
    }

    .frontpage-journal-header {
        position: relative;
    }

    .frontpage-journal-logos {
        position: relative;
        display: block;
    }

    .frontpage-journal-logo {
        width: auto;
        height: 80px;
        display: block;
    }

    .frontpage-unlp-logo {
        width: auto;
        height: 50px;
    }

    .frontpage-journal-info {
        font-family: 'Helvetica', sans-serif;
        font-size: 8pt;
        color: #444;
        line-height: 1.4;
        text-align: left;
        vertical-align: top;
    }

    .frontpage-journal-info a {
        color: #007bff;
        text-decoration: none;
    }

    .frontpage-journal-info-line {
        margin-bottom: 5px;
    }

    .frontpage-article-body {
        padding-top: 0;
    }

    .frontpage-article-title {
        font-family: 'Arial', sans-serif;
        font-size: 18px;
        font-weight: bold;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-top: 25px;
        margin-bottom: 0px;
    }

    .frontpage-article-subtitle {
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        font-weight: bold;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-top: 0px;
    }

    .frontpage-article-lang-title {
        font-weight: normal;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-bottom: 20px;
    }

    .frontpage-author-info {
        font-family: "Arial", sans-serif;
        font-size: 10pt;
        margin-bottom: 5px;
        line-height: 1.4;
    }

    .frontpage-orcid-logo {
        height: 1em;
        vertical-align: middle;
        margin-right: 5px;
    }

    .frontpage-author-name {
        font-size: 7.5pt;
        font-weight: bold;
        color: rgb(49, 132, 155);
    }

    .frontpage-author-name-colored {
        font-size: 9pt;
        font-weight: bold;
        color: #616161ff;
    }

    .frontpage-author-contact {
        font-family: "Arial", sans-serif;
        font-size: 8.5pt;
        color: #555;
        margin-top: 2px;
        margin-bottom: 2px;
    }

    .frontpage-affiliation {
        font-family: "Arial";
        font-size: 9px;
        color: #000;
        text-decoration: bold;
    }

    .frontpage-abstract-section {
        font-family: "Georgia";
        font-size: 10pt;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 5px;
    }

    .frontpage-abstract-title {
        font-size: 10pt;
        font-weight: bold;
        color: rgb(49, 132, 155);
        white-space: nowrap;
    }

    .frontpage-abstract-text {
        font-size: 8pt;
        color: #333;
        text-align: justify;
    }

    .frontpage-keywords-container {
        margin-bottom: 15px;
        font-size: 9pt;
        line-height: 1.5;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 5px;
    }

    .frontpage-keywords-label {
        font-weight: bold;
        color: rgb(49, 132, 155);
        white-space: nowrap;
    }

    .frontpage-keywords-list {
        display: inline;
        margin: 0;
        padding: 0;
    }

    .frontpage-license-info {
        font-size: 7.5pt;
        color: #666;
        margin-top: 20px;
        text-align: left;
    }

    .frontpage-license-image {
        max-width: auto;
        max-height: 30px;
        margin-right: 10px;
    }

    .frontpage-page-footer {
        border-top: 1px solid #ddd;
        padding-top: 10px;
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
        font-size: 7.5pt;
        color: #777;
    }

    .frontpage-footer-text {
        font-weight: normal;
    }

    .frontpage-page-number {
        text-align: right;
    }

    .frontpage-hr {
        border: none;
        height: 1px;
        color: solid;
        background-color: black;
    }
    
    .frontpage-anchor {
        color: rgb(49, 132, 155);
        text-decoration: none;
    }
</style>

<div class="frontpage-body">
    <div class="frontpage-container">
        <header class="frontpage-journal-header">
            <table style="width: 100%; border-collapse: collapse; padding-left= 0px;">
                <tr>
                    <td style="vertical-align: right;">
                        <div class="frontpage-journal-logos">
                            {call name="getMetadata" 
                                pre="<img src='"
                                search={$journal_logos_path.es}
                                post="' class='frontpage-journal-logo'>"
                            }
                        </div>
                    </td>
                    <td style="vertical-align: middle; text-align: left;">
                        <div class="frontpage-journal-info">
                            {call name="getMetadata" pre="<div class='frontpage-journal-info-line'>" search={$journal_title} post="</div>"}
                            <div class="frontpage-journal-info-line">
                                {call name="getMetadata" pre="Vol." search={$issue_volume}}
                                {call name="getMetadata" pre="No." search={$issue_number}}
                                {call name="getMetadata" pre="(" search={$issue_year} post=")"}
                            </div>
                            <div class="frontpage-journal-info-line">
                                {call name="getLinkedMetadata" preOne="<a href='https://doi.org/" preTwo="'class='frontpage-anchor'>https://doi.org/" search={$doi} post="</a>"}
                            </div>
                            {call name="getMetadata" pre="<div class='frontpage-journal-info-line'>ISSN " search={$online_issn} post="</div>"}
                            <div class="frontpage-journal-info-line">
                                {call name="getLinkedMetadata" preOne="<a href='" preTwo="'class='frontpage-anchor'>" search={$journal_url} post="</a>"}
                            </div>
                            <div class="frontpage-journal-info-line">
                                {call name="getMetadata" pre="Recibido: " search={$date_submitted} post=" - "}
                                {call name="getMetadata" pre="Aceptado: " seach={$date_accepted} post=" - "}
                                {call name="getMetadata" pre="Publicado: " search={$date_published}}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; position: relative;">
                <tr>
                    <td style="width: 80%;">
                        <hr class="frontpage-hr">
                    </td>
                    <td style="width: 20%; text-align: right;">
                        <img src="https://cdn.discordapp.com/attachments/1290089303879323690/1414985947015282698/af0ad727-1507-48a0-acbe-6a5cf8fb28e0.png?ex=68d20a9a&is=68d0b91a&hm=acab17166e7fc93d7061e40525de96927486c500d97070ee81c51ca0c247168a&" class="frontpage-unlp-logo">
                    </td>
                </tr>
            </table>
        </header>

        <div class="frontpage-article-body">
            {call name="getMetadata" pre="<h3 class='frontpage-article-title'>" search={$article_title} post="</h3>"}
            {call name="getMetadata" pre="<h4 class='frontpage-article-subtitle'>" search={$subtitles.es} post="</h4>"}
            <h5 class="frontpage-article-lang-title">
                {call name="getMetadata" pre="<span>" search={$titles.en} post="</span>."}
                {call name="getMetadata" pre="<span>" search={$subtitles.en} post="</span>"}
            </h5>

            <div class="frontpage-author-info">
                {foreach from=$authors item=author}
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
                                    {call name="getMetadata" search={$author.givenName.es}}
                                    {call name="getMetadata" search={$author.familyName.es}}
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="frontpage-author-contact">
                        {call name="getLinkedMetadata" preOne="<a href='mailto:'" preTwo="class='frontpage-anchor'>" search=$author.email post="</a>"}
                    </div>
                    {call name="getMetadata" pre="<div class='frontpage-author-affiliation'>" search={$author.affiliation.es} post="</div>"}
                {/foreach}
            </div>

            <hr>

            <div class="frontpage-abstract-section">
                {call name="getMetadata" pre="<span class='frontpage-abstract-title'>" search={$translations.es.abstract} post="</span>"}
                {call name="getMetadata" pre="<span class='frontpage-abstract-text'>" search={$abstract_texts.es} post="</span>"}
            </div>

            <div class="frontpage-keywords-container">
                {call name="getMetadata" pre="<span class='frontpage-keywords-label'>" search={$translations.es.keywords} post=" |</span>"}
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.es item=keyword name=keywords}
                        {call name="getMetadata" pre="<span class='frontpage-keyword'>" search={$keyword} post="</span>"}{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </span>
            </div>

            <div class="frontpage-abstract-section">
                {call name="getMetadata" pre="<span class='frontpage-abstract-title'>" search={$translations.en.abstract} post="</span>"}
                {call name="getMetadata" pre="<span class='frontpage-abstract-text'>" search={$abstract_texts.en} post="</span>"}
            </div>

            <div class="frontpage-keywords-container">
                {call name="getMetadata" pre="<span class='frontpage-keywords-label'>" search={$translations.en.keywords} post=" |</span>"}
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.en item=keyword name=keywords}
                        {call name="getMetadata" pre="<span class='frontpage-keyword'>" search={$keyword} post="</span>"}{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </span>
            </div>

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
                                preTwo="" searchTwo="{$translations.es.license_text}" postTwo='</a>'
                            }
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>