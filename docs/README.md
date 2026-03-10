
# bestqflow 0.2.0

Wersja offline-to-Qflow:
- 1 produkt wirtualny biletowy
- wybór miasta / eventu na karcie produktu
- limity biletów per miasto
- maksymalna liczba biletów na zamówienie
- minimalna wartość koszyka bez biletów
- generowanie PDF A6 po statusie opłaconym
- wysyłka maila z załącznikiem PDF
- eksport CSV per miasto do importu w Qflow
- lista awaryjna CSV i raport w panelu modułu

Uwagi:
- moduł korzysta z PDFGenerator/TCPDF dostępnego w PrestaShop
- CSV eksportuje kolumny: First Name, Last Name, Other Names, Email, Barcode
- 1 sztuka produktu = 1 rekord biletu = 1 barcode
