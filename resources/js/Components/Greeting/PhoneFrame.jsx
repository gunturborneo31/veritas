/**
 * Bingkai ponsel untuk menampilkan ucapan di Studio, Pratinjau, dan Demo.
 */
export default function PhoneFrame({ children, className = '' }) {
    return (
        <div className={`relative w-full max-w-[340px] rounded-[2.8rem] bg-[#1f1b18] p-2.5 shadow-2xl shadow-[#2e1a2c]/20 ${className}`}>
            <div className="relative flex h-[620px] w-full select-none flex-col overflow-hidden rounded-[2.3rem] bg-surface">
                <div className="absolute left-1/2 top-2 z-50 h-4 w-20 -translate-x-1/2 rounded-full bg-black" />
                {children}
            </div>
        </div>
    );
}
