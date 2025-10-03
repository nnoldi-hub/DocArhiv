# Checklist Validare Dosar Tehnic

Această listă ajută la pregătirea materialelor pentru consultare/validare ANR.

## 1. Format documente
- [ ] Toate PDF-urile convertite la PDF/A-2 (recomandat)
- [ ] Validare cu veraPDF sau Adobe Preflight (atașează raport)

## 2. Semnătură electronică (calificată, eIDAS)
- [ ] Strategie aleasă: PAdES (în PDF) sau CAdES (.p7s separat)
- [ ] Certificat calificat (prestator: ROSign/CertSign/Trans Sped)
- [ ] Verificare semnătură cu unelte conforme (ex: DSS)

## 3. Metadate
- [ ] Titlu, autor, dată, categorie, identificator unic per document
- [ ] Export metadate în manifest.xml (și/sau metadata.xml per document)
- [ ] Hash SHA-256 și dimensiune incluse în manifest

## 4. Structură XML
- [ ] Schema exemplu utilizată pentru manifest.xml (provizorie)
- [ ] Validare basic XML (well-formed)
- [ ] Aliniere la XSD ANR după consultare

## 5. Documentație tehnică
- [ ] Arhitectură (stack, componente, stocare)
- [ ] Securitate (autentificare, roluri, jurnalizare, hash fișiere)
- [ ] Proceduri (upload, conversie, export, semnare)
- [ ] Backup/restore, retenție, audit

## 6. Manual utilizator
- [ ] Ghid pentru upload, conversie PDF/A, export, semnare
- [ ] Capturi ecran și pași

## 7. Loguri și audit
- [ ] Loguri activitate (DB) și erori/securitate (fișiere)
- [ ] Mostre/anexe în dosar

## 8. Pachet demo
- [ ] Director export demo cu: manifest.xml, files/, signatures/ (opțional)
- [ ] 1–2 fișiere PDF/A de exemplu + hash aferent
- [ ] (Opțional) metadata.xml per document

## 9. Scrisoare/Draft email către ANR
- [ ] Draft revizuit
- [ ] Atașamente pregătite (pachet demo, dosar tehnic)

---
Note: Orice cerință suplimentară primită de la ANR va fi integrată în export și documentație.
