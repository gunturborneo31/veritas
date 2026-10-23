/**
 * Bingkai ponsel untuk menampilkan ucapan di Studio, Pratinjau, Demo, dan beranda.
 * `bare` menghilangkan bingkai di layar kecil supaya kartu memakai seluruh lebar HP.
 */
export default function PhoneFrame({ children, className = '', screenClassName = 'h-[min(620px,calc(100dvh_-_11rem))] min-h-[440px]', bare = false }) {
    return (
        <div
            className={`relative w-full max-w-[340px] rounded-[2.8rem] bg-[#1f1b18] p-2.5 shadow-2xl shadow-[#2e1a2c]/20 ${
                bare ? 'max-sm:max-w-none max-sm:rounded-[2rem] max-sm:bg-transparent max-sm:p-0 max-sm:shadow-[0_12px_32px_-12px_rgba(46,26,44,0.35)]' : ''
            } ${className}`}
        >
            <div className={`relative flex w-full select-none flex-col overflow-hidden rounded-[2.3rem] bg-surface ${bare ? 'max-sm:rounded-[2rem]' : ''} ${screenClassName}`}>
                <div className={`absolute left-1/2 top-2 z-50 h-4 w-20 -translate-x-1/2 rounded-full bg-black ${bare ? 'max-sm:hidden' : ''}`} />
                {children}
            </div>
        </div>
    );
}
