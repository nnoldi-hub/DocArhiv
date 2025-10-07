<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arhiva Documente - Sistem Electronic de Arhivare</title>
    <?php 
    // Include configurația pentru APP_URL
    require_once '../config/config.php';
    // Include helper pentru assets local
    require_once '../includes/functions/assets.php';
    renderBootstrapAssets();
    ?>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,144C960,149,1056,139,1152,122.7C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .feature-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            height: 100%;
            background: white;
            border-radius: 15px;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 30px;
        }
        
        .pricing-card {
            border: 2px solid #e5e7eb;
            border-radius: 20px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .pricing-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(37,99,235,0.2);
        }
        
        .pricing-card.featured {
            border-color: var(--primary-color);
            box-shadow: 0 10px 40px rgba(37,99,235,0.3);
            transform: scale(1.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-section {
            background: #f8fafc;
            padding: 60px 0;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        footer {
            background: #1f2937;
            color: white;
            padding: 40px 0 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-archive-fill text-primary"></i> Arhiva Documente
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Funcționalități</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Prețuri</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-outline-primary ms-2 px-3" href="login.php">Autentificare</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">Arhivează-ți Documentele în Cloud</h1>
                    <p class="lead mb-4">Sistem profesional de arhivare electronică pentru firme. Organizează, caută și gestionează documentele companiei tale într-un singur loc, sigur și accesibil.</p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-light btn-lg px-4">
                            <i class="bi bi-rocket-takeoff me-2"></i>Începe Gratuit
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-4">
                            Vezi Demo
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="opacity-75">
                            <i class="bi bi-check-circle me-1"></i> Fără card necesar
                            <i class="bi bi-check-circle ms-3 me-1"></i> 14 zile trial gratuit
                        </small>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 600'%3E%3Crect fill='%23fff' opacity='0.1' width='800' height='600' rx='20'/%3E%3Crect fill='%23fff' opacity='0.2' x='50' y='50' width='700' height='500' rx='15'/%3E%3Crect fill='%23fff' opacity='0.3' x='100' y='100' width='600' height='150' rx='10'/%3E%3Crect fill='%23fff' opacity='0.3' x='100' y='270' width='280' height='250' rx='10'/%3E%3Crect fill='%23fff' opacity='0.3' x='420' y='270' width='280' height='250' rx='10'/%3E%3C/svg%3E" alt="Dashboard Preview" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-number">500+</div>
                    <p class="text-muted">Companii Active</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-number">50K+</div>
                    <p class="text-muted">Documente Arhivate</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-number">99.9%</div>
                    <p class="text-muted">Uptime Garantat</p>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="stat-number">24/7</div>
                    <p class="text-muted">Support Tehnic</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Funcționalități Avansate</h2>
                <p class="lead text-muted">Tot ce ai nevoie pentru o arhivă digitală completă</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h4 class="text-center mb-3">Multi-Tenant</h4>
                        <p class="text-muted text-center">Fiecare firmă își gestionează propriile documente în mod complet izolat și securizat.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h4 class="text-center mb-3">Departamente</h4>
                        <p class="text-muted text-center">Organizează documentele pe departamente: HR, Financiar, Legal, Vânzări și multe altele.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-folder"></i>
                        </div>
                        <h4 class="text-center mb-3">Dosare Inteligente</h4>
                        <p class="text-muted text-center">Contracte furnizori, clienți, documente angajați - totul organizat ierarhic.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-tags"></i>
                        </div>
                        <h4 class="text-center mb-3">Sistem de Taguri</h4>
                        <p class="text-muted text-center">Etichetează și găsește rapid documentele folosind taguri personalizate.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4 class="text-center mb-3">Căutare Avansată</h4>
                        <p class="text-muted text-center">Full-text search în documente PDF, Word, Excel. Găsește orice informație instant.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="text-center mb-3">Securitate</h4>
                        <p class="text-muted text-center">Permisiuni granulare, criptare, backup automat și audit trail complet.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4 class="text-center mb-3">Gestionare Utilizatori</h4>
                        <p class="text-muted text-center">Roluri și permisiuni: Admin, Manager, User cu drepturi personalizabile.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h4 class="text-center mb-3">Versiuni</h4>
                        <p class="text-muted text-center">Ține evidența tuturor versiunilor unui document și restaurează versiuni anterioare.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="feature-icon">
                            <i class="bi bi-printer"></i>
                        </div>
                        <h4 class="text-center mb-3">Print & Download</h4>
                        <p class="text-muted text-center">Tipărește sau descarcă documentele cu un singur click, cu tracking complet.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Planuri de Abonament</h2>
                <p class="lead text-muted">Alege planul potrivit pentru afacerea ta</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card pricing-card p-4">
                        <div class="card-body">
                            <h3 class="text-center mb-3">Starter</h3>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">99 RON</span>
                                <span class="text-muted">/lună</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>5 utilizatori</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>10 GB stocare</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>3 departamente</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Taguri nelimitate</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Support email</li>
                                <li class="mb-3 text-muted"><i class="bi bi-x-circle me-2"></i>API acces</li>
                            </ul>
                            <button class="btn btn-outline-primary w-100 mt-3">Alege Starter</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card pricing-card featured p-4">
                        <div class="badge bg-primary position-absolute top-0 start-50 translate-middle">Recomandat</div>
                        <div class="card-body">
                            <h3 class="text-center mb-3">Business</h3>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">249 RON</span>
                                <span class="text-muted">/lună</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>20 utilizatori</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>50 GB stocare</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Departamente nelimitate</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Taguri nelimitate</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Support prioritar</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>API acces</li>
                            </ul>
                            <button class="btn btn-primary w-100 mt-3">Alege Business</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card pricing-card p-4">
                        <div class="card-body">
                            <h3 class="text-center mb-3">Enterprise</h3>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">Custom</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Utilizatori nelimitați</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Stocare personalizată</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Tot nelimitat</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>OCR pentru scanări</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Support 24/7</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Integrări custom</li>
                            </ul>
                            <button class="btn btn-outline-primary w-100 mt-3">Contactează-ne</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Gata să Începi?</h2>
            <p class="lead mb-4">Încearcă gratuit 14 zile, fără card necesar</p>
            <a href="register.php" class="btn btn-light btn-lg px-5">
                <i class="bi bi-rocket-takeoff me-2"></i>Creează Cont Gratuit
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3"><i class="bi bi-archive-fill"></i> Arhiva Documente</h5>
                    <p class="text-light">Sistem profesional de arhivare electronică pentru firme moderne.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Link-uri Utile</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Despre Noi</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Termeni și Condiții</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Politică Confidențialitate</a></li>
                        <li><a href="#" class="text-light text-decoration-none">GDPR</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Contact</h5>
                    <p class="text-light">
                        <i class="bi bi-envelope me-2"></i>contact@arhiva.ro<br>
                        <i class="bi bi-phone me-2"></i>+40 721 234 567<br>
                        <i class="bi bi-geo-alt me-2"></i>București, România
                    </p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center text-light">
                <p>&copy; 2025 Arhiva Documente. Toate drepturile rezervate.</p>
            </div>
        </div>
    </footer>

    <?php renderBootstrapJS(); ?>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 15px rgba(0,0,0,0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            }
        });
    </script>
</body>
</html>