{{-- Domande frequenti: solo fatti già stabiliti (orari videocall, regioni, flusso di approvazione). --}}
<section class="ef-section ef-soft" id="domande" aria-labelledby="faq-title">
    <div class="ef-wrap ef-narrow-lg">
        <div class="ef-eyebrow">Hai un dubbio?</div>
        <h2 id="faq-title">Domande frequenti</h2>
        <div class="ef-faqs">
            <details class="ef-faq"><summary>Chi può diventare dealer?</summary>
                <p>Lavoriamo con negozi di telefonia multibrand, specialisti dell'energia, CAF e agenzie immobiliari.</p></details>
            <details class="ef-faq"><summary>In quali regioni siete presenti?</summary>
                <p>{{ implode(', ', array_slice($regions, 0, -1)) }} e {{ end($regions) }}.</p></details>
            <details class="ef-faq"><summary>Come funziona la videocall?</summary>
                <p>La prenoti direttamente dal modulo di registrazione. Ci vediamo su Google Meet per circa 30 minuti, il lunedì, il mercoledì e il venerdì dalle 14 alle 19.</p></details>
            <details class="ef-faq"><summary>Cosa succede dopo la registrazione?</summary>
                <p>Dopo la videocall il nostro team valuta la richiesta. Se è approvata, ricevi via email le credenziali per l'area riservata e al primo accesso scegli la tua password.</p></details>
            <details class="ef-faq"><summary>Posso ordinare subito?</summary>
                <p>Per ordinare serve l'anagrafica fiscale completa, che inserisce il tuo agente insieme ai documenti. Finché non è completa, l'ordine non è disponibile.</p></details>
            <details class="ef-faq"><summary>Cerchi anche agenti?</summary>
                <p>Sì. Se conosci i negozi della tua zona, registrati come agente: costruiamo insieme la rete sul tuo territorio.</p></details>
        </div>
        <p style="margin-top:24px"><a class="ef-btn" href="#registrati">Registrati ora</a></p>
    </div>
</section>
