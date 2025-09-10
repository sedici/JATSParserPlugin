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
        margin: 40px auto;
        padding: 20px;
        box-sizing: border-box;
        position: relative;
    }

    .frontpage-journal-header {
        margin-bottom: 20px;
        padding-bottom: 20px;
        position: relative;
    }

    .frontpage-journal-logos {
        position: relative;
        padding-bottom: 10px;
        display: block;
    }

    .frontpage-journal-logo {
        width: auto;
        height: 100px;
        display: block;
    }

    .frontpage-unlp-logo {
        width: auto;
        height: 50px;
    }

    .frontpage-journal-info {
        font-size: 7.5pt;
        color: #444;
        line-height: 1.4;
        text-align: left;
        vertical-align: top;
    }

    .frontpage-journal-info-line {
        margin: 2px 0;
    }

    .frontpage-journal-info a {
        color: #007bff;
        text-decoration: none;
    }

    .frontpage-article-body {
        padding-top: 0;
    }

    .frontpage-article-title {
        font-size: 28px;
        font-weight: bold;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-top: 30px;
        margin-bottom: 0px;
    }

    .frontpage-article-subtitle {
        font-size: 16px;
        font-weight: bold;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-top: 0px;
        margin-bottom: 10px;
    }

    .frontpage-article-lang-title {
        font-size: 16px;
        font-weight: normal;
        color: rgb(49, 132, 155);
        text-align: left;
        margin-bottom: 5px;
    }

    .frontpage-author-info {
        font-size: 7.5pt;
        margin-bottom: 5px;
        line-height: 1.4;
    }

    .frontpage-orcid-logo {
        height: 1em; /* Ajusta la altura del logo al tamaño del texto */
        vertical-align: middle;
        margin-right: 5px;
    }

    .frontpage-author-name {
        font-size: 7.5pt;
        font-weight: bold;
        color: rgb(49, 132, 155);
    }
    
    .frontpage-author-name-colored {
        font-size: 7.5pt;
        font-weight: bold;
        color: #555;
    }

    .frontpage-author-contact, .frontpage-author-affiliation {
        font-size: 7.5pt;
        color: #555;
        margin-top: 2px;
    }
    
    .frontpage-affiliation-colored {
        color: #000; /* Negro sólido */
    }

    .frontpage-abstract-section {
        font-size: 7.5pt;
        margin-bottom: 25px;
        display: flex;
        flex-direction: row;
        flex-wrap: wrap; /* Permite que el texto se envuelva si es necesario */
        align-items: flex-start;
        gap: 5px;
    }

    .frontpage-abstract-title {
        font-size: 7.5pt;
        font-weight: bold;
        color: rgb(49, 132, 155);
        white-space: nowrap;
    }

    .frontpage-abstract-text {
        font-size: 7.5pt;
        color: #333;
        text-align: justify;
    }

    .frontpage-keywords-container {
        margin-top: 15px;
        margin-bottom: 15px;
        font-size: 7.5pt;
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
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <div class="frontpage-journal-logos">
                            <img src="{$journal_logos_path.es}" class="frontpage-journal-logo">
                        </div>
                    </td>
                    <td style="vertical-align: middle; text-align: left;">
                        <div class="frontpage-journal-info">
                            <div class="frontpage-journal-info-line">{$journal_title}</div>
                            <div class="frontpage-journal-info-line">Vol. {$issue_volume} No. {$issue_number} ({$issue_year})</div>
                            <div class="frontpage-journal-info-line"><a href="{$doi}" class="frontpage-anchor">{$doi}</a></div>
                            <div class="frontpage-journal-info-line">ISSN {$online_issn}</div>
                            <div class="frontpage-journal-info-line"><a href="{$journal_url}" class="frontpage-anchor">{$journal_url}</a></div>
                            <div class="frontpage-journal-info-line">Recibido: {$date_submitted} - Aceptado: {$date_accepted} - Publicado: {$date_published}</div>
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
                        <img src="https://media.discordapp.net/attachments/1290089303879323690/1414985947015282698/af0ad727-1507-48a0-acbe-6a5cf8fb28e0.png?ex=68c2389a&is=68c0e71a&hm=fcb4f14d8fba220d89793ac3889e462a2c2a6361d126db780c5e7756e4cdb794&=&format=webp&quality=lossless" class="frontpage-unlp-logo">
                    </td>
                </tr>
            </table>
        </header>

        <div class="frontpage-article-body">
            <h2 class="frontpage-article-title">{$article_title}</h1>
            <h3 class="frontpage-article-subtitle">{$subtitles.es}</h2>
            <h3 class="frontpage-article-lang-title"><span>{$titles.en}</span>. <span>{$subtitles.en}</span></h2>

            <div class="frontpage-author-info">
                {foreach from=$authors item=author}
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="padding: 0; vertical-align: middle;">
                                <a class="frontpage-anchor" href="{$author.orcid}"><img src="{$orcid_logo.white_bg}" class="frontpage-orcid-logo"></a>
                            </td>
                            <td style="padding: 0; vertical-align: middle;">
                                <div class="frontpage-author-name-colored">{$author.givenName.es} {$author.familyName.es}</div>
                            </td>
                        </tr>
                    </table>
                    <div class="frontpage-author-contact"><a href="mailto:{$author.email}" class="frontpage-anchor">{$author.email}</a></div>
                    <div class="frontpage-author-affiliation frontpage-affiliation-colored">{$author.affiliation.es}</div>
                {/foreach}
            </div>

            <hr>

            <div class="frontpage-abstract-section">
                <span class="frontpage-abstract-title">{$translations.es.abstract} |</span> <span class="frontpage-abstract-text">{$abstract_texts.es}</span>
            </div>

            <div class="frontpage-abstract-section">
                <span class="frontpage-abstract-title">{$translations.en.abstract} |</span> <span class="frontpage-abstract-text">{$abstract_texts.en}</span>
            </div>

            <div class="frontpage-keywords-container">
                <span class="frontpage-keywords-label">{$translations.es.keywords} |</span>
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.es item=keyword name=keywords}
                        <span class="frontpage-keyword">{$keyword}</span>{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </span>
            </div>

            <div class="frontpage-keywords-container">
                <span class="frontpage-keywords-label">{$translations.en.keywords} |</span>
                <span class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.en item=keyword name=keywords}
                        <span class="frontpage-keyword">{$keyword}</span>{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </span>
            </div>

            <table class="frontpage-license-info" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="width: 40px; vertical-align: middle;">
                        <a href="{$license_url}" class="frontpage-anchor"><img src="{$license_logo}" class="frontpage-license-image"></a>
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div class="frontpage-license-text"><a href="{$license_url}" class="frontpage-anchor">{$translations.es.license_text}</a></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<pagebreak/>