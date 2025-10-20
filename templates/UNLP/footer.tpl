{include file='./baseFunctions.tpl'}

<footer style="width: 100%; font-size: 9pt;">
    <hr>
    <table width="100%">
        <tr>
            <td style="color: rgb(49, 132, 155); text-align: left; font-family: 'Arial', sans-serif;">
                {call name="getMetadata" search={$section_title|upper}}
            </td>
            <td style="color: black; text-align: right; font-family: 'Arial', sans-serif;">
                <pagenumber />
            </td>
        </tr>
    </table>
</footer>