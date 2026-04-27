{include file={$baseFunctions}}

<div class="header-container">
    <div class="header-metadata">
        {if isset($journal_title)}{call name="getMetadata" search=$journal_title post=","}{/if} {if isset($issue_volume)}{call name="getMetadata" pre="Vol. " search=$issue_volume}{/if} {if isset($issue_number)}{call name="getMetadata" pre="Núm. " search=$issue_number}{/if} {if isset($issue_year)}{call name="getMetadata" pre="(" search=$issue_year post=")"}{/if}
    </div>
    <div>
        {if isset($doi)}{call name="getLinkedMetadata" preOne="<a class='header-linked-metadata' href='https://doi.org/" preTwo="' target='_blank'>https://doi.org/" search=$doi post="</a>"}{/if}
    </div>
</div>