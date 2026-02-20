<?php
if (!isset($activePage)) $activePage = '';
?>
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

<aside id="sidebar" class="w-64 shrink-0 h-screen sticky top-0 bg-dark-sidebar border-r border-white/5 flex flex-col z-30 transition-all duration-300 ease-in-out lg:translate-x-0 lg:relative lg:flex fixed">
    <div class="h-16 flex items-center px-4 border-b border-white/5">
        <div class="flex items-center gap-2">
            <img src="images/logo.jpg" alt="TeleCare AI Logo" class="h-9 w-auto object-contain rounded-lg sidebar-logo">
            <span class="font-bold text-sm sidebar-label">Tele<span class="text-primary">Care</span> <span class="text-white/30 text-xs">AI</span></span>
        </div>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <?php
        $navItems = [
            ['label' => 'Dashboard',     'href' => 'dashboard.php',     'key' => 'dashboard',     'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'Appointments',  'href' => 'appointments.php',  'key' => 'appointments',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['label' => 'Consultations', 'href' => 'consultations.php',  'key' => 'consultations', 'icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
            ['label' => 'Documents',     'href' => '#',                 'key' => 'documents',     'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['label' => 'Meetings',      'href' => '#',                 'key' => 'meetings',      'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ];
        if ($userRole === 'admin') {
            $navItems[] = ['label' => 'Users', 'href' => '#', 'key' => 'users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
        }
        $navItems[] = ['label' => 'Settings', 'href' => '#', 'key' => 'settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
        foreach ($navItems as $item):
            $isActive = ($activePage === $item['key']);
        ?>
        <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all <?= $isActive ? 'bg-primary/10 text-primary font-medium' : 'text-white/50 hover:text-white hover:bg-white/5' ?>" title="<?= $item['label'] ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
            </svg>
            <span class="sidebar-label"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-white/5">
        <div class="flex items-center gap-3 mb-3 overflow-hidden">
            <div class="w-9 h-9 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center text-sm font-bold shrink-0">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div class="min-w-0 sidebar-label">
                <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($userName) ?></p>
                <p class="text-xs text-white/40 capitalize"><?= $userRole ?></p>
            </div>
        </div>
        <button onclick="openLogoutModal()" class="flex items-center gap-2 text-xs text-white/40 hover:text-red-400 transition-colors px-1" title="Sign Out">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="sidebar-label">Sign Out</span>
        </button>
    </div>
</aside>