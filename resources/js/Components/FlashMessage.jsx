import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [show, setShow] = useState(false);
    const [message, setMessage] = useState('');
    const [type, setType] = useState('success');

    useEffect(() => {
        if (flash?.success) {
            setMessage(flash.success);
            setType('success');
            setShow(true);
            setTimeout(() => setShow(false), 4000);
        }
        if (flash?.error) {
            setMessage(flash.error);
            setType('error');
            setShow(true);
            setTimeout(() => setShow(false), 4000);
        }
    }, [flash]);

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center pointer-events-none">
            <div className={`pointer-events-auto max-w-md w-full mx-4 px-8 py-6 rounded-2xl shadow-2xl text-white font-medium animate-slide-in ${
                type === 'success' ? 'bg-emerald-500' : 'bg-red-500'
            }`}>
                <div className="flex flex-col items-center gap-3 text-center">
                    {type === 'success' ? (
                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                    ) : (
                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                    )}
                    <p className="text-lg">{message}</p>
                    <button onClick={() => setShow(false)} className="mt-1 px-5 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 text-sm transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
}
