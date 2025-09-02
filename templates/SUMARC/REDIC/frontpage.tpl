<style>
  body {
      font-family: 'Times New Roman', Times, serif;
      line-height: 1.6;
      margin: 0;
      padding: 0;
      background-color: #ffffff;
  }

  .container {
      max-width: 800px;
      margin: 40px auto;
      padding: 20px;
      box-sizing: border-box;
  }

  .journal-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
  }

  .journal-logo {
      width: 120px;
      height: auto;
  }

  .journal-info {
      font-size: 14px;
      color: #333;
  }

  .journal-info-line {
      margin: 2px 0;
  }

  .journal-info a {
      color: #007bff;
      text-decoration: none;
  }

  .article-title {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      font-size: 28px;
      font-weight: bold;
      color: #000;
      text-align: left;
      margin-top: 40px;
      margin-bottom: 10px;
  }

  .article-subtitle {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      font-size: 20px;
      font-weight: normal;
      font-style: italic;
      color: #444;
      text-align: left;
      margin-bottom: 30px;
  }

  .author-info {
      margin-bottom: 30px;
  }

  .author-name {
      font-size: 16px;
      font-weight: bold;
      color: #000;
  }

  .author-contact {
      font-size: 14px;
      color: #555;
      margin-top: 5px;
  }

  .author-affiliation {
      font-size: 14px;
      color: #555;
      margin-top: 5px;
  }

  .abstract-section {
      margin-bottom: 30px;
  }

  .abstract-title {
      font-size: 16px;
      font-weight: bold;
      color: #000;
      margin-bottom: 10px;
  }

  .abstract-text {
      font-size: 14px;
      color: #333;
      text-align: justify;
  }

  .keywords-container {
      margin-top: 20px;
      margin-bottom: 20px;
  }

  .keywords-label {
      font-weight: bold;
      margin-right: 5px;
  }

  .keywords-list {
      display: inline;
      margin: 0;
      padding: 0;
  }

  .license-info {
      font-size: 12px;
      color: #666;
      margin-top: 30px;
      text-align: center;
  }

  .page-footer {
      border-top: 1px solid #ddd;
      padding-top: 10px;
      margin-top: 50px;
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #777;
  }

  .footer-text {
      font-weight: bold;
  }

  .page-number {
      text-align: right;
  }
</style>

<div class="container">
    <header class="journal-header">
        <div class="journal-logo-container">
            <img src="{$journal_logo_url}" alt="Logo de la Revista" class="journal-logo">
        </div>
        <div class="journal-info">
            <div class="journal-info-line">{$journal_title}</div>
            <div class="journal-info-line">Vol. {$journal_data.volume} No. {$journal_data.number} ({$journal_data.year})</div>
            <div class="journal-info-line">ISSN {$journal_issn}</div>
            <div class="journal-info-line"><a href="{$journal_url}">{$journal_url}</a></div>
            <div class="journal-info-line">Recibido: {$date_submitted} - Aceptado: {$date_accepted} - Publicado: {$date_published}</div>
        </div>
    </header>

    <div class="article-body">
        <h1 class="article-title">{$article_title}</h1>
        <h2 class="article-subtitle">{$subtitles.es_ES}</h2>

        <div class="author-info">
            {foreach from=$authors item=author}
                <div class="author-name">{$author.givenName.es} {$author.familyName.es}</div>
                <div class="author-contact">{$author.email}</div>
                <div class="author-affiliation">{$author.affiliation.es}</div>
            {/foreach}
        </div>

        <div class="abstract-section">
            <div class="abstract-title">Resumen |</div>
            <div class="abstract-text">{$abstract_texts.es_ES}</div>
        </div>

        <div class="abstract-section">
            <div class="abstract-title">Abstract |</div>
            <div class="abstract-text">{$abstract_texts.en_US}</div>
        </div>

        <div class="keywords-container">
            <div class="keywords-label">Palabras clave |</div>
            <div class="keywords-list">{$keywords_texts.es_ES|@join:", "}</div>
        </div>
        
        <div class="keywords-container">
            <div class="keywords-label">Keywords |</div>
            <div class="keywords-list">{$keywords_texts.en_US|@join:", "}</div>
        </div>

        <div class="license-info">
            <img src="{$license_url}" alt="Licencia Creative Commons">
            <div class="license-text">Licencia: <a href="{$license_url}">{$license_url}</a></div>
        </div>
    </div>
</div>

<pagebreak/>