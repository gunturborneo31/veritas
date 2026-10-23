import AskQuestion from './AskQuestion';
import BigQuote from './BigQuote';
import BlowCandle from './BlowCandle';
import Certificate from './Certificate';
import Closing from './Closing';
import ContributionWall from './ContributionWall';
import Countdown from './Countdown';
import Couple from './Couple';
import Coupons from './Coupons';
import Cover from './Cover';
import EventDetails from './EventDetails';
import FramedPhoto from './FramedPhoto';
import Gallery from './Gallery';
import GateBadge from './GateBadge';
import GiftEnvelope from './GiftEnvelope';
import Honoree from './Honoree';
import LayeredMessage from './LayeredMessage';
import MemoryMap from './MemoryMap';
import Letter from './Letter';
import Numbers from './Numbers';
import Quiz from './Quiz';
import Reasons from './Reasons';
import Rsvp from './Rsvp';
import ScratchCard from './ScratchCard';
import SongEmbed from './SongEmbed';
import TimeLock from './TimeLock';
import Timeline from './Timeline';
import VoiceNote from './VoiceNote';
import Wishes from './Wishes';

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
    jejak_lokasi: MemoryMap,
    lagu_kita: SongEmbed,
    kutipan: BigQuote,
    daftar_alasan: Reasons,
    angka_kita: Numbers,
    sertifikat: Certificate,
    tanya_jawab: AskQuestion,
    dinding_ucapan: ContributionWall,
    mempelai: Couple,
    profil_acara: Honoree,
    acara_undangan: EventDetails,
    rsvp: Rsvp,
    amplop_digital: GiftEnvelope,
    ucapan_doa: Wishes,
    penutup: Closing,
};

/**
 * Komponen yang tidak tampil sebagai adegan tersendiri di pemutar.
 * Musik latar diputar global; gembok ditangani di layar gerbang.
 */
export const BACKGROUND_COMPONENTS = ['musik_latar', 'gembok_pin'];

/**
 * Blok undangan yang isinya diambil dari data hidup undangan (bukan isian Studio),
 * sehingga tampil atau tidaknya ditentukan oleh data tersebut.
 */
export const INVITATION_SCENES = {
    acara_undangan: (invitation) => invitation.events.length > 0,
    rsvp: (invitation) => invitation.rsvp.enabled,
    amplop_digital: (invitation) => invitation.gifts.length > 0,
    ucapan_doa: (invitation) => invitation.wishes.enabled,
};
