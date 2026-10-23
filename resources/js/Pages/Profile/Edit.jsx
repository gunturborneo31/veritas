import Icon from '@/Components/Icon';
import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

const cardClass = 'rounded-3xl bg-surface-container-lowest p-space-lg shadow-card';

/**
 * Profil pelanggan dengan kerangka publik yang sama (header + navigasi bawah) agar terasa satu aplikasi.
 */
export default function Edit({ mustVerifyEmail, status }) {
    const { user } = usePage().props.auth;

    return (
        <PublicLayout title="Profil">
            <div className="flex flex-col gap-space-md px-margin pb-space-xl pt-space-lg">
                <section className="flex items-center gap-space-md">
                    <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary font-display text-headline-md text-on-primary shadow-md">
                        {user.name.charAt(0).toUpperCase()}
                    </span>
                    <div className="min-w-0 flex-1">
                        <h1 className="truncate text-headline-lg-mobile text-on-surface">{user.name}</h1>
                        <p className="truncate text-body-sm text-on-surface-variant">{user.email}</p>
                    </div>
                </section>

                <div className="grid grid-cols-2 gap-space-sm">
                    <Link
                        href={route('dashboard')}
                        className="flex min-h-12 items-center justify-center gap-1.5 rounded-2xl bg-surface-container-lowest px-3 text-label-md text-on-surface shadow-sm active:scale-95"
                    >
                        <Icon name="mark_email_read" size={18} className="text-primary" />
                        Kartu Saya
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="flex min-h-12 items-center justify-center gap-1.5 rounded-2xl bg-surface-container-lowest px-3 text-label-md text-on-surface shadow-sm active:scale-95"
                    >
                        <Icon name="logout" size={18} className="text-primary" />
                        Keluar
                    </Link>
                </div>

                <div className={cardClass}>
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                </div>

                <div className={cardClass}>
                    <UpdatePasswordForm />
                </div>

                <div className={cardClass}>
                    <DeleteUserForm />
                </div>
            </div>
        </PublicLayout>
    );
}
