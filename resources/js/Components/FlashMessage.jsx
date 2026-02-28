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
        <div className={`fixed top-4 right-4 z-[100] px-5 py-3 rounded-xl shadow-2xl text-white text-sm font-medium animate-slide-in ${
            type === 'success' ? 'bg-emerald-500' : 'bg-red-500'
        }`}>
            <div className="flex items-center gap-2">
                {type === 'success' ? (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                ) : (
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                )}
                {message}
                <button onClick={() => setShow(false)} className="ml-2 hover:opacity-70">×</button>
            </div>
        </div>
    );
}
