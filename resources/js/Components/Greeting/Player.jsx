import Icon from '@/Components/Icon';
import { Motif } from '@/Components/TemplateCover';
import { resolveTheme, themeVars } from '@/lib/theme';
import { useEffect, useMemo, useRef, useState } from 'react';
import GateBadge from './blocks/GateBadge';
import { BACKGROUND_COMPONENTS, BLOCKS, INVITATION_SCENES } from './blocks';
import { isTimeLocked } from './blocks/TimeLock';
import Watermark from './Watermark';

const IGNORED_FIELDS = ['enabled', 'allow_reply'];

const WALL_SCENE_KEY = '__dinding_ucapan';

/**
 * Blok undangan tampil sesuai data undangannya (selalu tampil di Studio agar bisa diatur).
 * Tanpa data undangan (mis. pratinjau admin), blok ini disembunyikan.
 */
function isVisibleInvitationScene(slot, invitation, mode) {
    if (!invitation) {
        return false;
    }

    return mode === 'studio' || INVITATION_SCENES[slot.component](invitation);
}

/**
 * Komponen opsional baru tampil jika pengirim benar-benar mengisinya.
 */
function hasContent(slot, slotValues = {}) {
    return slot.schema.some((field) => {
        const value = slotValues?.[field.name];

        if (IGNORED_FIELDS.includes(field.name)) {
            return false;
        }

        return Array.isArray(value) ? value.length > 0 : value !== null && value !== undefined && value !== '';
    });
}

/**
 * Satu-satunya pemutar ucapan. Dipakai di Studio, Pratinjau, Demo katalog,
 * dan halaman penerima sehingga tampilan selalu konsisten.
 *
 * @param {'studio'|'preview'|'demo'|'recipient'} mode
 */
export default function Player({
    greeting,
    mode = 'recipient',
    slug = null,
    replied = false,
    reacted = [],
    answers = {},
    reactionEmojis = [],
    watermark = false,
    autoplayMusic = false,
    initialScene = 0,
}) {
    const { components, values, base } = greeting;
    const [index, setIndex] = useState(initialScene);
    const [isMuted, setIsMuted] = useState(false);
    const audioRef = useRef(null);
    const pausedForSongRef = useRef(false);

    const theme = resolveTheme(greeting.theme);

    const contributions = greeting.contributions ?? [];
    const invitation = greeting.invitation ?? null;

    const scenes = useMemo(() => {
        const list = components.filter(
            (slot) =>
                !BACKGROUND_COMPONENTS.includes(slot.component) &&
                BLOCKS[slot.component] &&
                (INVITATION_SCENES[slot.component]
                    ? isVisibleInvitationScene(slot, invitation, mode)
                    : slot.required || mode === 'studio' || hasContent(slot, values[slot.key])),
        );

        if (contributions.length === 0) {
            return list;
        }

        // Dinding ucapan patungan tampil tepat sebelum penutup.
        const wall = { key: WALL_SCENE_KEY, component: 'dinding_ucapan', required: true, schema: [] };
        const closingIndex = list.findIndex((slot) => slot.component === 'penutup');

        return closingIndex === -1 ? [...list, wall] : [...list.slice(0, closingIndex), wall, ...list.slice(closingIndex)];
    }, [components, values, mode, contributions.length, invitation]);
    const musicSlot = components.find((slot) => slot.component === 'musik_latar');
    const gateSlot = components.find((slot) => slot.component === 'gembok_pin');
    const track = musicSlot ? values[musicSlot.key]?.track : null;

    useEffect(() => {
        if (autoplayMusic && audioRef.current) {
            audioRef.current.play().catch(() => setIsMuted(true));
        }
    }, [autoplayMusic, track?.url]);

    useEffect(() => {
        if (audioRef.current) {
            audioRef.current.muted = isMuted;
        }
    }, [isMuted]);

    const scene = scenes[Math.min(index, scenes.length - 1)];
    const sceneValues = scene?.key === WALL_SCENE_KEY ? { items: contributions } : scene ? values[scene.key] ?? {} : {};
    const isBlockedByTime = mode === 'recipient' && scene?.component === 'kunci_waktu' && isTimeLocked(sceneValues);
    const isLast = index >= scenes.length - 1;
    const Block = scene ? BLOCKS[scene.component] : null;
    const isSongScene = scene?.component === 'lagu_kita';

    // Di adegan Lagu Kita, musik latar dijeda agar tidak bertabrakan dengan pemutar Spotify/YouTube,
    // lalu dilanjutkan saat penerima pindah adegan.
    useEffect(() => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        if (isSongScene && !audio.paused) {
            audio.pause();
            pausedForSongRef.current = true;
        } else if (!isSongScene && pausedForSongRef.current) {
            pausedForSongRef.current = false;
            audio.play().catch(() => {});
        }
    }, [isSongScene]);

    const toggleMusic = () => {
        if (!audioRef.current) {
            return;
        }

        if (audioRef.current.paused) {
            audioRef.current.play().catch(() => {});
            setIsMuted(false);
        } else {
            setIsMuted((muted) => !muted);
        }
    };

    return (
        <div
            className="relative flex h-full w-full flex-col overflow-hidden"
            style={{ ...themeVars(theme), background: `linear-gradient(180deg, ${theme.gradient.join(', ')})` }}
        >
            <Motif motif={theme.motif} accent={theme.accent} opacity={0.55} />
            {watermark && <Watermark recipientName={base.recipient_name} />}
            <div className="pointer-events-none absolute -right-20 -top-32 h-80 w-80 rounded-full blur-3xl" style={{ backgroundColor: theme.soft, opacity: 0.6 }} />
            <div className="pointer-events-none absolute -left-28 top-1/3 h-72 w-72 rounded-full blur-3xl" style={{ backgroundColor: theme.accent, opacity: 0.08 }} />

            {track?.url && <audio ref={audioRef} src={track.url} loop preload="none" />}

            <div className="relative z-30 flex flex-col gap-3 px-5 pb-2 pt-8">
                <div className="grid w-full items-center gap-1.5" style={{ gridTemplateColumns: `repeat(${Math.max(scenes.length, 1)}, minmax(0, 1fr))` }}>
                    {scenes.map((item, sceneIndex) => (
                        <button
                            key={item.key}
                            type="button"
                            aria-label={`Ke bagian ${sceneIndex + 1}`}
                            onClick={() => mode !== 'recipient' && setIndex(sceneIndex)}
                            className={`h-1 rounded-full ${sceneIndex <= index ? '' : 'bg-on-surface/15'}`}
                            style={sceneIndex <= index ? { backgroundColor: theme.accent } : undefined}
                        />
                    ))}
                </div>
                <div className="flex items-center justify-between">
                    {track ? (
                        <div className="flex items-center gap-2 rounded-full bg-surface-container-lowest/85 px-3 py-1.5 shadow-sm backdrop-blur-md">
                            <Icon name="music_note" size={14} className="text-primary" />
                            <span className="max-w-[150px] truncate text-label-sm text-on-surface-variant">{track.title}</span>
                        </div>
                    ) : (
                        <span />
                    )}
                    {track && (
                        <button
                            type="button"
                            onClick={toggleMusic}
                            aria-label="Musik"
                            className={`flex h-9 w-9 items-center justify-center rounded-full bg-surface-container-lowest/85 shadow-sm backdrop-blur-md ${isMuted ? 'text-error' : 'text-on-surface-variant'}`}
                        >
                            <Icon name={isMuted ? 'volume_off' : 'volume_up'} size={19} />
                        </button>
                    )}
                </div>
                {mode !== 'recipient' && gateSlot && <GateBadge values={values[gateSlot.key] ?? {}} />}
            </div>

            <div className="no-scrollbar relative z-20 flex flex-1 flex-col items-center overflow-y-auto px-5 py-3">
                {Block && (
                    <div key={scene.key} className="my-auto flex w-full animate-scene-in flex-col items-center">
                        <Block
                            slot={scene}
                            values={sceneValues}
                            base={base}
                            mode={mode}
                            slug={slug}
                            replied={replied}
                            reacted={reacted}
                            answers={answers}
                            reactionEmojis={reactionEmojis}
                            invitation={invitation}
                        />
                    </div>
                )}
            </div>

            <div className="relative z-30 flex flex-col items-center px-5 pb-6 pt-3">
                <div className="flex w-full items-center gap-2">
                    {index > 0 && (
                        <button
                            type="button"
                            onClick={() => setIndex((value) => value - 1)}
                            aria-label="Sebelumnya"
                            className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-surface-container-lowest text-on-surface shadow-sm"
                        >
                            <Icon name="arrow_back" size={20} />
                        </button>
                    )}
                    {!isLast && (
                        <button
                            type="button"
                            disabled={isBlockedByTime}
                            onClick={() => setIndex((value) => value + 1)}
                            className="group flex flex-1 items-center justify-between rounded-full px-6 py-3.5 text-white shadow-lg transition-all active:scale-[0.98] disabled:opacity-50"
                            style={{ backgroundColor: theme.ink }}
                        >
                            <span className="text-label-lg font-bold tracking-wide">Lanjut</span>
                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                <Icon name="arrow_forward" size={18} />
                            </span>
                        </button>
                    )}
                </div>
                <span className="mt-2 text-label-sm text-on-surface-variant">
                    Bagian {Math.min(index + 1, scenes.length)} dari {scenes.length}
                </span>
            </div>
        </div>
    );
}
