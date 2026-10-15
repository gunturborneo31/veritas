import AskQuestion from './AskQuestion';
import BigQuote from './BigQuote';
import BlowCandle from './BlowCandle';
import Certificate from './Certificate';
import Closing from './Closing';
import ContributionWall from './ContributionWall';
import Countdown from './Countdown';
import Coupons from './Coupons';
import Cover from './Cover';
import FramedPhoto from './FramedPhoto';
import Gallery from './Gallery';
import GateBadge from './GateBadge';
import LayeredMessage from './LayeredMessage';
import Letter from './Letter';
import Numbers from './Numbers';
import Quiz from './Quiz';
import Reasons from './Reasons';
import ScratchCard from './ScratchCard';
import TimeLock from './TimeLock';
import Timeline from './Timeline';
import VoiceNote from './VoiceNote';

/**
 * Registri komponen ucapan: key komponen di database → blok React.
 * Menambah komponen baru = tambah baris di sini + definisi skema di admin.
 */
export const BLOCKS = {
    sampul: Cover,
    gembok_pin: GateBadge,
    kunci_waktu: TimeLock,
    foto_bingkai: FramedPhoto,
    galeri_geser: Gallery,
    surat: Letter,
    pesan_berlapis: LayeredMessage,
    pesan_suara: VoiceNote,
    tiup_lilin: BlowCandle,
    kuis: Quiz,
    gosok_kartu: ScratchCard,
    kupon_janji: Coupons,
    hitung_mundur: Countdown,
    linimasa: Timeline,
    kutipan: BigQuote,
    daftar_alasan: Reasons,
    angka_kita: Numbers,
    sertifikat: Certificate,
    tanya_jawab: AskQuestion,
    dinding_ucapan: ContributionWall,
    penutup: Closing,
};

/**
 * Komponen yang tidak tampil sebagai adegan tersendiri di pemutar.
 * Musik latar diputar global; gembok ditangani di layar gerbang.
 */
export const BACKGROUND_COMPONENTS = ['musik_latar', 'gembok_pin'];
