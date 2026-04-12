import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const navItems = [
    {
        label: 'Dashboard',
        route: 'dashboard',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z" />
            </svg>
        ),
    },
    {
        label: 'Absensi',
        route: 'attendance.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
    },
    {
        label: 'Pengajuan Cuti',
        route: 'leaves.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        ),
    },
    {
        label: 'Pengajuan Lembur',
        route: 'overtimes.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        ),
    },
    {
        label: 'Penggajian',
        route: 'payroll.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        ),
    },
];

const adminNavItems = [
    {
        label: 'Jadwal Karyawan',
        route: 'schedule.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m0 0v10a2 2 0 01-2 2h-2a2 2 0 01-2-2V5" />
            </svg>
        ),
    },
    {
        label: 'Kelola Karyawan',
        route: 'employees.index',
        icon: (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        ),
    },
];

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const isAdmin = user.role === 'admin_sdm';
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const allNavItems = isAdmin ? [...navItems, ...adminNavItems] : navItems;

    return (
        <div className="flex h-screen bg-slate-100 overflow-hidden">
            {/* Mobile overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 w-72 transform bg-gradient-to-b from-[#0f2027] via-[#203a43] to-[#2c5364] transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                    }`}
            >
                <div className="flex h-full flex-col">
                    {/* Logo */}
                    <div className="flex items-center gap-3 px-4 py-4 border-b border-white/10">
                        <div className="flex-shrink-0 flex h-12 w-12 items-center justify-center rounded-xl bg-white shadow-md overflow-hidden">
                            <img 
                            src="/logo.png" 
                            alt="Logo" 
                            className="h-10 w-10 object-contain"
                            />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-bold text-white leading-tight">RS Kartika Husada Setu</p>
                            <p className="text-[12px] text-emerald-400/80 font-medium mt-0.5">Sistem Informasi Karyawan</p>
                        </div>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="flex-shrink-0 ml-1 lg:hidden text-white/60 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {/* Menu label */}
                    <div className="px-6 pt-6 pb-2">
                        <p className="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-400">Menu Utama</p>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 overflow-y-auto px-4 space-y-1">
                        {allNavItems.map((item) => {
                            const isActive = route().current(item.route);
                            return (
                                <Link
                                    key={item.route}
                                    href={route(item.route)}
                                    className={`group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200 ${isActive
                                            ? 'bg-emerald-500/20 text-emerald-400 shadow-lg shadow-emerald-500/10'
                                            : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                        }`}
                                >
                                    <span className={`transition-colors ${isActive ? 'text-emerald-400' : 'text-slate-400 group-hover:text-white'}`}>
                                        {item.icon}
                                    </span>
                                    {item.label}
                                    {isActive && (
                                        <span className="ml-auto h-2 w-2 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/50" />
                                    )}
                                </Link>
                            );
                        })}
                    </nav>

                    {/* User info */}
                    <div className="border-t border-white/10 p-4">
                        <div className="flex items-center gap-3 rounded-xl bg-white/5 px-4 py-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-cyan-500 text-sm font-bold text-white">
                                {user.name?.charAt(0)?.toUpperCase()}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-white truncate">{user.name}</p>
                                <p className="text-[11px] text-slate-400 truncate">{isAdmin ? 'Admin SDM' : user.position || 'Karyawan'}</p>
                            </div>
                        </div>
                        <div className="mt-2 flex gap-2">
                            <Link
                                href={route('profile.edit')}
                                className="flex-1 text-center rounded-lg bg-white/5 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
                            >
                                Profil
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="flex-1 text-center rounded-lg bg-red-500/10 px-3 py-2 text-xs font-medium text-red-400 hover:bg-red-500/20 hover:text-red-300 transition-colors"
                            >
                                Keluar
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <header className="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:px-6 lg:py-4 shadow-sm">
                    <div className="flex items-center gap-3 min-w-0">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors flex-shrink-0"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        {typeof header === 'string' ? (
                            <h1 className="text-lg lg:text-xl font-bold text-slate-800 truncate">{header}</h1>
                        ) : (
                            header
                        )}
                    </div>
                    <div className="flex items-center gap-3 flex-shrink-0">
                        <span className="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            Online
                        </span>
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-4 pb-8 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
