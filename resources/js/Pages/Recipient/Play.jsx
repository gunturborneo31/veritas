import FlashToast from '@/Components/FlashToast';
import Player from '@/Components/Greeting/Player';
import ReactionRecorder from '@/Components/Greeting/ReactionRecorder';
import { Head } from '@inertiajs/react';

/**
 * Pemutaran ucapan untuk penerima, dibaca dari snapshot permanen (desain layarpenerimapemutaran).
 * Undangan memakai pemutar yang sama, tanpa perekam video reaksi (ditujukan ke banyak tamu).
 */
export default function Play({ slug, greeting, replied, reacted, answers, reactionEmojis, videoSent }) {
    const isInvitation = Boolean(greeting.invitation);
    const title = isInvitation ? `Undangan ${greeting.base.occasion ?? ''} ${greeting.base.sender_name ?? ''}`.trim() : `Untuk ${greeting.base.recipient_name}`;

    return (
        <div className="mx-auto h-[100dvh] w-full max-w-md">
            <Head title={title}>
                <meta name="robots" content="noindex" />
            </Head>
            <FlashToast />
            <Player
                greeting={greeting}
                mode="recipient"
                slug={slug}
                replied={replied}
                reacted={reacted}
                answers={answers}
                reactionEmojis={reactionEmojis}
                autoplayMusic
            />
            {!isInvitation && <ReactionRecorder slug={slug} senderName={greeting.base.sender_name} alreadySent={videoSent} />}
        </div>
    );
}
