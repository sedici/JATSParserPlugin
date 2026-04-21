{function name=isMetadataSet display='' search=''}
    {if isset($search) && $search}
        {$display nofilter}
    {/if}
{/function}

{function name=getMetadata pre='' search='' post=''}
    {if isset($search) && $search}
        {$pre nofilter}{$search}{$post nofilter}
    {/if}
{/function}

{function name=getLinkedMetadata preOne='' preTwo='' search='' post=''}
    {if isset($search) && $search}
        {$preOne nofilter}{$search}
        {$preTwo nofilter}{$search}{$post nofilter}
    {/if}
{/function}

{function name="getDoubleMetadata" preOne='' searchOne='' postOne='' preTwo='' searchTwo='' postTwo=''}
    {if isset($searchOne) && $searchOne && isset($searchTwo) && $searchTwo}
        {$preOne nofilter}{$searchOne}{$postOne nofilter}
        {$preTwo nofilter}{$searchTwo}{$postTwo nofilter}
    {/if}
{/function}

{function name="getImage" pre='' search='' post=''}
    {if !isset($search) || !$search}
        {assign var="imgSrc" value=$images.not_found}
    {else}
        {assign var="imgSrc" value=$search}
    {/if}
    {$pre nofilter}{$imgSrc}{$post nofilter}
{/function}