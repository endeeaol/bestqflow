BESTQFLOW – README / SPECYFIKACJA FUNKCJONALNA I TECHNICZNA
===============================================================

Status dokumentu
----------------
Dokument opisuje docelową logikę modułu PrestaShop „bestqflow” według obecnych ustaleń projektowych.
Celem dokumentu jest weryfikacja, czy założenia modułu są spójne i czy obie strony rozumieją projekt tak samo.

1. Cel modułu
-------------
Moduł bestqflow służy do sprzedaży biletów na wydarzenia z poziomu PrestaShop, bez bezpośredniej integracji online
z API Qflow.

PrestaShop ma obsługiwać:
- wybór eventu na karcie produktu,
- dodanie biletu do koszyka,
- rozróżnienie linii koszyka po wybranym evencie,
- kontrolę limitu miejsc per event,
- zapis informacji o evencie do koszyka i zamówienia,
- później generowanie biletów, PDF, barcode/QR oraz eksport CSV.

Qflow ma służyć wyłącznie jako zewnętrzny system eventowy do:
- ręcznego importu listy gości z CSV,
- generowania własnych kodów QR po stronie Qflow,
- skanowania wejść podczas wydarzenia.

Moduł NIE MA:
- korzystać z bieżącej integracji API Qflow,
- zależeć od developer.qflowhub.io,
- opierać się na module SellTickets,
- opierać się na kombinacjach produktu jako nośniku eventu.

2. Główny model biznesowy
-------------------------
Sprzedaż odbywa się przez JEDEN produkt w PrestaShop.

Przykład:
- produkt: Tour de BestLab 2026
- id_product: 98

Nie tworzymy:
- 3 osobnych produktów dla 3 miast,
- kombinacji produktu dla miast,
- osobnych wariantów produktu w standardowej logice PrestaShop.

Zamiast tego:
- eventy są przechowywane we własnej tabeli modułu,
- klient wybiera event na froncie produktu,
- wybrany event staje się częścią linii koszyka,
- jedna linia koszyka reprezentuje produkt + konkretny event.

3. Eventy
---------
Aktualny model eventów:

- Szczecin — 2026-05-12 — limit 180
- Poznań   — 2026-05-13 — limit 180
- Warszawa — 2026-05-14 — limit 180

Event ma co najmniej pola:
- id_bestqflow_event
- id_product
- city
- event_date
- stock_limit
- is_active

W bazie mogą występować również:
- display_name
- event_time
- venue
- address
- position
- date_add
- date_upd

Na obecnym etapie użytkownik na froncie wybiera praktycznie miasto, natomiast data i limit są metadanymi eventu
utrzymywanymi przez moduł.

4. Front produktu
-----------------
Na karcie produktu biletowego ma się pojawić blok wyboru eventu.

Aktualna koncepcja UX:
- nagłówek:
  „Wybierz miasto wydarzenia”
- 3 przyciski:
  - Szczecin
  - Poznań
  - Warszawa
- przyciski wizualnie wyglądają jak warianty produktu / przyciski wyboru
- technicznie są to radio buttony z ostylowanymi labelami
- domyślnie nic nie jest zaznaczone
- przycisk „do koszyka” jest zablokowany, dopóki klient nie wybierze miasta
- po wyborze miasta ustawiany jest hidden input:
  bestqflow_event_id

Ważne:
- selector musi być osadzony wewnątrz formularza add-to-cart,
- używamy własnego hooka:
  {hook h='displayBestqflowSelector' product=$product}
- hook ma być wstawiony ręcznie w wybranym miejscu w product-add-to-cart.tpl, tylko raz

5. Dlaczego nie używamy kombinacji
----------------------------------
Kombinacje i wcześniejsza logika SellTickets zostały odrzucone, ponieważ:
- komplikowały checkout,
- powodowały konflikty z produktem wirtualnym,
- nie dawały czystej i przewidywalnej architektury,
- utrudniały późniejszy eksport do Qflow.

Event nie jest więc kombinacją.
Event jest bytem modułu i jest przechowywany we własnych tabelach.

6. Dlaczego nie wracamy do 3 osobnych produktów
-----------------------------------------------
Rozważana była opcja:
- Tour de BestLab 2026 – Szczecin
- Tour de BestLab 2026 – Poznań
- Tour de BestLab 2026 – Warszawa

Ta droga została uznana za gorszą, ponieważ:
- zaśmieca katalog produktowy,
- pogarsza UX,
- utrudnia rozbudowę o kolejne miasta i terminy,
- komplikuje raportowanie i dalsze zarządzanie wydarzeniami,
- wymusza powrót do starego, problematycznego podejścia.

Wniosek:
zostajemy przy jednym produkcie i własnym modelu eventów.

7. Produkt wirtualny
--------------------
Aktualna decyzja: produkt biletowy pozostaje produktem wirtualnym.

Powody:
- bilet na wydarzenie nie jest towarem fizycznym,
- nie powinien mieszać się z wysyłką i logistyką,
- nie powinien uruchamiać niepotrzebnej logiki przewoźników i wag,
- lepiej odpowiada modelowi: zakup uczestnictwa / rejestracji na event.

Nie planujemy zmiany tego produktu na zwykły fizyczny produkt, ponieważ nie upraszcza to logiki eventowej,
a jedynie dokłada niepotrzebne konsekwencje logistyczne.

8. Kluczowa decyzja dotycząca koszyka
-------------------------------------
Ustalono, że ten sam produkt 98 może wystąpić w koszyku kilka razy dla różnych miast.

Przykład:
- 2 bilety na Szczecin
- 3 bilety na Poznań

muszą być widoczne jako DWIE osobne linie koszyka.

To oznacza, że PrestaShop musi technicznie rozróżniać pozycje:
- produkt 98 + event Szczecin
- produkt 98 + event Poznań

Nie wystarczy więc samo zapisanie eventu do oddzielnej tabeli.
Trzeba również spowodować, aby PrestaShop nie scalał tych pozycji w jedną linię.

9. Techniczny mechanizm rozdzielania linii koszyka
--------------------------------------------------
Docelowa decyzja techniczna:

- event rozróżnia linię koszyka przez mechanizm customizacji produktu
- jednocześnie moduł przechowuje własne powiązanie biznesowe w osobnej tabeli

To oznacza model:
A) customizacja jako techniczny identyfikator linii koszyka
B) własna tabela modułu jako źródło prawdy biznesowej dla eventu

Customizacja nie jest tu używana jako klasyczne pole personalizacji dla klienta,
tylko jako mechanizm, który pozwala PrestaShop traktować:
- Tour de BestLab 2026 / Szczecin
- Tour de BestLab 2026 / Poznań

jako różne pozycje.

10. Informacja widoczna w koszyku
---------------------------------
W koszyku przy pozycji produktu biletowego ma się pojawić prosty dopisek z eventem.

Ustalony format:
- „Poznań, 2026-05-13”
- „Szczecin, 2026-05-12”
- „Warszawa, 2026-05-14”

Źródło tej etykiety:
- city + ", " + event_date
- wartości pobierane z tabeli eventów w module

Nie trzeba tu pokazywać pełnego opisu, venue, godziny ani dodatkowych informacji.
Na ten etap wystarczy sam:
- city
- date

11. Tabele modułu
-----------------
Aktualnie moduł zakłada co najmniej dwie tabele.

11.1. ps_bestqflow_event
Tabela eventów:
- id_bestqflow_event
- id_product
- display_name
- city
- event_date
- event_time
- venue
- address
- stock_limit
- is_active
- position
- date_add
- date_upd

11.2. ps_bestqflow_cart_item
Tabela powiązań koszyka z eventem:
- id_bestqflow_cart_item
- id_cart
- id_product
- id_product_attribute
- id_customization
- id_bestqflow_event
- quantity
- date_add
- date_upd

Rola tej tabeli:
- zapisać, jaki event został wybrany dla konkretnej linii koszyka,
- później umożliwić poprawne przeniesienie logiki do zamówienia, biletów i eksportu.

12. Walidacja na froncie
------------------------
Na froncie obowiązuje:
- bez wyboru miasta nie można dodać produktu do koszyka
- add-to-cart jest disabled na starcie
- po wyborze miasta przycisk się odblokowuje
- przy submit bez eventu ma pojawić się komunikat:
  „Wybierz miasto wydarzenia przed dodaniem biletu do koszyka.”

To jest warstwa UX i pierwsza walidacja.

13. Walidacja backendowa
------------------------
Oprócz JS musi istnieć twarda walidacja po PHP.

Jeżeli:
- id_product == produkt biletowy
- a bestqflow_event_id nie został poprawnie przekazany

to:
- nie wolno dodać produktu do koszyka
- ma zostać zwrócony komunikat błędu

To zabezpiecza system przed:
- ręcznym POST-em,
- błędami JS,
- pominięciem selectora.

14. Kontrola limitu 180 miejsc per event
----------------------------------------
Limit ma być kontrolowany per id_bestqflow_event, a nie per produkt.

Dla każdego eventu:
- stock_limit = maksymalna liczba miejsc
- obecnie 180

System ma blokować overbooking.

Docelowy model kontroli zajętości:
- sold_qty = liczba miejsc sprzedanych
- reserved_qty = liczba miejsc aktualnie zarezerwowanych w koszykach / procesie zakupu
- available_qty = stock_limit - reserved_qty - sold_qty

Na starcie można przyjąć praktyczny model:
- rezerwacje koszykowe liczone czasowo, np. z oknem 30 minut
- porzucone koszyki po czasie przestają blokować miejsca

Limit trzeba sprawdzać co najmniej:
- przy dodaniu do koszyka
- przy zmianie ilości w koszyku
- opcjonalnie ponownie przy finalizacji zamówienia

15. Model zakupu i biletów
--------------------------
Ustalono jednoznacznie:
- 1 sztuka produktu = 1 bilet

Przykład:
- klient kupuje 4 sztuki biletu na Poznań
- system ma później wygenerować 4 osobne bilety

Nie robimy:
- jednego biletu grupowego,
- biletu z wieloma wejściami,
- uproszczenia „1 rekord = wiele wejść”.

Docelowo każdy bilet ma mieć:
- osobny ticket_code
- osobny barcode / QR
- osobny rekord

16. Numeracja biletów
---------------------
Założenie docelowe:

ticket_code:
- czytelny numer biznesowy, np.
  TBL26-265590-01
  TBL26-265590-02

barcode:
- losowy
- unikalny
- trudniejszy do podrobienia
- używany jako wartość techniczna dla kodu QR / eksportu

17. PDF biletu
--------------
PDF będzie generowany po stronie PrestaShop, a nie po stronie Qflow.

Format:
- najlepiej A6
- 1 strona = 1 bilet
- dla wielu biletów: 1 PDF wielostronicowy

Na bilecie mają być co najmniej:
- logo sklepu lub eventu
- nazwa wydarzenia
- miasto
- data
- imię i nazwisko kupującego
- ID zamówienia
- numer biletu
- kod QR
- barcode tekstowo

18. Mail do klienta
-------------------
Po wygenerowaniu biletów moduł ma:
- wygenerować PDF
- wysłać mail z PrestaShop
- dołączyć PDF jako załącznik

Nie korzystamy tu z wysyłki biletów po stronie Qflow.

19. Eksport CSV do Qflow
------------------------
Końcowy model integracji z Qflow to eksport CSV.

W Qflow będą utworzone ręcznie 3 eventy:
- Szczecin
- Poznań
- Warszawa

Z PrestaShop będą eksportowane osobne CSV:
- CSV dla Szczecina
- CSV dla Poznania
- CSV dla Warszawy

Minimalne kolumny CSV:
- First Name
- Last Name
- Other Names
- Email
- Barcode

Mapowanie:
- First Name -> imię kupującego
- Last Name -> nazwisko kupującego
- Other Names -> numer zamówienia / numer biletu / opis pomocniczy
- Email -> email klienta
- Barcode -> nasz wygenerowany barcode

20. Raporty w adminie
---------------------
Docelowo moduł ma mieć część raportową.

20.1. Podsumowanie eventów
Per miasto / event:
- limit miejsc
- liczba sprzedanych
- liczba opłaconych
- liczba wygenerowanych biletów
- liczba wysłanych maili
- liczba błędów
- wolne miejsca

20.2. Lista biletów
Na poziomie biletu:
- ID zamówienia
- klient
- email
- miasto
- data eventu
- quantity
- ticket_code
- barcode
- status płatności
- status PDF
- status maila
- status eksportu CSV

20.3. Lista awaryjna / backup
Na wypadek problemów z internetem lub Qflow:
- nazwisko
- imię
- miasto
- numer biletu
- barcode
- ID zamówienia
- email

21. Obecny stan realizacji
--------------------------
Na dziś zostało już wykonane:
- moduł istnieje
- jest panel administracyjny
- można ustawić ID produktu biletowego
- istnieje tabela eventów
- są formularze dodawania i edycji eventów
- hook frontowy działa
- selector eventów jest renderowany na karcie produktu
- przycisk add-to-cart może być blokowany do czasu wyboru miasta
- dodana została druga tabela ps_bestqflow_cart_item
- przygotowane zostały helpery do pracy na eventach i koszyku
- rozpoczęto refactor modułu do osobnych klas

Na dziś NIE jest jeszcze gotowe:
- pełne rozdzielenie linii koszyka po id_customization
- dopisek eventu pod produktem w koszyku
- pełny zapis eventu do linii koszyka
- kontrola limitu miejsc w pełnym obiegu
- przeniesienie eventu do zamówienia
- generacja biletów
- PDF
- maile
- eksport CSV

22. Kierunek refaktoru
----------------------
Ustalono, że dalszy rozwój nie powinien już być upychany w jednym bestqflow.php.

Docelowo moduł ma być rozdzielony co najmniej na klasy:
- BestQflowEventRepository
- BestQflowCartRepository
- BestQflowAvailabilityService
- BestQflowCartService

Rola klas:
- EventRepository: pobieranie eventów i etykiet
- CartRepository: zapis i odczyt powiązań koszyka z eventami
- AvailabilityService: liczenie dostępności i limitów
- CartService: logika dodawania do koszyka, customizacji, walidacji i aktualizacji

23. Priorytety najbliższych prac
--------------------------------
Najbliższy plan prac:

Etap 1
- rozdzielić logikę na klasy
- dokończyć repository i service layer

Etap 2
- przejąć add-to-cart dla produktu biletowego
- utworzyć id_customization per event
- rozdzielać linie koszyka po wybranym mieście
- zapisać powiązanie do ps_bestqflow_cart_item

Etap 3
- pokazać w koszyku dopisek:
  „Poznań, 2026-05-13”

Etap 4
- wdrożyć walidację limitu 180 per event
- przy add-to-cart
- przy zmianie ilości w koszyku

Etap 5
- przenosić event z koszyka do zamówienia
- utworzyć rekordy biletów po opłaceniu

Etap 6
- generacja PDF
- wysyłka maili
- eksport CSV do Qflow
- raporty i lista awaryjna

24. Podsumowanie
----------------
Finalna wizja modułu bestqflow jest następująca:

- jeden produkt wirtualny w PrestaShop
- wiele eventów przechowywanych w tabeli modułu
- wybór miasta na karcie produktu
- osobne linie koszyka dla tego samego produktu, jeśli wybrano różne miasta
- limit miejsc kontrolowany per event
- późniejsze generowanie wielu biletów z jednej pozycji zamówienia
- eksport CSV do Qflow per event
- pełna logika eventowa po stronie PrestaShop
- Qflow używane tylko jako zewnętrzny system importu gości i obsługi wejść

Koniec dokumentu.
