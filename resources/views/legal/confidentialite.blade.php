@php
    $miseAJour = 'septembre 2026';
@endphp

<x-public-layout :titre="'RÉVOLUTION — Politique de confidentialité'">
    <x-colonne class="pb-16">

        <div class="text-center mb-10">
            <p class="text-xs uppercase tracking-[0.18em] text-or font-semibold mb-2">Vos données</p>
            <h1 class="text-2xl sm:text-3xl font-semibold text-encre tracking-tight">Politique de confidentialité</h1>
            <p class="mt-2 text-xs text-texte-secondaire">Dernière mise à jour : {{ $miseAJour }}</p>
        </div>

        <div class="border-l-4 border-l-or border border-filet bg-carte px-5 py-4 mb-10 text-[13px] text-texte-secondaire leading-relaxed">
            Cette politique explique simplement ce que RÉVOLUTION fait de vos informations quand vous passez commande
            ou consultez ce site. Elle s'appuie sur le cadre légal ivoirien de protection des données personnelles
            (voir section 13). Les mentions entre crochets seront précisées dès finalisation du statut juridique de
            RÉVOLUTION ; le reste de cette politique décrit fidèlement le fonctionnement réel du site.
        </div>

        <div class="space-y-10 text-[15px] leading-relaxed text-encre/90">

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">1. Qui est responsable de vos données</h2>
                <p>
                    Le responsable du traitement des données collectées sur ce site est RÉVOLUTION, [À compléter —
                    forme juridique], représentée par sa gérante Djiehi Carine, joignable à
                    <a href="mailto:{{ config('revolution.email_reception') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">{{ config('revolution.email_reception') }}</a>.
                    L'identité complète de l'éditeur figure dans les
                    <a href="{{ route('legal.mentions-legales') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">mentions légales</a>.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">2. Notre activité, en bref</h2>
                <p>
                    RÉVOLUTION prend vos commandes de vêtements personnalisés (dont des tee-shirts imprimés avec un
                    verset que vous choisissez vous-même) et vous livre à Abidjan. Ce site remplace la prise de
                    commande par message WhatsApp : les informations que vous saisissez servent uniquement à
                    préparer, confirmer et livrer votre commande, et à faire fonctionner votre carte de fidélité.
                    RÉVOLUTION ne vend, ne loue ni ne partage vos données avec des tiers à des fins commerciales.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">3. Quelles données nous collectons</h2>

                <p class="mb-3">Deux situations : vous passez commande, ou vous nous écrivez.</p>

                <div class="overflow-x-auto border border-filet mb-4">
                    <table class="w-full text-[13px] sm:text-[14px]">
                        <thead>
                            <tr class="bg-creme border-b border-filet text-left">
                                <th class="px-4 py-3 font-medium">Donnée</th>
                                <th class="px-4 py-3 font-medium">Collectée quand</th>
                                <th class="px-4 py-3 font-medium">Obligatoire ?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-filet">
                            <tr>
                                <td class="px-4 py-3">Nom et prénom</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Oui</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Numéro de téléphone</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Oui</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Adresse email</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Non — sert uniquement à la newsletter, si vous le souhaitez</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Commune, quartier, point de repère</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Commune : oui · Quartier : non</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Détails de l'article (taille, couleur, type, verset choisi et texte que vous rédigez)</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Selon la collection choisie</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Mode et créneau de livraison souhaités</td>
                                <td class="px-4 py-3">À chaque commande</td>
                                <td class="px-4 py-3">Oui</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3">Historique de vos commandes, nombre de commandes, palier de fidélité</td>
                                <td class="px-4 py-3">Généré automatiquement dès votre 1ʳᵉ commande</td>
                                <td class="px-4 py-3">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-[13px] text-texte-secondaire">
                    Nous ne demandons jamais votre numéro de carte bancaire, un mot de passe, ou une pièce d'identité
                    sur ce site. Nous ne collectons pas non plus votre localisation précise (GPS) : seuls la commune
                    et le quartier que vous indiquez vous-même sont enregistrés.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">4. Pourquoi nous les utilisons</h2>
                <ul class="space-y-3">
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Traiter votre commande :</strong> nom, téléphone, article, taille et adresse de livraison sont indispensables pour préparer et vous livrer le bon article, au bon endroit.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Vous contacter :</strong> confirmer la commande, organiser la livraison, répondre à une question — par téléphone ou par email si vous en avez laissé un.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Générer et faire vivre votre carte de fidélité :</strong> compter vos commandes pour calculer votre palier et vos avantages (−15 % à −65 %).</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Vous reconnaître à votre prochaine commande :</strong> retrouver votre historique via votre numéro de téléphone, pour ne pas vous redemander ce que nous savons déjà.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Vous envoyer la newsletter :</strong> uniquement si vous avez renseigné votre email, et jusqu'à ce que vous nous demandiez d'arrêter.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-or shrink-0">—</span>
                        <span><strong class="font-medium">Suivre notre activité :</strong> la gérante consulte un tableau de bord interne (commandes du jour, clientes) pour gérer la boutique — ces données ne sortent pas de cet usage interne.</span>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">5. Sur quelle base légale</h2>
                <p>Conformément aux principes posés par la loi n° 2013-450 relative à la protection des données à caractère personnel (voir section 13), chaque traitement repose sur l'une de ces bases :</p>
                <ul class="mt-3 space-y-2 list-disc list-inside">
                    <li><strong class="font-medium">Exécution de votre commande</strong> : en remplissant le formulaire, vous nous demandez de traiter une commande — le traitement de vos données est nécessaire pour y répondre.</li>
                    <li><strong class="font-medium">Consentement</strong> : pour l'email et la newsletter, qui restent facultatifs et que vous pouvez retirer à tout moment.</li>
                    <li><strong class="font-medium">Intérêt légitime</strong> : pour la carte de fidélité et la reconnaissance client, qui font partie du service que vous attendez d'une boutique où vous revenez.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">6. Qui a accès à vos données</h2>
                <p>Vos données sont accessibles uniquement :</p>
                <ul class="mt-3 space-y-2 list-disc list-inside">
                    <li>À la gérante de RÉVOLUTION, pour traiter votre commande et gérer la boutique ;</li>
                    <li>À notre hébergeur technique, <strong class="font-medium">Hostinger International Limited</strong>, qui stocke la base de données du site (serveur situé à Paris, France) — en tant que sous-traitant technique, sans droit d'utiliser vos données pour son propre compte ;</li>
                    <li>Au service d'envoi d'e-mails utilisé pour vous confirmer votre commande (Gmail / Google), uniquement pour l'acheminement du message ;</li>
                    <li>Au livreur (Yango ou livreur partenaire) : uniquement votre nom, téléphone et adresse de livraison, le temps de la livraison.</li>
                </ul>
                <p class="mt-3">RÉVOLUTION ne vend et ne loue vos données à aucun tiers, et ne les utilise à aucune fin publicitaire externe.</p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">7. Combien de temps nous les gardons</h2>
                <p>
                    Vos données de commande et votre historique de fidélité sont conservés tant que vous restez cliente
                    de RÉVOLUTION (c'est ce qui permet à votre carte de fidélité de fonctionner d'une commande à
                    l'autre). Si vous souhaitez la suppression complète de vos données et renoncez à votre historique
                    de fidélité, écrivez-nous (voir section 10) : nous y donnerons suite, sous réserve des obligations
                    légales de conservation qui pourraient s'appliquer à certaines données commerciales.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">8. Comment nous les protégeons</h2>
                <p>
                    Le site est servi en connexion chiffrée (HTTPS). L'accès au tableau de bord de gestion des
                    commandes est protégé par un identifiant et un mot de passe réservés à la gérante, sur une adresse
                    non communiquée publiquement. Vos données sont hébergées sur un serveur dédié, distinct du code
                    source du site rendu public.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">9. Cookies et traceurs</h2>
                <p>
                    Ce site utilise uniquement des cookies techniques, strictement nécessaires à son fonctionnement
                    (maintenir votre session pendant la saisie du formulaire, protéger le formulaire contre les
                    soumissions frauduleuses). Aucun cookie publicitaire, aucun traceur de mesure d'audience
                    (Google Analytics ou équivalent) et aucun cookie de réseau social n'est déposé par RÉVOLUTION.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">10. Vos droits</h2>
                <p>Conformément à la loi n° 2013-450, vous disposez sur vos données des droits suivants :</p>
                <ul class="mt-3 space-y-2 list-disc list-inside">
                    <li><strong class="font-medium">Droit d'accès :</strong> savoir quelles données nous avons sur vous ;</li>
                    <li><strong class="font-medium">Droit de rectification :</strong> corriger une information erronée (ex. faute de frappe sur votre nom) ;</li>
                    <li><strong class="font-medium">Droit d'effacement :</strong> demander la suppression de vos données ;</li>
                    <li><strong class="font-medium">Droit d'opposition :</strong> vous opposer à un traitement, notamment retirer votre email de la newsletter à tout moment ;</li>
                    <li><strong class="font-medium">Droit à la portabilité :</strong> recevoir une copie de vos données dans un format lisible.</li>
                </ul>
                <p class="mt-3">
                    Pour exercer l'un de ces droits, écrivez à
                    <a href="mailto:{{ config('revolution.email_reception') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">{{ config('revolution.email_reception') }}</a>
                    en précisant votre nom et votre numéro de téléphone (pour vous identifier). Nous répondons dans
                    les meilleurs délais, et au plus tard dans le mois suivant votre demande.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">11. Mineurs</h2>
                <p>
                    Ce site s'adresse à un public majeur ou capable de passer commande avec l'accord d'un parent ou
                    tuteur. Si vous êtes mineur(e), veuillez passer commande avec l'accompagnement d'un adulte
                    responsable.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">12. Transferts de données</h2>
                <p>
                    Vos données sont hébergées sur un serveur situé en France (Union européenne), exploité par
                    Hostinger International Limited. L'envoi des e-mails de confirmation transite par les
                    infrastructures de Google (Gmail), susceptibles de traiter des données en dehors de la Côte
                    d'Ivoire. Ces prestataires sont choisis pour leurs garanties de sécurité reconnues ; aucune autre
                    donnée n'est transférée à l'étranger.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">13. Cadre juridique de référence</h2>
                <p>Cette politique s'appuie sur le cadre légal ivoirien applicable au numérique et aux données personnelles :</p>
                <ul class="mt-3 space-y-2 list-disc list-inside">
                    <li>Loi n° 2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel ;</li>
                    <li>Loi n° 2013-546 du 30 juillet 2013 relative aux transactions électroniques ;</li>
                    <li>Loi n° 2013-451 du 19 juin 2013 relative à la lutte contre la cybercriminalité.</li>
                </ul>
                <p class="mt-3">
                    L'autorité ivoirienne compétente en matière de protection des données personnelles est l'ARTCI
                    (Autorité de Régulation des Télécommunications/TIC de Côte d'Ivoire). Si vous estimez que vos
                    droits ne sont pas respectés après nous avoir contactés, vous pouvez lui adresser une réclamation.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">14. Modifications de cette politique</h2>
                <p>
                    Cette politique peut être mise à jour pour refléter une évolution du site ou de la réglementation.
                    La date de dernière mise à jour figure en haut de cette page. Nous vous invitons à la consulter
                    de temps à autre.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-encre mb-3">15. Contact</h2>
                <p>
                    Pour toute question sur cette politique ou sur vos données, écrivez à
                    <a href="mailto:{{ config('revolution.email_reception') }}" class="text-rouille underline underline-offset-4 decoration-filet hover:decoration-rouille">{{ config('revolution.email_reception') }}</a>.
                </p>
            </section>

        </div>

    </x-colonne>
</x-public-layout>
