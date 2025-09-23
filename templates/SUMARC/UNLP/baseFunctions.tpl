{function name=getMetadata pre='' search='' post=''}
    {if isset($search) && $search}
        {$pre nofilter}{$search}{$post nofilter}
    {/if}
{/function}

{function name=getLinkedMetadata preOne='' preTwo='' search='' post=''}
    {if isset($search) && $search}
        {$preOne nofilter}{$search}
        {$preTwo nofilter}
        {$search}
        {$post nofilter}
    {/if}
{/function}

{function name="getDoubleMetadata" preOne='' searchOne='' postOne='' preTwo='' searchTwo='' postTwo=''}
    {if isset($searchOne) && $searchOne && isset($searchTwo) && $searchTwo}
        {$preOne nofilter}{$searchOne}{$postOne nofilter}
        {$preTwo nofilter}{$searchTwo}{$postTwo nofilter}
    {/if}
{/function}