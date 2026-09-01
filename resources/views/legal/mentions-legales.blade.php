@php
    $miseAJour = 'septembre 2026';
@endphp

<x-public-layout :titre="'RÉVOLUTION — Mentions légales'">
    <x-colonne class="pb-16">

        <div class="text-center mb-10">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">Informations légales</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight">Mentions légales</h1>
            <p class="mt-2 text-xs text-texte-secondaire">Dernière mise à jour : {{ $miseAJour }}</p>
        </div>

        <div class="border-l-4 border-l-or border border-filet bg-carte px-5 py-4 mb-10 text-[13px] text-texte-secondaire leading-relaxed">
            Cette page identifie l'éditeur du site conformément à la loi n° 2013-546 du 30 juillet 2013 relative aux transactions électroniques.
            Les informations entre crochets seront complétées par la gérante de RÉVOLUTION dès formalisation de sa situation
            (statut juridique, numéro RCCM le cas échéant) ; elles n'affectent pas la validité du reste de cette page.
        </div>

        <div class="space-y-10 text-[15px] leading-relaxed text-encre/90">

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">1. Éditeur du site</h2>
                <p>Le site RÉVOLUTION (ci-après « le Site »), accessible à l'adresse {{ url('/') }}, est édité par :</p>
                <ul class="mt-3 space-y-1.5 border border-filet bg-carte px-5 py-4">
                    <li><span class="text-texte-secondaire">Nom / raison sociale :</span> [À compléter — ex. « RÉVOLUTION », entreprise individuelle]</li>
                    <li><span class="text-texte-secondaire">Forme juridique :</span> [À compléter — ex. entreprise individuelle / SARL en cours d'immatriculation]</li>
                    <li><span class="text-texte-secondaire">Numéro RCCM :</span> [À compléter si immatriculée au Registre du Commerce et du Crédit Mobilier]</li>
                    <li><span class="text-texte-secondaire">Siège / adresse :</span> [À compléter — Abidjan, Côte d'Ivoire]</li>
                    <li><span class="text-texte-secondaire">Responsable de la publication :</span> Djiehi Carine (gérante)</li>
                    <li><span class="text-texte-secondaire">Email de contact :</span> {{ config('revolution.email_reception') }}</li>
                    <li><span class="text-texte-secondaire">Téléphone :</span> [À compléter]</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">2. Nature de l'activité</h2>
                <p>
                    RÉVOLUTION conçoit et commercialise des vêtements et accessoires à caractère chrétien, imprimés à Abidjan :
                    tee-shirts personnalisés avec un verset biblique choisi et rédigé par la cliente (collection MY VERSE),
                    ainsi que pulls, tote bags, chaussettes et casquettes (Autre collection). La prise de commande se fait
                    exclusivement via ce Site ; elle ne constitue pas une vente à distance avec paiement en ligne intégré :
                    le règlement et les modalités précises de livraison sont convenus directement avec la cliente après
                    réception de la demande.
                </p>
                <p class="mt-3">
                    Le Site ne collecte, ne traite ni ne conserve aucune donnée de paiement (numéro de carte bancaire,
                    identifiants Mobile Money, etc.) : ces échanges, lorsqu'ils ont lieu, se font en dehors du Site.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">3. Zone de livraison</h2>
                <p>
                    Les livraisons sont assurées dans l'agglomération d'Abidjan (Yopougon, Cocody, Koumassi, Treichville,
                    Adjamé, Marcory, Bingerville, Faya, Jules Vernes, Grand-Bassam et communes environnantes), par
                    coursier Yango ou par livreur partenaire selon le mode choisi par la cliente lors de la commande.
                    Les frais de livraison affichés sur le Site sont fixés par commune et peuvent évoluer ; le tarif
                    appliqué est celui en vigueur au moment de l'enregistrement de la commande.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">4. Articles personnalisés</h2>
                <p>
                    Les articles de la collection MY VERSE sont fabriqués à la demande, avec le texte exact saisi par
                    la cliente dans le formulaire de commande. RÉVOLUTION imprime le verset tel qu'il est écrit et ne
                    peut être tenue responsable d'une erreur d'orthographe ou de saisie commise par la cliente elle-même ;
                    celle-ci est invitée à vérifier son texte avant validation, comme rappelé sur le formulaire.
                    S'agissant d'articles confectionnés sur mesure à la demande de la cliente, ils ne peuvent, sauf
                    défaut de fabrication avéré, faire l'objet d'un droit de rétractation ou d'un échange une fois la
                    production lancée.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">5. Hébergement du Site</h2>
                <p>Le Site est hébergé par :</p>
                <ul class="mt-3 space-y-1.5 border border-filet bg-carte px-5 py-4">
                    <li><span class="text-texte-secondaire">Hébergeur :</span> Hostinger International Limited</li>
                    <li><span class="text-texte-secondaire">Siège social :</span> 61 Lordou Vironos Street, 6023 Larnaca, Chypre</li>
                    <li><span class="text-texte-secondaire">Localisation du serveur exploité pour ce Site :</span> Paris, France</li>
                    <li><span class="text-texte-secondaire">Site :</span> hostinger.fr</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">6. Propriété intellectuelle</h2>
                <p>
                    Le nom « RÉVOLUTION », le logo, la baseline « Même ta garde-robe intéresse JÉSUS ! », la charte
                    graphique, les textes, visuels et l'architecture du Site sont la propriété de RÉVOLUTION ou de ses
                    partenaires et sont protégés à ce titre. Toute reproduction ou représentation, totale ou partielle,
                    sans autorisation préalable est interdite.
                </p>
                <p class="mt-3">
                    Les textes de versets bibliques cités par les clientes relèvent du domaine public en tant que
                    textes religieux ; leur mise en forme et leur impression sur les articles RÉVOLUTION restent
                    propres à chaque commande et n'emportent aucune revendication de propriété sur le texte biblique
                    lui-même.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">7. Données personnelles</h2>
                <p>
                    Le traitement des données transmises via ce Site est détaillé dans la
                    <a href="{{ route('legal.confidentialite') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">Politique de confidentialité</a>,
                    partie intégrante des présentes mentions légales.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">8. Droit applicable et litiges</h2>
                <p>
                    Les présentes mentions légales sont soumises au droit ivoirien. Tout litige relatif à l'utilisation
                    du Site ou à une commande passée par son intermédiaire relève, à défaut de règlement amiable
                    préalable, de la compétence des juridictions d'Abidjan.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">9. Contact</h2>
                <p>
                    Pour toute question relative au Site ou à ces mentions légales, écrivez à
                    <a href="mailto:{{ config('revolution.email_reception') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">{{ config('revolution.email_reception') }}</a>.
                </p>
            </section>

        </div>

    </x-colonne>
</x-public-layout>
