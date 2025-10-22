{include file={$baseFunctions}}

<div class="header-container">
    <div class="header-metadata">
        {call name="getMetadata" search={$journal_title}} {call name="getMetadata" pre="Vol. " search={$issue_volume}} {call name="getMetadata" pre="No. " search={$issue_number}} {call name="getMetadata" pre="(" search={$issue_year} post=")"}
    </div>
    <div>
        <a class="header-doi-link" href="https://doi.org/{$doi}" target="_blank">https://doi.org/{$doi}</a>
        {call name="getLinkedMetadata" preOne="<a class='header-linked-metadata' href='https://doi.org/" preTwo="'https://doi.org/" search={$doi} post="'</a>"}
    </div>
</div>