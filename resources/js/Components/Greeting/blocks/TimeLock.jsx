import Icon from '@/Components/Icon';
import { CountdownDigits, useRemaining } from './Countdown';
import { SceneCard } from './shared';

/**
 * Menahan komponen berikutnya sampai waktu tertentu. Player membaca `isTimeLocked()`.
 */
export function isTimeLocked(values) {
    return Boolean(values.unlock_at) && new Date(values.unlock_at).getTime() > Date.now();
}

export default function TimeLock({ values, mode }) {
    const { remaining } = useRemaining(values.unlock_at ?? Date.now());

    if (remaining === 0 || mode !== 'recipient') {
        return (
            <SceneCard className="items-center text-center">
                <Icon name="lock_clock" size={32} className="text-tertiary" />
                <p className="text-body-sm text-on-surface-variant">
                    {mode === 'recipient' ? 'Waktunya tiba! Lanjutkan 💝' : 'Kunci waktu: penerima baru bisa melanjutkan pada waktu yang kamu atur.'}
                </p>
            </SceneCard>
        );
    }

    return (
        <SceneCard className="items-center text-center">
            <Icon name="lock_clock" size={32} className="text-tertiary" />
            <p className="font-display text-headline-sm text-on-surface">{values.waiting_message || 'Sabar ya, kejutannya belum waktunya dibuka!'}</p>
            <CountdownDigits target={values.unlock_at} />
        </SceneCard>
    );
}
