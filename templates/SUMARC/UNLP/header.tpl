{include file={$baseFunctions}}

<div style="text-align: center;">
    <div style="font-weight: bold; color: rgb(49, 132, 155); font-size: 0.8em; font-family: 'Helvetica';">
        {call name="getMetadata" search={$journal_title}} {call name="getMetadata" pre="Vol. " search={$issue_volume}} {call name="getMetadata" pre="No. " search={$issue_number}} {call name="getMetadata" pre="(" search={$issue_year} post=")"}
    </div>
    <div>
        <a style="color: rgb(49, 132, 155); text-decoration: none; font-size: 0.8em; word-break: break-all; font-family: 'Helvetica';
        " href="https://doi.org/{$doi}" target="_blank">https://doi.org/{$doi}</a>
        {call name="getLinkedMetadata" preOne="<a style='color: rgb(49, 132, 155); text-decoration: none; font-size: 1em; word-break: break-all; font-family: 'Philosopher', sans-serif;' href='https://doi.org/" preTwo="'https://doi.org/" search={$doi} post="'</a>"}
    </div>
</div>