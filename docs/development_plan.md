# Plan dezvoltare – Document Archive (Q4 2025 – Q1 2026)

Acest plan prioritizează ce lipsește și ce merită îmbunătățit, cu pași concreți și criterii de acceptare.

## 0) Stabilizare export și conversie (imediat)
- [x] Link descărcare export separat de backup (done)
- [ ] Verificare PDF/A automată (integrare veraPDF local, opțional)
- [ ] Opțiune export metadata per-document (metadata.xml) pe lângă manifest
- [ ] Log “export_generated” (company, interval, count, zip size, duration)

Criterii acceptare:
- Butonul de download din alertă funcționează și pentru arhive mari
- Opțiunea metadata per-document generează fișiere corecte
- Logurile conțin eveniment sumar pentru fiecare export

## 1) Semnătură electronică calificată (prioritar)
- [ ] Alegere model: PAdES (în PDF) vs CAdES (fișier .p7s)
- [ ] Integrare furnizor (ROSign/CertSign/Trans Sped) – API sandbox
- [ ] Verificare semnătură (DSS sau furnizor)
- [ ] UI SuperAdmin – configurare certificate / endpoint / test

Criterii acceptare:
- Semnătură atașată corect la cel puțin un PDF demo
- Validare semnătură “verde” în instrumentul furnizorului / DSS

## 2) Preview documente (UX)
- [ ] Integrare PDF.js pentru previzualizare PDF
- [ ] Viewer fallback pentru imagini și text
- [ ] Control acces (role-based) la previzualizare

Criterii acceptare:
- Buton “Preview” deschide viewer fără download
- Respectă permisiunile utilizatorului

## 3) CRUD rămase și UX
- [ ] Tags: CRUD + filtrare după tag
- [ ] Folders: (opțional) structură logică/virtuală, drag & drop
- [ ] Users (company-level): CRUD, roluri
- [ ] Recuperare parolă + Register flow (email)

Criterii acceptare:
- Liste/forme consistente în UI; validări server-side
- Email reset funcțional în mediu dev (MailHog/SMTP local)

## 4) Notificări & Email
- [ ] Setări SMTP în SuperAdmin
- [ ] Templatizare email (Twig/Blade simplu sau PHP templates)
- [ ] Trigger la acțiuni (upload, share, export, erori critice)

Criterii acceptare:
- Minim 3 scenarii email trimiși cu succes în dev

## 5) OCR & Căutare extinsă
- [ ] OCR pentru PDF scanate (Tesseract) – pipeline opțional
- [ ] Indexare full-text (MySQL FT sau Elastic/Lucene – în funcție de volum)
- [ ] Căutare în text extras

Criterii acceptare:
- Un PDF scanat devine “căutabil” după OCR

## 6) API REST (integrare)
- [ ] Endpoint-uri securizate: auth, upload, list, search, download
- [ ] Chei API / token-based auth
- [ ] Rate limiting

Criterii acceptare:
- Postman collection cu 6-8 endpoint-uri funcționale

## 7) Teste & Ops
- [ ] Teste unitare (helpers, permissions, export pipeline)
- [ ] Scripturi maintenance (backup/cleanup) – Windows compat
- [ ] Health checks + config linter

Criterii acceptare:
- Rulare test suite local; 70%+ linii critice acoperite

## 8) Documentație & UX
- [ ] Manual utilizator (upload, conversie, export, semnare)
- [ ] Ghid admin (setări, backup, logs)
- [ ] FAQ, Troubleshooting

Criterii acceptare:
- Docs disponibile în `docs/` + link din UI

---

## Milestones propuse

- M1 (2 săpt): Export + metadata.xml opțional + log eveniment; Preview PDF.js
- M2 (3–4 săpt): Integrare semnătură (sandbox) + UI configurare + validare
- M3 (3 săpt): CRUD rămase + reset/register + email notificări
- M4 (3 săpt): OCR pipeline minim + căutare text; API REST v1
- M5 (2 săpt): Teste + documentație completată

---

## Dependencies & riscuri
- Ghostscript/veraPDF/Tesseract instalare locală
- Chei/certificate de test la furnizor semnături
- Resurse pentru indexare full-text (în funcție de volum)

---

## Notițe de implementare
- Preferă handlers dedicate pentru download (export vs backup)
- Evită nested forms; folosește CSRF pe acțiuni sensibile
- Guard role-based în entrypoints public/
- Normalizează cache/log paths; validează input întotdeauna
