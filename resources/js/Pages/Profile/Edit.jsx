import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import FlashMessage from '@/Components/FlashMessage';
import { useEffect, useMemo, useRef, useState } from 'react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';

export default function Edit() {
    const { auth, flash } = usePage().props;
    const user = auth.user;
    const [previewUrl, setPreviewUrl] = useState(user?.photo_url || null);
    const [photoError, setPhotoError] = useState('');
    const [menuOpen, setMenuOpen] = useState(false);
    const fileInputRef = useRef(null);
    const [cropSource, setCropSource] = useState(null);
    const [cropZoom, setCropZoom] = useState(1);
    const [cropX, setCropX] = useState(0);
    const [cropY, setCropY] = useState(0);

    const profileForm = useForm({
        photo: null,
        remove_photo: false,
    });

    useEffect(() => {
        setPreviewUrl(user?.photo_url || null);
    }, [user?.photo_url]);

    const initial = useMemo(() => user?.name?.charAt(0)?.toUpperCase() || '?', [user?.name]);

    const onSelectPhoto = (e) => {
        const file = e.target.files?.[0];
        setPhotoError('');

        if (!file) {
            profileForm.setData('photo', null);
            profileForm.setData('remove_photo', false);
            setPreviewUrl(user?.photo_url || null);
            setCropSource(null);
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            setPhotoError('Ukuran foto maksimal 2MB.');
            e.target.value = '';
            return;
        }

        profileForm.setData('photo', file);
        profileForm.setData('remove_photo', false);
        const localUrl = URL.createObjectURL(file);
        setPreviewUrl(localUrl);
        setCropSource(localUrl);
        setCropZoom(1);
        setCropX(0);
        setCropY(0);
    };

    const createCroppedFile = async (file) => {
        if (!cropSource || !file) {
            return file;
        }

        const image = new Image();
        const loadPromise = new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = reject;
        });

        image.src = cropSource;
        await loadPromise;

        const width = image.naturalWidth;
        const height = image.naturalHeight;
        const minEdge = Math.min(width, height);
        const safeZoom = Math.max(1, Math.min(3, cropZoom));
        const sourceSize = minEdge / safeZoom;

        const centerX = width / 2 + (cropX / 100) * (width / 2);
        const centerY = height / 2 + (cropY / 100) * (height / 2);

        const sx = Math.max(0, Math.min(width - sourceSize, centerX - sourceSize / 2));
        const sy = Math.max(0, Math.min(height - sourceSize, centerY - sourceSize / 2));

        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const context = canvas.getContext('2d');
        context.drawImage(image, sx, sy, sourceSize, sourceSize, 0, 0, 512, 512);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
        if (!blob) {
            return file;
        }

        const baseName = file.name ? file.name.replace(/\.[^/.]+$/, '') : 'profile-photo';
        return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' });
    };

    const savePhoto = async () => {
        if (!profileForm.data.photo && !profileForm.data.remove_photo) {
            setPhotoError('Pilih foto dulu atau gunakan opsi hapus foto profil.');
            return;
        }

        setPhotoError('');

        let croppedPhoto = null;

        if (profileForm.data.photo) {
            croppedPhoto = await createCroppedFile(profileForm.data.photo);
        }

        profileForm.transform((data) => ({
            ...data,
            photo: croppedPhoto ?? data.photo,
        }));

        profileForm.patch(route('profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                profileForm.setData('photo', null);
                profileForm.setData('remove_photo', false);
                setMenuOpen(false);
                setCropSource(null);
                setCropZoom(1);
                setCropX(0);
                setCropY(0);

                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
            onFinish: () => {
                profileForm.transform((data) => data);
            },
        });
    };

    const openFilePicker = () => {
        setMenuOpen(false);
        setPhotoError('');
        fileInputRef.current?.click();
    };

    const removePhoto = () => {
        setMenuOpen(false);
        setPhotoError('');
        profileForm.setData('photo', null);
        profileForm.setData('remove_photo', true);
        setPreviewUrl(null);
        setCropSource(null);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    useEffect(() => {
        const closeMenu = () => setMenuOpen(false);
        window.addEventListener('click', closeMenu);
        return () => window.removeEventListener('click', closeMenu);
    }, []);

    const selectedActionText = profileForm.data.remove_photo
        ? 'Foto akan dihapus setelah disimpan.'
        : profileForm.data.photo
            ? 'Foto baru siap disimpan.'
            : 'Belum ada perubahan foto.';

    const onMenuToggle = (e) => {
        e.stopPropagation();
        setMenuOpen((prev) => !prev);
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />
            <FlashMessage flash={flash} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <section className="max-w-xl">
                            <h2 className="text-lg font-medium text-gray-900">Foto Profil</h2>
                            <p className="mt-1 text-sm text-gray-600">Upload lalu sesuaikan crop foto profil dari menu dashboard ini untuk mengganti ikon inisial.</p>

                            <div className="mt-6 space-y-5">
                                <div className="flex items-center gap-4">
                                    {previewUrl ? (
                                        <img src={previewUrl} alt="Preview" className="h-20 w-20 rounded-2xl object-cover ring-2 ring-slate-200" />
                                    ) : (
                                        <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-cyan-500 text-2xl font-bold text-white">
                                            {initial}
                                        </div>
                                    )}
                                    <div className="flex-1 space-y-2">
                                        <div className="relative inline-block" onClick={(e) => e.stopPropagation()}>
                                            <button
                                                type="button"
                                                onClick={onMenuToggle}
                                                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Atur Foto Profil
                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            {menuOpen && (
                                                <div className="absolute left-0 z-20 mt-2 w-52 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                                                    <button
                                                        type="button"
                                                        onClick={openFilePicker}
                                                        className="w-full rounded-md px-3 py-2 text-left text-sm text-slate-700 hover:bg-emerald-50"
                                                    >
                                                        Ganti Foto Profil
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={removePhoto}
                                                        className="w-full rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                                    >
                                                        Hapus Foto Profil
                                                    </button>
                                                </div>
                                            )}
                                        </div>

                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            onChange={onSelectPhoto}
                                            className="hidden"
                                        />

                                        <p className="text-xs text-gray-500">Format: JPG, PNG, WEBP. Maksimum 2MB.</p>
                                        <p className="text-xs text-slate-600">{selectedActionText}</p>
                                    </div>
                                </div>

                                {profileForm.data.photo && cropSource && !profileForm.data.remove_photo && (
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                        <p className="text-sm font-medium text-slate-700">Sesuaikan Crop</p>

                                        <div className="flex items-center gap-4 flex-wrap">
                                            <div
                                                className="h-40 w-40 rounded-xl border border-slate-300 bg-center bg-no-repeat bg-cover overflow-hidden"
                                                style={{
                                                    backgroundImage: `url(${cropSource})`,
                                                    backgroundSize: `${cropZoom * 100}%`,
                                                    backgroundPosition: `${50 + cropX}% ${50 + cropY}%`,
                                                }}
                                            />

                                            <div className="flex-1 min-w-[220px] space-y-2">
                                                <label className="block text-xs text-slate-600">Zoom</label>
                                                <input
                                                    type="range"
                                                    min="1"
                                                    max="3"
                                                    step="0.01"
                                                    value={cropZoom}
                                                    onChange={(e) => setCropZoom(parseFloat(e.target.value))}
                                                    className="w-full"
                                                />

                                                <label className="block text-xs text-slate-600">Posisi Horizontal</label>
                                                <input
                                                    type="range"
                                                    min="-50"
                                                    max="50"
                                                    step="1"
                                                    value={cropX}
                                                    onChange={(e) => setCropX(parseInt(e.target.value, 10))}
                                                    className="w-full"
                                                />

                                                <label className="block text-xs text-slate-600">Posisi Vertikal</label>
                                                <input
                                                    type="range"
                                                    min="-50"
                                                    max="50"
                                                    step="1"
                                                    value={cropY}
                                                    onChange={(e) => setCropY(parseInt(e.target.value, 10))}
                                                    className="w-full"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {(photoError || profileForm.errors.photo) && (
                                    <p className="text-sm text-red-600">{photoError || profileForm.errors.photo}</p>
                                )}

                                <button
                                    type="button"
                                    onClick={savePhoto}
                                    disabled={profileForm.processing}
                                    className="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    {profileForm.processing ? 'Menyimpan...' : 'Simpan Foto Profil'}
                                </button>
                            </div>
                        </section>
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
