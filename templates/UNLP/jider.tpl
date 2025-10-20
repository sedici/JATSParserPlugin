{include file='./baseFunctions.tpl'}

<div style="text-align: center;">
    <div style="font-weight: bold; color: rgba(155, 49, 49, 1); font-size: 0.8em; font-family: 'Helvetica';">
        Esta es una prueba usando un header distinto en la frontpage, lo único que se debe hacer es definir el XML con el role "header", pero el resto es custom
    </div>
    <div>
        <a style="color: rgb(49, 132, 155); text-decoration: none; font-size: 0.8em; word-break: break-all; font-family: 'Helvetica';
        " href="https://doi.org/{$doi}" target="_blank">https://doi.org/{$doi}</a>
        {call name="getLinkedMetadata" preOne="<a style='color: rgb(49, 132, 155); text-decoration: none; font-size: 1em; word-break: break-all; font-family: 'Philosopher', sans-serif;' href='https://doi.org/" preTwo="'https://doi.org/" search={$doi} post="'</a>"}
    </div>
</div>