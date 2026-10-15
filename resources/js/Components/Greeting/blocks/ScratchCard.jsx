import { useEffect, useRef, useState } from 'react';
import { Confetti, SceneLabel } from './shared';

const REVEAL_RATIO = 0.55;

/**
 * Kartu gosok: lapisan emas di canvas yang dihapus dengan jari.
 */
export default function ScratchCard({ slot, values }) {
    const canvasRef = useRef(null);
    const isDrawing = useRef(false);
    const [isRevealed, setIsRevealed] = useState(false);

    useEffect(() => {
        const canvas = canvasRef.current;
        const context = canvas.getContext('2d');
        const { width, height } = canvas.getBoundingClientRect();
        canvas.width = width;
        canvas.height = height;

        const gradient = context.createLinearGradient(0, 0, width, height);
        gradient.addColorStop(0, '#fabc4d');
        gradient.addColorStop(0.5, '#ffdead');
        gradient.addColorStop(1, '#9b6b00');
        context.fillStyle = gradient;
        context.fillRect(0, 0, width, height);
        context.fillStyle = '#604100';
        context.font = '600 16px "Plus Jakarta Sans", sans-serif';
        context.textAlign = 'center';
        context.fillText(values.cover_label || 'Gosok aku!', width / 2, height / 2 + 6);
    }, [values.cover_label]);

    const scratch = (event) => {
        if (!isDrawing.current || isRevealed) {
            return;
        }

        const canvas = canvasRef.current;
        const context = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        const point = event.touches?.[0] ?? event;

        context.globalCompositeOperation = 'destination-out';
        context.beginPath();
        context.arc(point.clientX - rect.left, point.clientY - rect.top, 22, 0, Math.PI * 2);
        context.fill();
    };

    const checkProgress = () => {
        isDrawing.current = false;
        const canvas = canvasRef.current;
        const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        let cleared = 0;

        for (let index = 3; index < pixels.length; index += 16) {
            if (pixels[index] === 0) {
                cleared++;
            }
        }

        if (cleared / (pixels.length / 16) > REVEAL_RATIO) {
            setIsRevealed(true);
        }
    };

    return (
        <div className="flex w-full flex-col gap-2">
            {slot?.title && <SceneLabel icon="ink_eraser">{slot.title}</SceneLabel>}
            <div className="relative h-48 w-full overflow-hidden rounded-3xl bg-surface-container-lowest shadow-lg">
                {isRevealed && <Confetti />}
                <p className="flex h-full items-center justify-center p-6 text-center font-display text-headline-sm text-on-surface">{values.hidden_message}</p>
                <canvas
                    ref={canvasRef}
                    className={`absolute inset-0 h-full w-full touch-none transition-opacity duration-500 ${isRevealed ? 'pointer-events-none opacity-0' : ''}`}
                    onPointerDown={() => (isDrawing.current = true)}
                    onPointerMove={scratch}
                    onPointerUp={checkProgress}
                    onPointerLeave={checkProgress}
                />
            </div>
        </div>
    );
}
