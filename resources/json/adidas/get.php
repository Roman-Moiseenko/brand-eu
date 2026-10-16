<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale = 1.0, maximum-scale=1.0, user-scalable=no" />
    <title>adidas</title>
    <link href="/WAFfailoverassets/style.css" rel="stylesheet"/>
</head>
<body>

<main class="content-wrapper">
    <header class="top">
        <div class="logo">adidas</div>
        <span id="ctl00_mainContentPlaceHolder_lblInvalidRequest">
<br/> Błąd: 18.8e257e68.1792079620.163ed71a </span>
        <h1 class="title">Niestety, nie możemy dać Ci tym razem dostępu do strony. </h1>
        <p>Podczas Twojego przejścia na stronę został automatycznie wykryty alert związany z bezpieczeństwem.</p>
    </header>

    <div class="columns">

        <section class="column column-1">
            <h3>Jaki może być powód?</h3>
            <p>W czasie dużego zainteresowania nowymi produktami ustanawiamy dodatkowe zabezpieczenia, aby osoby nieupoważnione nie miały dostępu do naszej strony. Robimy to, aby chronić naszych klientów i jednocześnie dać wszystkim zainteresowanym jednakowe szanse zamówienia nowych sneakerów. Najprawdopodobniej coś w Twoich ustawieniach zaalarmowało nasz system ochrony, dlatego dostęp do strony został zablokowany.</p>
        </section>

        <section class="column column-2">
            <h3>Jak można rozwiązać ten problem?</h3>
            <p>Spróbuj wejść na stronę, korzystając z innej przeglądarki lub z innego urządzenia. Możesz też spróbować odświeżyć stronę. Sprawdź również, czy nie masz włączonych żadnych skryptów blokujących (np. blokowania reklam), uniemożliwiających dostęp do strony. </p>
            <p>Jeśli to nie pomoże, problem może być związany ze szkodliwym oprogramowaniem. Jeśli korzystasz z prywatnego urządzenia, możesz uruchomić skanowanie antywirusowe, aby upewnić się, że złośliwe oprogramowanie nie będzie miało wpływu na jego funkcjonowanie. Jeśli korzystasz z urządzenia podłączonego do sieci, poproś administratora o pomoc.</p>
        </section>

    </div>

    <small class="error">Błąd HTTP 403 – Dostęp zabroniony</small>

</main>
<script type="text/javascript">
    // Extract reference code from the visible error span (must run AFTER the span exists in DOM)
    function getReferenceCode() {
        var span = document.getElementById('ctl00_mainContentPlaceHolder_lblInvalidRequest');
        if (span) {
            var text = span.textContent || span.innerText || '';
            var match = text.match(/Reference Error:\s*([\w.]+)/i);
            if (match && match[1]) {
                return match[1];
            }
        }
        return ""; // fallback if span or reference code not found
    }

    window.utag_data = {
    "country": "PL",
    "environment": "PRODUCTION",
    "is_mobile": "false",
    "reference_code": getReferenceCode(), // Dynamically extracted from the error span above
        "euci": "", // Optional
        "language": "PL",
    "logged_in": "FALSE", // Optional
        "site_name": "ADIDAS",
    "page_name": "REGULAR 403 ERROR", // Or "CPR 403 ERROR"
        "page_owner": "AKAMAI",
    "page_type": "ERROR",
    };

    (function (a, b, c, d) {
        a = '//tags.tiqcdn.com/utag/adidas/adidasglobal/prod/utag.js';
        b = document;
        c = 'script';
        d = b.createElement(c);
        d.src = a;
        d.type = 'text/java' + c;
        d.async = true;
        a = b.getElementsByTagName(c)[0];
        a.parentNode.insertBefore(d, a);
    })();
    </body>
    </html>
