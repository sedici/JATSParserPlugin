{include file={$baseFunctions}}

<div class="header-container">
    <div class="header-metadata">
        {call name="getMetadata" search={$journal_title} post=","} {call name="getMetadata" pre="Vol. " search={$issue_volume}} {call name="getMetadata" pre="Núm. " search={$issue_number}} {call name="getMetadata" pre="(" search={$issue_year} post=")"}
    </div>
    <div>
        {call name="getLinkedMetadata" preOne="<a class='header-linked-metadata' href='https://doi.org/" preTwo="' target='_blank'>https://doi.org/" search=$doi post="</a>"}
    </div>
</div>