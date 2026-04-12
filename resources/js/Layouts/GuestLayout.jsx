import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen">
            {/* Left panel — branding */}
            <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-[#0f2027] via-[#203a43] to-[#2c5364] flex-col items-center justify-center p-12 overflow-hidden">
                {/* Decorative circles */}
                <div className="absolute top-0 left-0 w-96 h-96 bg-emerald-500/10 rounded-full -translate-x-1/2 -translate-y-1/2" />
                <div className="absolute bottom-0 right-0 w-80 h-80 bg-cyan-500/10 rounded-full translate-x-1/3 translate-y-1/3" />
                <div className="absolute top-1/2 left-1/2 w-64 h-64 bg-emerald-400/5 rounded-full -translate-x-1/2 -translate-y-1/2" />

                <div className="relative z-10 text-center">
                    <Link href="/" className="inline-block">
                        <div className="flex items-center justify-center gap-4 mb-8">
                            <div className="flex h-24 w-24 items-center justify-center rounded-2xl bg-white shadow-2xl overflow-hidden">
                        <img 
                            src="/logo.png" 
                            alt="Logo" 
                            className="h-20 w-20 object-contain"
                        />
                    </div>
                        </div>
                    </Link>
                    <h1 className="text-4xl font-extrabold text-white leading-tight">
                        RS Kartika Husada Setu
                    </h1>
                    <p className="text-emerald-400 font-medium text-lg mt-2">Sistem Informasi Karyawan</p>
                    <p className="text-slate-400 text-sm mt-6 max-w-sm mx-auto leading-relaxed">
                        Portal absensi dan pengelolaan HR khusus untuk pegawai internal Rumah Sakit Kartika Husada Setu.
                    </p>

                    {/* Feature highlights */}
                    <div className="mt-12 space-y-4 text-left max-w-xs mx-auto">
                        {[
                            { icon: '📸', text: 'Absensi dengan Foto' },
                            { icon: '📋', text: 'Pengajuan Cuti & Lembur' },
                            { icon: '💰', text: 'Slip Gaji Otomatis' },
                        ].map((item, i) => (
                            <div key={i} className="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3 backdrop-blur-sm">
                                <span className="text-lg">{item.icon}</span>
                                <span className="text-sm text-slate-300 font-medium">{item.text}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Right panel — form */}
            <div className="flex flex-1 flex-col items-center justify-center bg-slate-50 px-6 py-12 lg:px-12">
                {/* Mobile logo */}
                <div className="lg:hidden mb-8 text-center">
                    <Link href="/" className="inline-flex flex-col items-center gap-3">
                        <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white shadow-lg overflow-hidden">
                            <img 
                            src="/logo.png" 
                            alt="Logo" 
                            className="h-16 w-16 object-contain"
                            />
                        </div>
                        <div className="text-center">
                            <p className="text-xl font-bold text-slate-800">RS Kartika Husada Setu</p>
                            <p className="text-sm text-emerald-600 font-medium">Sistem Informasi Karyawan</p>
                        </div>
                    </Link>
                </div>

                <div className="w-full max-w-md">
                    {children}
                </div>
            </div>
        </div>
    );
}
