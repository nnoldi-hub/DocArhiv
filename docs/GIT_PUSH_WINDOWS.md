# Git – Inițializare și Push (Windows PowerShell)

Acest ghid te ajută să inițializezi repo-ul local și să faci push către GitHub.

## 1) Verifică Git
```powershell
git --version
```

## 2) Inițializează repo
```powershell
cd C:\wamp64\www\document-archive
git init
```

## 3) Configurează identitatea (o singură dată)
```powershell
git config user.name "Numele Tău"
git config user.email "emailul@tine.ro"
```

## 4) Adaugă remote (după ce creezi repo-ul gol pe GitHub)
```powershell
# Înlocuiește cu URL-ul tău
$REMOTE = "https://github.com/username/document-archive.git"
git remote add origin $REMOTE
```

## 5) Pregătește commit-ul inițial
```powershell
# Verifică .gitignore – este deja creat
git status

git add .
git commit -m "chore: initial import"
```

## 6) Push pe GitHub
```powershell
git branch -M main
git push -u origin main
```

## 7) Actualizări ulterioare
```powershell
git add -A
git commit -m "feat: descriere concisă a schimbărilor"
git push
```

Note:
- Poți folosi SSH în loc de HTTPS dacă ai chei SSH configurate.
- Pentru proiecte private, verifică permisiunile colaboratorilor.
