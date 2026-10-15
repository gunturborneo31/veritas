import Icon from '@/Components/Icon';

/**
 * Sertifikat lucu yang bisa diunduh sebagai PNG (digambar di canvas, tanpa dependensi).
 */
export default function Certificate({ values, base, mode }) {
    const download = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 850;
        const context = canvas.getContext('2d');

        context.fillStyle = '#fff7f9';
        context.fillRect(0, 0, 1200, 850);
        context.strokeStyle = '#fabc4d';
        context.lineWidth = 18;
        context.strokeRect(40, 40, 1120, 770);
        context.strokeStyle = '#a83223';
        context.lineWidth = 3;
        context.strokeRect(70, 70, 1060, 710);

        context.textAlign = 'center';
        context.fillStyle = '#a83223';
        context.font = '700 34px "Plus Jakarta Sans", sans-serif';
        context.fillText('SERTIFIKAT', 600, 200);
        context.fillStyle = '#281526';
        context.font = '800 64px Epilogue, sans-serif';
        context.fillText(values.title || 'Orang Paling Spesial', 600, 320);
        context.font = '400 30px "Plus Jakarta Sans", sans-serif';
        context.fillText('diberikan kepada', 600, 400);
        context.font = '700 56px Epilogue, sans-serif';
        context.fillText(base.recipient_name || '', 600, 480);
        context.font = '400 26px "Plus Jakarta Sans", sans-serif';
        context.fillStyle = '#58413d';
        context.fillText(values.description || '', 600, 560);
        context.fillText(`— ${base.sender_name || ''}`, 600, 680);

        const link = document.createElement('a');
        link.download = `sertifikat-${(base.recipient_name || 'kamu').toLowerCase().replace(/\s+/g, '-')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    };

    return (
        <div className="flex w-full flex-col items-center gap-4">
            <div className="w-full rounded-2xl border-[6px] border-tertiary-fixed-dim bg-surface-container-lowest p-1 shadow-lg">
                <div className="flex flex-col items-center gap-2 rounded-xl border-2 border-primary p-5 text-center">
                    <Icon name="workspace_premium" size={36} className="text-tertiary" />
                    <span className="text-label-sm uppercase tracking-widest text-primary">Sertifikat</span>
                    <p className="font-display text-headline-md text-on-surface">{values.title || 'Orang Paling Spesial'}</p>
                    <p className="text-body-sm text-on-surface-variant">diberikan kepada</p>
                    <p className="font-display text-headline-sm text-on-surface">{base.recipient_name}</p>
                    {values.description && <p className="text-body-sm text-on-surface-variant">{values.description}</p>}
                </div>
            </div>
            <button
                type="button"
                onClick={download}
                disabled={mode === 'studio'}
                className="flex items-center gap-2 rounded-full bg-surface-container px-5 py-2.5 text-label-md text-on-surface shadow-sm active:scale-95 disabled:opacity-60"
            >
                <Icon name="download" size={18} className="text-primary" />
                Unduh Sertifikat
            </button>
        </div>
    );
}
