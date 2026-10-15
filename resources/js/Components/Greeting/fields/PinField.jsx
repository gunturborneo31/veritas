import Icon from '@/Components/Icon';
import { useRef } from 'react';

/**
 * PIN bersifat write-only: server hanya menyimpan hash dan tidak pernah mengirimnya kembali.
 */
export default function PinField({ field, value = '', onChange, isSet }) {
    const length = field.max ?? 6;
    const minimum = field.min ?? 4;
    const inputsRef = useRef([]);
    const digits = Array.from({ length }, (_, index) => value?.[index] ?? '');

    const setDigit = (index, digit) => {
        const next = [...digits];
        next[index] = digit.replace(/\D/g, '').slice(-1);
        onChange(next.join('').replace(/\s/g, ''));

        if (next[index] && index < length - 1) {
            inputsRef.current[index + 1]?.focus();
        }
    };

    return (
        <div className="flex flex-col gap-2">
            {isSet && !value && (
                <p className="flex items-center gap-1 text-label-sm text-success">
                    <Icon name="verified_user" size={14} />
                    PIN sudah diatur. Isi lagi jika ingin mengganti.
                </p>
            )}
            <div className="flex items-center gap-2">
                {digits.map((digit, index) => (
                    <input
                        key={index}
                        ref={(element) => (inputsRef.current[index] = element)}
                        type="text"
                        inputMode="numeric"
                        maxLength={1}
                        value={digit}
                        aria-label={`Digit PIN ${index + 1}`}
                        onChange={(event) => setDigit(index, event.target.value)}
                        onKeyDown={(event) => event.key === 'Backspace' && !digit && inputsRef.current[index - 1]?.focus()}
                        className={`h-14 w-11 rounded-2xl border-0 bg-surface-container-low text-center font-display text-headline-sm text-on-surface focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/50 ${
                            index >= minimum ? 'opacity-70' : ''
                        }`}
                    />
                ))}
            </div>
            <span className="text-label-sm text-on-surface-variant">
                {minimum}–{length} angka. Jangan pakai PIN yang sama dengan rekening/HP.
            </span>
        </div>
    );
}
