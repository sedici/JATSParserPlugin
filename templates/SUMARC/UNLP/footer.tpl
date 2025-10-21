{include file={$baseFunctions}}

<footer class="footer-container">
    <hr class="footer-hr">
    <table class="footer-table">
        <tr>
            <td class="footer-section-title">
                {call name="getMetadata" search={$section_title|upper}}
            </td>
            <td class="footer-page-number">
                <pagenumber />
            </td>
        </tr>
    </table>
</footer>