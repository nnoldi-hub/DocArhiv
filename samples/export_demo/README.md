# Export Demo – Structură

Acest director conține exemple pentru consultare și testare.

## Structură propusă

- `manifest_example.xml` – manifest central cu metadate, hash, referințe semnături
- `metadata_example.xml` – exemplu alternativ de metadate per-document
- `files/` – PDF/A generate (nu sunt incluse aici)
- `signatures/` – semnături CAdES `.p7s` (opțional)

## Cum generezi un pachet din aplicație
1. SuperAdmin → Sistem → Export Arhivistic
2. Selectezi compania și intervalul (sau „Toate”)
3. Bifezi „Include manifest XML”; opțional „Conversie PDF/A”
4. Generezi pachetul și descarci arhiva ZIP

## Verificări recomandate
- Validare PDF/A: veraPDF / Adobe Preflight
- Hash: `Get-FileHash -Algorithm SHA256 path\to\file.pdf`
- Semnătură: DSS / instrumentul furnizorului certificatului calificat
