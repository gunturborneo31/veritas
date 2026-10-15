import Icon from '@/Components/Icon';
import { duration } from '@/lib/format';
import { useRef, useState } from 'react';

export default function VoiceNote({ values, base }) {
    const audioRef = useRef(null);
    const [isPlaying, setIsPlaying] = useState(false);
    const [progress, setProgress] = useState(0);
    const [length, setLength] = useState(0);

    const toggle = () => {
        if (!audioRef.current) {
            return;
        }

        if (isPlaying) {
            audioRef.current.pause();
        } else {
            audioRef.current.play();
        }
    };

    return (
        <div className="flex w-full items-center gap-3 rounded-2xl bg-surface-container-lowest p-3 shadow-sm">
            {values.audio?.url && (
                <audio
                    ref={audioRef}
                    src={values.audio.url}
                    preload="metadata"
                    onPlay={() => setIsPlaying(true)}
                    onPause={() => setIsPlaying(false)}
                    onEnded={() => setIsPlaying(false)}
                    onLoadedMetadata={(event) => setLength(event.currentTarget.duration)}
                    onTimeUpdate={(event) => setProgress(event.currentTarget.currentTime)}
                />
            )}
            <button
                type="button"
                onClick={toggle}
                disabled={!values.audio?.url}
                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary shadow-sm disabled:opacity-50"
            >
                <Icon name={isPlaying ? 'pause' : 'play_arrow'} size={22} />
            </button>
            <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between text-label-md text-on-surface">
                    <span className="truncate">{values.note || `Pesan suara dari ${base.sender_name || 'pengirim'}`}</span>
                    <span className="text-on-surface-variant">{duration(isPlaying ? progress : length)}</span>
                </div>
                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-primary/20">
                    <div className="h-full bg-primary transition-all" style={{ width: `${length ? (progress / length) * 100 : 0}%` }} />
                </div>
            </div>
        </div>
    );
}
