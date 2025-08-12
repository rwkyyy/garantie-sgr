=== Garanție SGR pentru WooCommerce ===
Contributors: rwky,robertutzu
Donate link: https://www.paypal.me/eduardvd
Tags: SGR, WooCommerce, garantie, returnare, reciclare
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 7.9
WC tested up to: 10.0
Stable tag: 2.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extensie WooCommerce pentru sistemul garanție-returnare SGR.

== Prezentare ==

Extensie WooCommerce pentru sistemul garanție-returnare SGR (ReturoSGR). Garanția SGR (Sistem de Garanție-Returnare) este o sumă de bani plătită de consumatorii finali la achiziția unor produse cu ambalaje reutilizabile sau reciclabile (precum sticle, PET-uri sau doze de aluminiu). Aceste sume pot fi recuperată la returnarea acestora în punctele de colectare.

**Caracteristici principale:**
- ✅ Adaugă automat taxa SGR la produsele dorite.
- ✅ Permite activarea/dezactivarea taxei pentru fiecare produs.
- ✅ Poți vedea centralizat toate produsele care au SGR activ.
- ✅ Afișează un mesaj informativ despre SGR pe pagina produsului.
- ✅ Include taxa SGR în coșul de cumpărături și în totalul comenzii.
- ✅ Compatibil cu toate softurile de facturare.
- ✅ Taxa SGR este netaxabilă (TVA).
- ✅ Compatibil cu sistemul HPOS (High-Performance Order Storage) din WooCommerce

== Instalare ==

1. **Instalare automată**
   - Accesează **Panoul de control WordPress** → **Module** → **Adaugă modul**.
   - Caută **"Garanție SGR pentru WooCommerce"**.
   - Apasă pe **Instalează acum** și apoi **Activează**.

2. **Instalare manuală**
   - Descarcă arhiva `.zip` a pluginului.
   - Accesează **Panoul de control WordPress** → **Module** → **Adaugă modul** → **Încarcă modul**.
   - Selectează fișierul `.zip` descărcat și apasă **Instalează acum**.
   - Activează pluginul din secțiunea **Module instalate**.

== Utilizare ==

1. **Activarea SGR pentru un produs**
   - Accesează **Produse → Editează un produs**.
   - În secțiunea **Date produs**, navighează la tab-ul **SGR**.
   - Bifează opțiunea **"Aplică taxă SGR pentru acest produs"**.
   - Salvează produsul.


== Întrebări frecvente ==

= Ce se întâmplă dacă nu activez SGR pentru un produs? =
Dacă nu activezi opțiunea, taxa SGR nu va fi adăugată la acel produs.

= Pot modifica produsul SGR (ex. titlu)? =
Da, sistemul urmărește codul de produs, atâta timp cât acesta este același poți să modifici ce dorești.

= Taxa SGR are TVA? =
Nu, este setată în mod explicit ca și nepurtătoare de TVA.

= Este compatibil cu HPOS (High-Performance Order Storage)? =
Da, pluginul este compatibil cu noul sistem WooCommerce HPOS.

= Taxa SGR apare pe factură? =
Da, taxa SGR este adăugată automat în totalul comenzii și notată separat ca și poziție în comandă, iar în teorie ar trebui să apară și pe factură, cu 0% TVA - desigur acest lucru variază în funcție de softul de facturare folosit. Spre exemplu: SmartBill are cotă unică pentru toate produsele, fiind necesare modificări suplimentare în SmartBill pentru a avea 0% TVA la taxa SGR.

= Pot vedea toate produsele care au taxa SGR activă? =
Da, în Panou WordPress → Produse → SGR poți vedea centralizat toate produsele

= Merge cu sistemul meu de facturare? =
Din versiunes 2.0 ar trebui să meargă cu orice sistem!

= Cu ce sisteme de facturare ați testat? =
Deocamdată am testat: Oblio, SmartBill și câteva sisteme custom - în toate a mers din prima, fără modificări ulterioare, cu excepție la Smartbill unde prin integrarea lor oficială se setează cotă unică pentru toate produsele fiind necesare modificări în Smartbill pentru a adjusta TVA la 0% pentru taxa SGR.

= Am șters din greșeală produsul virtual SGR, ce fac mai departe? =
Simplu, dezactivează și reactivează modulul, ar trebui ca produsul să fie creat din nou!

== Schimbări ==

= 1.0 =
- Lansarea inițială a pluginului.
- Adăugare opțiune de activare SGR per produs.
- Implementare taxă SGR în coșul de cumpărături și total comandă.
- Suport pentru WooCommerce HPOS.

= 2.0 =
- Regândită logica pentru a facilita integrarea cu mai multe sisteme de facturare (via produs digital)
- Testat multiple sisteme de facturare naționale
- Creare automată a produsului virtual
- Refăcută logica de sincronizarea cantității cu produsele SGR din coș
- Blocare aplicare discount-uri de orice fel pe poziția de SGR
- Blocare achiziție Taxă SGR din pagina de produs / listare de produse

== Licență ==

Acest plugin este licențiat sub **GPLv2 sau o versiune ulterioară**.
Detalii: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
