import { MediaImage, SceneLabel } from './shared';

export default function Timeline({ values }) {
    const moments = values.moments ?? [];

    return (
        <div className="flex w-full flex-col gap-3">
            <SceneLabel icon="timeline">Perjalanan Kenangan</SceneLabel>
            <ol className="relative flex flex-col gap-4 border-l-2 border-primary-fixed-dim pl-5">
                {(moments.length ? moments : [{}, {}]).map((moment, index) => (
                    <li key={index} className="relative">
                        <span className="absolute -left-[27px] top-3 h-3 w-3 rounded-full bg-primary ring-4 ring-surface" />
                        <div className="flex gap-3 rounded-2xl bg-surface-container-lowest p-2.5 shadow-sm">
                            <div className="h-20 w-20 shrink-0 overflow-hidden rounded-xl">
                                <MediaImage media={moment.media} />
                            </div>
                            <p className="self-center text-body-sm text-on-surface">{moment.caption || 'Momen berharga'}</p>
                        </div>
                    </li>
                ))}
            </ol>
        </div>
    );
}
