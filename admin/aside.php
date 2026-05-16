<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

 
       
        /* ── Layout ── */
       

        /* ── Sidebar ── */
        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 1.25rem 1.5rem;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0 1.25rem;
            margin: 0.75rem 0 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.55rem 1.25rem;
            font-size: 14px;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s, background 0.15s;
            border-radius: 0;
        }

        .sidebar-link i { font-size: 17px; }

        .sidebar-link:hover {
            color: var(--text);
            background: rgba(255,255,255,0.04);
        }

        .sidebar-link.active {
            color: var(--accent);
            background: rgba(255,77,28,0.08);
        }

        .sidebar-bottom {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

     
        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar { display: none; }
         
        }
    </style>



<aside class="sidebar">
        <a href="dashboard.php" class="sidebar-logo">
            <div class="logo-icon">⚡</div>
            <span class="logo-name">SwiftDrop</span>
        </a>

        <span class="sidebar-label">Main</span>
        <a href="dashboard.php" class="sidebar-link">
            <i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard
        </a>
        <a href="events.php" class="sidebar-link ">
            <i class="ti ti-bolt" aria-hidden="true"></i> Events
        </a>
        

        <span class="sidebar-label">Manage</span>
        <a href="users.php" class="sidebar-link">
            <i class="ti ti-users" aria-hidden="true"></i> Users
        </a>
        <a href="settings.php" class="sidebar-link">
            <i class="ti ti-settings" aria-hidden="true"></i> Settings
        </a>

        <div class="sidebar-bottom">
            <a href="/Bitcode/public/logout.php" class="sidebar-link">
        <i class="ti ti-logout" aria-hidden="true"></i> Logout
    </a>
        </div>
    </aside>