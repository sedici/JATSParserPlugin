<style>
.frontpage-body {
    font-family: 'Times New Roman', Times, serif;
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
    border-bottom: 1px solid #000;
}

.frontpage-journal-logos {
    position: relative;
    padding-bottom: 10px;
    display: block;
}

.frontpage-journal-logo {
    width: auto;
    height: 100px;
    margin-right: 20px;
    display: block;
}

.frontpage-unlp-logo {
    height: 50px;
}

.frontpage-journal-info {
    font-size: 11px;
    color: #444;
    line-height: 1.4;
    text-align: left;
    margin-left: 20px;
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
    font-family: 'Helvetica Neue', Arial, sans-serif;
    font-size: 28px;
    font-weight: bold;
    color: #000;
    text-align: left;
    margin-top: 30px;
    margin-bottom: 5px;
}

.frontpage-article-subtitle {
    font-family: 'Helvetica Neue', Arial, sans-serif;
    font-size: 16px;
    font-weight: normal;
    font-style: italic;
    color: #444;
    text-align: left;
    margin-bottom: 5px;
}

.frontpage-author-info {
    margin-bottom: 5px;
    line-height: 1.4;
}

.frontpage-author-name {
    font-size: 14px;
    font-weight: bold;
    color: #000;
}

.frontpage-author-contact, .frontpage-author-affiliation {
    font-size: 12px;
    color: #555;
    margin-top: 2px;
}

.frontpage-abstract-section {
    margin-bottom: 25px;
}

.frontpage-abstract-title {
    font-size: 12px;
    font-weight: bold;
    color: #000;
    margin-bottom: 8px;
}

.frontpage-abstract-text {
    font-size: 12px;
    color: #333;
    text-align: justify;
}

.frontpage-keywords-container {
    margin-top: 15px;
    margin-bottom: 15px;
    font-size: 12px;
    line-height: 1.5;
}

.frontpage-keywords-label {
    font-weight: bold;
    margin-right: 5px;
    display: inline;
}

.frontpage-keywords-list {
    display: inline;
    margin: 0;
    padding: 0;
}

.frontpage-license-info {
    font-size: 10px;
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
    font-size: 12px;
    color: #777;
}

.frontpage-footer-text {
    font-weight: normal;
}

.frontpage-page-number {
    text-align: right;
}

</style>

<div class="frontpage-body">
    <div class="frontpage-container">
        <header class="frontpage-journal-header">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                        <div class="frontpage-journal-logos">
                            <img src="https://cdn.discordapp.com/attachments/1290089303879323690/1414977405789147299/image.png?ex=68c187e6&is=68c03666&hm=f402d6fea42b83e0c090440a64072a52677790323b0108d5e640fb13aaebfef0&" class="frontpage-journal-logo">
                            {* <img src="{$journal_logo_url}" class="frontpage-journal-logo"> *}
                        </div>
                    </td>
                    <td style="vertical-align: top; text-align: left;">
                        <div class="frontpage-journal-info">
                            <div class="frontpage-journal-info-line">{$journal_title}</div>
                            <div class="frontpage-journal-info-line">Vol. {$issue_volume} No. {$issue_number} ({$issue_year})</div>
                            <div class="frontpage-journal-info-line">ISSN {$journal_issn}</div>
                            <div class="frontpage-journal-info-line"><a href="{$journal_url}">{$journal_url}</a></div>
                            <div class="frontpage-journal-info-line">Recibido: {$date_submitted} - Aceptado: {$date_accepted} - Publicado: {$date_published}</div>
                        </div>
                    </td>
                </tr>
            </table>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: -30px; position: relative;">
                <tr>
                    <td style="width: 80%;">
                        </td>
                    <td style="width: 20%; text-align: right;">
                        <img src="https://cdn.discordapp.com/attachments/1290089303879323690/1414985947015282698/af0ad727-1507-48a0-acbe-6a5cf8fb28e0.png?ex=68c18fda&is=68c03e5a&hm=c9c73998d2ea9a8828ba494d8b8967310fa7c3b88757a7e97aab8f95b66f5e23&" class="frontpage-unlp-logo">
                    </td>
                </tr>
            </table>
        </header>

        <div class="frontpage-article-body">
            <h1 class="frontpage-article-title">{$article_title}</h1>
            <h2 class="frontpage-article-subtitle">{$subtitles.es}</h2>

            <div class="frontpage-author-info">
                {foreach from=$authors item=author}
                    <div class="frontpage-author-name">{$author.givenName.es} {$author.familyName.es}</div>
                    <div class="frontpage-author-contact">{$author.email}</div>
                    <div class="frontpage-author-affiliation">{$author.affiliation.es}</div>
                {/foreach}
            </div>

            <div class="frontpage-abstract-section">
                <div class="frontpage-abstract-title">Resumen |</div>
                <div class="frontpage-abstract-text">{$abstract_texts.es}</div>
            </div>

            <div class="frontpage-abstract-section">
                <div class="frontpage-abstract-title">Abstract |</div>
                <div class="frontpage-abstract-text">{$abstract_texts.en}</div>
            </div>

            <div class="frontpage-keywords-container">
                <div class="frontpage-keywords-label">Palabras clave |</div>
                <div class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.es item=keyword name=keywords}
                        <span class="frontpage-keyword">{$keyword}</span>{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </div>
            </div>

            <div class="frontpage-keywords-container">
                <div class="frontpage-keywords-label">Keywords |</div>
                <div class="frontpage-keywords-list">
                    {foreach from=$keywords_texts.en item=keyword name=keywords}
                        <span class="frontpage-keyword">{$keyword}</span>{if !$smarty.foreach.keywords.last}, {/if}
                    {/foreach}
                </div>
            </div>

            <table class="frontpage-license-info" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="width: 40px; vertical-align: middle;">
                        <img src="https://cdn.discordapp.com/attachments/1290089303879323690/1414974631974146158/image.png?ex=68c18551&is=68c033d1&hm=5271418c4a6611e014ddf55638377e4c21c3d01d880addab3d5551abb91ef73b&" class="frontpage-license-image">
                        {* <img src="{$license_url}" class="frontpage-license-image"> *}
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div class="frontpage-license-text">Esta obra está bajo una Licencia <a href="{$license_url}">{$license_url}</a></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<pagebreak/>