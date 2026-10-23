import Icon from '@/Components/Icon';
import { SceneLabel } from './shared';

/**
 * Kupon janji bergaya tiket sobek (lekukan di kedua sisi dan garis potong).
 */
export default function Coupons({ slot, values, base }) {
    const coupons = (values.coupons ?? []).filter(Boolean);

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="confirmation_number">{slot?.title ?? `Kupon Janji untuk ${base.recipient_name}`}</SceneLabel>
            {coupons.map((coupon, index) => (
                <div
                    key={index}
                    className="relative flex animate-layer-in items-stretch overflow-hidden rounded-2xl bg-surface-container-lowest shadow-[0_10px_24px_-14px_rgba(40,21,38,0.4)]"
                    style={{ animationDelay: `${index * 90}ms` }}
                >
                    <div className="flex w-16 shrink-0 flex-col items-center justify-center gap-0.5 bg-primary text-on-primary">
                        <Icon name="local_activity" size={22} />
                        <span className="text-[11px] font-bold tracking-wider">#{index + 1}</span>
                    </div>
                    <div className="relative flex-1 border-l-2 border-dashed border-primary/30 px-4 py-3">
                        <p className="text-label-sm uppercase tracking-wider text-primary">Berlaku selamanya</p>
                        <p className="text-body-md text-on-surface">{coupon}</p>
                    </div>
                    <span className="absolute -top-2 left-[3.6rem] h-4 w-4 rounded-full" style={{ backgroundColor: 'rgb(var(--color-primary-fixed))' }} />
                    <span className="absolute -bottom-2 left-[3.6rem] h-4 w-4 rounded-full" style={{ backgroundColor: 'rgb(var(--color-primary-fixed))' }} />
                </div>
            ))}
            <p className="text-center text-label-sm text-on-surface-variant">Tunjukkan layar ini untuk menukar kupon 😉</p>
        </div>
    );
}
