import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Selamat Datang" />
            <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-900 selection:bg-emerald-500 selection:text-white">
                <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=2653&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat opacity-20"></div>
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent"></div>

                <div className="relative z-10 w-full max-w-5xl px-6 lg:px-8 flex flex-col min-h-screen">
                    <nav className="flex items-center justify-between pb-12 pt-6">
                        <div className="flex items-center gap-3">
                            <div className="flex items-center justify-center rounded-xl bg-gradient-to-br">
                            <img 
                            src="/logo.png" 
                            alt="Logo" 
                            className="h-14 w-14 sm:h-20 sm:w-20 object-cover"
                            />
                            </div>
                            <span className="text-base sm:text-xl font-bold text-white tracking-wide">
                                RSKHS <span className="font-light text-orange-400">Attendance</span>
                            </span>
                        </div>

                        <div>
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="rounded-full bg-white/10 px-6 py-2.5 text-sm font-semibold text-white backdrop-blur-md transition hover:bg-white/20 hover:text-white"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <div className="flex items-center gap-4">
                                    <Link
                                        href={route('login')}
                                        className="rounded-full bg-emerald-500 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-600 shadow-lg shadow-emerald-500/30"
                                    >
                                        Log in
                                    </Link>
                                </div>
                            )}
                        </div>
                    </nav>

                    <main className="flex flex-col items-center justify-center flex-1 py-12 lg:py-20">
                        <div className="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 mb-8 text-sm font-medium text-emerald-300 backdrop-blur-sm">
                            <span className="relative flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Sistem Absensi Pegawai Terintegrasi
                        </div>

                        <h1 className="text-center text-4xl sm:text-5xl font-extrabold tracking-tight text-white lg:text-7xl">
                            Kelola Kehadiran <br className="hidden lg:block" />
                            <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">Lebih Efisien</span>
                        </h1>

                        <p className="mt-8 max-w-2xl text-center text-lg leading-relaxed text-slate-300">
                            Portal absensi dan pengelolaan HR khusus untuk pegawai internal RS Kartika Husada Setu. Lacak kehadiran, ajukan cuti, dan manajemen absensi dengan mudah.
                        </p>

                        <div className="mt-10 flex flex-col gap-4 sm:flex-row items-center justify-center">
                            <Link
                                href={auth.user ? route('dashboard') : route('login')}
                                className="group flex items-center gap-2 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 px-8 py-4 text-base font-semibold text-white transition-all hover:scale-105 hover:shadow-lg hover:shadow-emerald-500/40"
                            >
                                Mulai Sekarang
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 transition-transform group-hover:translate-x-1">
                                    <path fillRule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clipRule="evenodd" />
                                </svg>
                            </Link>
                        </div>
                    </main>

                    <footer className="border-t border-white/10 py-8 text-center text-sm text-slate-400">
                        &copy; {new Date().getFullYear()} Created by <span className="text-emerald-400 font-medium">Wahyu Ardiansyah</span>
                    </footer>
                </div>
            </div>
        </>
    );
}
