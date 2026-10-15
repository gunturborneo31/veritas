import Icon from '@/Components/Icon';
import { useEffect, useRef, useState } from 'react';
import { Confetti } from './shared';

const BLOW_THRESHOLD = 0.32;

/**
 * Tiup lilin lewat mikrofon. Jika izin mikrofon ditolak, penerima bisa mengetuk tombol.
 */
export default function BlowCandle({ values, base }) {
    const [isBlown, setIsBlown] = useState(false);
    const [isListening, setIsListening] = useState(false);
    const cleanupRef = useRef(() => {});

    useEffect(() => () => cleanupRef.current(), []);

    const blowOut = () => {
        cleanupRef.current();
        setIsListening(false);
        setIsBlown(true);
        navigator.vibrate?.([30, 40, 60]);
    };

    const listen = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const context = new AudioContext();
            const analyser = context.createAnalyser();
            analyser.fftSize = 512;
            context.createMediaStreamSource(stream).connect(analyser);
            const samples = new Uint8Array(analyser.fftSize);
            let frame;

            cleanupRef.current = () => {
                cancelAnimationFrame(frame);
                stream.getTracks().forEach((track) => track.stop());
                context.close();
            };

            const detect = () => {
                analyser.getByteTimeDomainData(samples);
                const rms = Math.sqrt(samples.reduce((sum, sample) => sum + ((sample - 128) / 128) ** 2, 0) / samples.length);

                if (rms > BLOW_THRESHOLD) {
                    blowOut();

                    return;
                }

                frame = requestAnimationFrame(detect);
            };

            setIsListening(true);
            detect();
        } catch {
            setIsListening(false);
        }
    };

    return (
        <div className="relative flex w-full flex-col items-center gap-4 text-center">
            {isBlown && <Confetti />}
            <div className="relative flex w-full flex-col items-center rounded-[2rem] px-4 pb-2 pt-6 transition-colors duration-700" style={{ backgroundColor: isBlown ? 'transparent' : 'rgba(31,22,43,0.82)' }}>
                {!isBlown && <p className="mb-1 text-label-sm uppercase tracking-[0.3em] text-tertiary-fixed-dim">Lampu dimatikan…</p>}
                <div className={`pointer-events-none absolute left-1/2 top-10 h-40 w-40 -translate-x-1/2 rounded-full bg-tertiary-fixed-dim blur-3xl transition-opacity duration-700 ${isBlown ? 'opacity-0' : 'opacity-50'}`} />
                <svg viewBox="0 0 200 190" className="relative h-52 w-52" aria-hidden="true">
                    <ellipse cx="100" cy="178" rx="88" ry="9" fill="#000" opacity="0.15" />
                    <ellipse cx="100" cy="170" rx="86" ry="10" fill="#fff7f9" />
                    <rect x="30" y="118" width="140" height="52" rx="12" fill="rgb(var(--color-primary-fixed-dim))" />
                    <path d="M30 128 q0 -10 12 -10 h116 q12 0 12 10 v6 q-8 10 -16 0 q-8 12 -17 0 q-8 10 -17 0 q-8 12 -17 0 q-8 10 -17 0 q-8 12 -17 0 q-8 10 -17 0 q-8 12 -16 0z" fill="#fffdf8" />
                    <rect x="52" y="78" width="96" height="42" rx="10" fill="rgb(var(--color-primary))" />
                    <path d="M52 86 q0 -8 10 -8 h76 q10 0 10 8 v4 q-7 9 -13 0 q-7 11 -14 0 q-7 9 -14 0 q-7 11 -14 0 q-7 9 -14 0 q-7 11 -14 0 q-6 9 -13 0z" fill="#fffdf8" />
                    {[[62, 150, '#fabc4d'], [92, 158, '#ffffff'], [128, 148, '#fabc4d'], [150, 160, '#ffffff'], [76, 104, '#fabc4d'], [122, 108, '#ffffff']].map(([x, y, color], index) => (
                        <rect key={index} x={x} y={y} width="7" height="3" rx="1.5" fill={color} transform={`rotate(${index * 37} ${x} ${y})`} />
                    ))}
                    <text x="100" y="146" textAnchor="middle" fontFamily="Epilogue, sans-serif" fontWeight="800" fontSize="20" fill="rgb(var(--color-primary))">
                        {values.candles}
                    </text>
                    {[78, 100, 122].map((x) => (
                        <g key={x}>
                            <rect x={x - 3} y="44" width="6" height="36" rx="2" fill="#fff" />
                            <path d={`M${x - 3} 52 l6 -4 M${x - 3} 62 l6 -4 M${x - 3} 72 l6 -4`} stroke="rgb(var(--color-primary))" strokeWidth="2" />
                            <g className={`origin-bottom transition-opacity duration-500 ${isBlown ? 'opacity-0' : 'animate-[flicker_0.9s_ease-in-out_infinite_alternate]'}`} style={{ transformOrigin: `${x}px 42px` }}>
                                <ellipse cx={x} cy="34" rx="6" ry="11" fill="#fabc4d" />
                                <ellipse cx={x} cy="37" rx="3" ry="6" fill="#fff7e0" />
                            </g>
                            {isBlown && <path d={`M${x} 40 q-5 -8 0 -16 q5 -8 0 -16`} stroke="#9a8f99" strokeWidth="2" fill="none" opacity="0.6" />}
                        </g>
                    ))}
                </svg>
            </div>

            {isBlown ? (
                <p className="max-w-xs font-display text-headline-sm text-on-surface">
                    {values.wish || `Semoga semua harapanmu terkabul, ${base.recipient_name}!`}
                </p>
            ) : (
                <div className="flex flex-col items-center gap-2">
                    <button
                        type="button"
                        onClick={isListening ? blowOut : listen}
                        className="flex h-14 w-14 animate-bounce items-center justify-center rounded-full bg-primary text-on-primary shadow-[0_4px_16px_rgba(168,50,35,0.3)]"
                    >
                        <Icon name={isListening ? 'air' : 'mic'} size={26} />
                    </button>
                    <span className="text-label-sm text-on-surface-variant">
                        {isListening ? 'Tiup mikrofon HP-mu sekarang! (atau ketuk lagi)' : 'Ketuk lalu tiup mikrofon untuk memadamkan lilin'}
                    </span>
                </div>
            )}
        </div>
    );
}
