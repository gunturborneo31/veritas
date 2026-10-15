import FlashToast from '@/Components/FlashToast';
import Player from '@/Components/Greeting/Player';
import ReactionRecorder from '@/Components/Greeting/ReactionRecorder';
import { Head } from '@inertiajs/react';

/**
 * Pemutaran ucapan untuk penerima, dibaca dari snapshot permanen (desain layarpenerimapemutaran).
 */
export default function Play({ slug, greeting, replied, reacted, answers, reactionEmojis, videoSent }) {
    return (
        <div className="mx-auto h-[100dvh] w-full max-w-md">
            <Head title={`Untuk ${greeting.base.recipient_name}`}>
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
            <ReactionRecorder slug={slug} senderName={greeting.base.sender_name} alreadySent={videoSent} />
        </div>
    );
}
