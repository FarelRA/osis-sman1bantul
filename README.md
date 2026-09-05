# OSIS SMA Negeri 1 Bantul

Website resmi **OSIS (Organisasi Intra Sekolah) SMA Negeri 1 Bantul** — PHP.

## Struktur

- `index.php` + `router.php` — front controller dan router
- `views/` — halaman publik
- `dash/` — panel admin (login, kelola konten)
- `src/`, `public/` — aset dan kode pendukung
- `data/`, `sessions/` — runtime (bind-mounted di produksi, tidak di-commit)

## Development

```bash
php -S localhost:8080 router.php
```

## Deployment

CI/CD: setiap push ke `main` memicu GitHub Actions yang build image linux/arm64 dan
publish ke `ghcr.io/farelra/osis-sman1bantul`. Data runtime di-mount dari host
(lihat repo [nodus-infra](https://github.com/FarelRA/nodus-infra)).
