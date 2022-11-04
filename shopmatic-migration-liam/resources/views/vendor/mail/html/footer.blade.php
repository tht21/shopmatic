<table align="center">
    <tr>
        <td>
            <a href="https://www.instagram.com/combinesell/">
                <img src="{{ url('/images/icons/instagram-brands.png') }}" class="fa" alt="CombineSell Instagram">
            </a>
        </td>
        <td>
            <a href="https://www.facebook.com/CombineSell">
                <img src="{{ url('/images/icons/facebook-brands.png') }}" class="fa" alt="CombineSell Facebook">
            </a>
        </td>
        <td>
            <a href="https://twitter.com/combinesell">
                <img src="{{ url('/images/icons/twitter-square-brands.png') }}" class="fa" alt="CombineSell Twitter">
            </a>
        </td>
        <td>
            <a href="https://www.linkedin.com/company/combinesell">
                <img src="{{ url('/images/icons/linkedin-brands.png') }}" class="fa" alt="CombineSell LinkedIn">
            </a>
        </td>
    </tr>
</table>
<tr>
    <td>
        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content-cell" align="center">
                    {{ Illuminate\Mail\Markdown::parse($slot) }}
                </td>
            </tr>
        </table>
    </td>
</tr>
