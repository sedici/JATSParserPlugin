{function name=getMetadata pre='' search='' post=''}
    {if isset($search) && $search}
        {$pre nofilter}
        {$search}
        {$post nofilter}
    {/if}
{/function}

{function name=getLinkedMetadata preOne='' preTwo='' search='' post=''}
    {if isset($search) && $search}
        {$preOne nofilter}
        {$search}
        {$preTwo nofilter}
        {$search}
        {$post nofilter}
    {/if}
{/function}

<div style="
    text-align: center;
">
    <div style="
        font-weight: bold;
        color: rgb(49, 132, 155);
        font-size: 1em;
        font-family: 'Philosopher', sans-serif;
    ">
        {$journal_title} Vol. {$issue_volume} No. {$issue_number} ({$issue_year})
    </div>
    <div>
        <a style="
            color: rgb(49, 132, 155);
            text-decoration: none;
            font-size: 1em;
            word-break: break-all;
            font-family: 'Philosopher', sans-serif;
        " href="https://doi.org/{$doi}" target="_blank">https://doi.org/{$doi}</a>
    </div>
</div>