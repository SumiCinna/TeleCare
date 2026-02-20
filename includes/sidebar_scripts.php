<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeLogoutModal()"></div>
    <div class="relative bg-dark-card border border-white/10 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-red-500/10 rounded-2xl mx-auto mb-4">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-white text-center mb-1">Sign Out</h3>
        <p class="text-sm text-white/45 text-center mb-6">Are you sure you want to sign out of your TeleCare account?</p>
        <div class="flex gap-3">
            <button onclick="closeLogoutModal()" class="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium py-2.5 rounded-xl text-sm transition-all">
                Cancel
            </button>
            <a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 1) ?>auth/logout.php" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 rounded-xl text-sm transition-all text-center">
                Yes, Sign Out
            </a>
        </div>
    </div>
</div>

<style>
#sidebar.collapsed { width: 68px; }
#sidebar.collapsed .sidebar-label { display: none; }
#sidebar.collapsed nav a { justify-content: center; padding-left: 0; padding-right: 0; }
#sidebar.collapsed .p-4 { align-items: center; }
#sidebar.collapsed .p-4 > div:first-child { justify-content: center; }
#sidebar.collapsed .p-4 button { justify-content: center; padding-left: 0; }
@media (max-width: 1023px) {
    #sidebar { position: fixed; top: 0; left: 0; height: 100vh; transform: translateX(-100%); }
    #sidebar.mobile-open { transform: translateX(0); }
    #sidebar.collapsed { width: 256px; }
    #sidebar.collapsed .sidebar-label { display: inline; }
    #sidebar.collapsed nav a { justify-content: flex-start; padding-left: 0.75rem; padding-right: 0.75rem; }
}
</style>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const isLg    = () => window.innerWidth >= 1024;

function toggleSidebar() {
    if (isLg()) {
        sidebar.classList.toggle('collapsed');
    } else {
        const open = sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('hidden', !open);
    }
}

function openLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeLogoutModal();
        if (!isLg() && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.add('hidden');
        }
    }
});
</script>