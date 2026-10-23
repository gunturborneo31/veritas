import { mixHex } from '@/lib/theme';

const HEART = 'M0 4C-2-2-10-1-10 5c0 5 7 9 10 12 3-3 10-7 10-12 0-6-8-7-10-1z';
const SPARKLE = 'M0-10C1 -3 3 -1 10 0 3 1 1 3 0 10-1 3-3 1-10 0-3-1-1-3 0-10z';
const STAR = 'M0-12 3.5-4 12-3.7 5.5 2 7.4 10.5 0 6-7.4 10.5-5.5 2-12-3.7-3.5-4z';
const CRESCENT = 'M118 46A56 56 0 1 0 156 126 46 46 0 1 1 118 46z';
const TULIP = 'M-12-4C-12-20-6-22 0-14 6-22 12-20 12-4 12 10-12 10-12-4z';

const Heart = ({ x, y, scale = 1, fill, opacity = 1 }) => <path d={HEART} transform={`translate(${x} ${y}) scale(${scale})`} fill={fill} opacity={opacity} />;

const Sparkles = ({ points, fill }) =>
    points.map(([x, y, scale = 1], index) => <path key={index} d={SPARKLE} transform={`translate(${x} ${y}) scale(${scale})`} fill={fill} />);

const Flame = ({ x, y, c }) => (
    <g transform={`translate(${x} ${y})`}>
        <circle r="11" fill={c.gold} opacity="0.22" />
        <path d="M0-14C6-6 7 0 0 4-7 0-6-6 0-14z" fill={c.gold} />
        <path d="M0-6C3-2 3 1 0 3-3 1-3-2 0-6z" fill="#fff3c4" />
    </g>
);

const Cloud = ({ x, y, scale = 1, fill }) => (
    <g transform={`translate(${x} ${y}) scale(${scale})`} fill={fill}>
        <circle cx="-12" cy="2" r="10" />
        <circle cx="2" cy="-5" r="13" />
        <circle cx="15" cy="3" r="9" />
        <rect x="-22" y="2" width="46" height="10" rx="5" />
    </g>
);

const Shadow = ({ c, rx = 56 }) => <ellipse cx="100" cy="172" rx={rx} ry="7" fill={c.dark} opacity="0.14" />;

/**
 * Adegan ilustrasi datar 200×200. Semua warna diturunkan dari aksen tema agar serasi dengan kartu.
 */
const SCENES = {
    cake: (c) => (
        <>
            <Shadow c={c} rx={70} />
            <ellipse cx="100" cy="162" rx="70" ry="10" fill={c.white} />
            <rect x="42" y="110" width="116" height="50" rx="10" fill={c.accent} />
            <rect x="42" y="110" width="116" height="12" rx="6" fill={c.white} />
            {[[52, 14], [74, 22], [100, 12], [128, 18], [146, 10]].map(([x, height]) => (
                <rect key={x} x={x} y="114" width="8" height={height} rx="4" fill={c.white} />
            ))}
            {[[60, 142, c.gold], [84, 148, c.white], [112, 140, c.gold], [136, 148, c.white]].map(([x, y, fill]) => (
                <rect key={x} x={x} y={y} width="8" height="3" rx="1.5" fill={fill} transform={`rotate(-25 ${x} ${y})`} />
            ))}
            <rect x="60" y="76" width="80" height="38" rx="9" fill={c.light} />
            <rect x="60" y="76" width="80" height="10" rx="5" fill={c.white} />
            {[[70, 12], [94, 16], [120, 10]].map(([x, height]) => (
                <rect key={x} x={x} y="80" width="7" height={height} rx="3.5" fill={c.white} />
            ))}
            {[74, 96.5, 119].map((x) => (
                <g key={x}>
                    <rect x={x} y="48" width="7" height="30" rx="2" fill={c.white} />
                    <rect x={x} y="56" width="7" height="4" fill={c.accent} opacity="0.55" />
                    <rect x={x} y="66" width="7" height="4" fill={c.accent} opacity="0.55" />
                    <Flame x={x + 3.5} y={42} c={c} />
                </g>
            ))}
            <Sparkles points={[[34, 70, 0.8], [168, 82, 0.6], [156, 40, 0.5]]} fill={c.gold} />
        </>
    ),
    cupcake: (c) => (
        <>
            <Shadow c={c} rx={46} />
            <path d="M60 118h80l-10 50H70z" fill={c.accent} />
            <path d="M80 118l3 50M100 118v50M120 118l-3 50" stroke={c.dark} strokeWidth="4" opacity="0.22" />
            <ellipse cx="100" cy="116" rx="46" ry="14" fill={c.light} />
            <ellipse cx="100" cy="102" rx="38" ry="13" fill={c.light} />
            <ellipse cx="100" cy="88" rx="26" ry="11" fill={c.light} />
            <ellipse cx="100" cy="78" rx="14" ry="8" fill={c.light} />
            <ellipse cx="84" cy="100" rx="10" ry="4" fill={c.white} opacity="0.7" />
            <ellipse cx="92" cy="86" rx="6" ry="3" fill={c.white} opacity="0.7" />
            {[[72, 112, c.gold], [118, 108, c.accent], [96, 96, c.gold], [110, 84, c.white], [128, 116, c.white]].map(([x, y, fill]) => (
                <rect key={`${x}-${y}`} x={x} y={y} width="7" height="3" rx="1.5" fill={fill} transform={`rotate(30 ${x} ${y})`} />
            ))}
            <rect x="97" y="46" width="6" height="28" rx="2" fill={c.white} />
            <rect x="97" y="54" width="6" height="4" fill={c.accent} opacity="0.55" />
            <Flame x={100} y={40} c={c} />
            <Heart x={156} y={64} scale={0.9} fill={c.accent} opacity={0.7} />
            <Sparkles points={[[44, 60, 0.7], [160, 120, 0.5]]} fill={c.gold} />
        </>
    ),
    popper: (c) => (
        <>
            <Shadow c={c} rx={40} />
            <path d="M110 100c6-30 30-24 26-50" stroke={c.accent} strokeWidth="4" strokeLinecap="round" fill="none" />
            <path d="M120 108c30-12 30-38 52-42" stroke={c.light} strokeWidth="4" strokeLinecap="round" fill="none" />
            <path d="M104 98c-8-28 8-42-4-64" stroke={c.gold} strokeWidth="4" strokeLinecap="round" fill="none" />
            <path d="M58 168 82 94q26 6 50 30z" fill={c.gold} />
            <path d="M68.8 134.7 91.3 148.2M76 112.5l37.5 22.5" stroke={c.accent} strokeWidth="7" />
            <ellipse cx="107" cy="109" rx="29" ry="8" transform="rotate(31 107 109)" fill={c.dark} />
            {[[150, 40, c.accent, 20], [80, 50, c.gold, -30], [160, 128, c.accent, 45], [132, 76, c.white, 10]].map(([x, y, fill, rotate]) => (
                <rect key={x} x={x} y={y} width="6" height="12" rx="2" fill={fill} transform={`rotate(${rotate} ${x} ${y})`} />
            ))}
            <circle cx="170" cy="96" r="4" fill={c.gold} />
            <circle cx="126" cy="30" r="3.5" fill={c.light} />
            <circle cx="62" cy="80" r="3" fill={c.accent} />
            <Sparkles points={[[174, 40, 0.8], [48, 112, 0.6]]} fill={c.gold} />
        </>
    ),
    crown: (c) => (
        <>
            <Shadow c={c} rx={60} />
            <rect x="44" y="136" width="112" height="30" rx="15" fill={c.accent} />
            <rect x="56" y="140" width="88" height="6" rx="3" fill={c.white} opacity="0.25" />
            <circle cx="46" cy="160" r="5" fill={c.gold} />
            <circle cx="154" cy="160" r="5" fill={c.gold} />
            <path d="M58 132 52 78l28 24 20-38 20 38 28-24-6 54z" fill={c.gold} />
            <path d="M64 100 62 82l12 10" stroke={c.white} strokeWidth="3" opacity="0.5" fill="none" strokeLinecap="round" />
            <rect x="56" y="118" width="88" height="16" rx="3" fill={c.goldDark} />
            <circle cx="52" cy="76" r="6" fill={c.gold} />
            <circle cx="100" cy="60" r="7" fill={c.gold} />
            <circle cx="148" cy="76" r="6" fill={c.gold} />
            <ellipse cx="100" cy="100" rx="6" ry="8" fill={c.accent} />
            <circle cx="100" cy="126" r="5" fill={c.accent} />
            <circle cx="78" cy="126" r="4" fill={c.white} />
            <circle cx="122" cy="126" r="4" fill={c.white} />
            <Sparkles points={[[36, 52, 0.8], [166, 46, 0.7], [170, 110, 0.5]]} fill={c.gold} />
        </>
    ),
    gifts: (c) => (
        <>
            <Shadow c={c} rx={66} />
            <rect x="112" y="116" width="52" height="50" rx="5" fill={c.light} />
            <rect x="108" y="106" width="60" height="14" rx="4" fill={c.light} />
            <rect x="108" y="116" width="60" height="5" fill={c.dark} opacity="0.12" />
            <rect x="134" y="106" width="8" height="60" fill={c.accent} />
            <rect x="40" y="106" width="84" height="60" rx="6" fill={c.accent} />
            <rect x="34" y="92" width="96" height="20" rx="5" fill={c.accent} />
            <rect x="40" y="112" width="84" height="6" fill={c.dark} opacity="0.25" />
            <rect x="76" y="92" width="12" height="74" fill={c.gold} />
            <ellipse cx="70" cy="84" rx="16" ry="9" transform="rotate(-20 70 84)" fill={c.gold} />
            <ellipse cx="94" cy="84" rx="16" ry="9" transform="rotate(20 94 84)" fill={c.gold} />
            <circle cx="82" cy="90" r="6" fill={c.goldDark} />
            <rect x="48" y="96" width="20" height="4" rx="2" fill={c.white} opacity="0.35" />
            <Heart x={150} y={64} fill={c.accent} />
            <Heart x={50} y={54} scale={0.7} fill={c.light} />
            <Sparkles points={[[170, 92, 0.6], [120, 52, 0.7]]} fill={c.gold} />
        </>
    ),
    letter: (c) => (
        <>
            <Shadow c={c} rx={60} />
            <path d="M46 98 100 58l54 40z" fill={c.dark} />
            <rect x="62" y="60" width="76" height="70" rx="4" fill={c.white} />
            <rect x="72" y="74" width="40" height="4" rx="2" fill={c.light} />
            <rect x="72" y="84" width="56" height="4" rx="2" fill={c.light} />
            <rect x="72" y="94" width="48" height="4" rx="2" fill={c.light} />
            <rect x="46" y="96" width="108" height="70" rx="8" fill={c.accent} />
            <path d="M48 104l52 36 52-36" stroke={c.dark} strokeWidth="3" opacity="0.35" fill="none" strokeLinejoin="round" />
            <circle cx="100" cy="140" r="13" fill={c.gold} />
            <Heart x={100} y={134} scale={0.55} fill={c.goldDark} />
            <Heart x={156} y={60} scale={0.9} fill={c.accent} opacity={0.75} />
            <Heart x={40} y={70} scale={0.6} fill={c.light} />
            <Sparkles points={[[166, 104, 0.6], [34, 128, 0.5]]} fill={c.gold} />
        </>
    ),
    rose: (c) => (
        <>
            <Shadow c={c} rx={34} />
            <path d="M100 152c-2-30 2-50 0-68" stroke={c.leafDark} strokeWidth="5" fill="none" strokeLinecap="round" />
            <path d="M100 120c-14-6-22-2-26 6 12 4 20 2 26-6z" fill={c.leaf} />
            <path d="M101 106c13-8 23-4 27 4-12 4-20 2-27-4z" fill={c.leaf} />
            <path d="M84 124c-8 16-8 36 2 44h28c10-8 10-28 2-44z" fill={c.white} opacity="0.75" />
            <rect x="88" y="116" width="24" height="10" rx="3" fill={c.white} opacity="0.9" />
            <circle cx="100" cy="68" r="24" fill={c.accent} />
            <path d="M78 66c0 22 44 22 44 0-6 14-38 14-44 0z" fill={c.dark} opacity="0.35" />
            <path d="M100 56c12 0 12 16 0 16-8 0-8-10 0-10" stroke={c.dark} strokeWidth="3" fill="none" strokeLinecap="round" opacity="0.6" />
            <path d="M84 58c4-8 12-10 16-10" stroke={c.white} strokeWidth="3" opacity="0.4" fill="none" strokeLinecap="round" />
            <Sparkles points={[[54, 52, 0.8], [148, 40, 0.6], [150, 112, 0.5]]} fill={c.gold} />
        </>
    ),
    clock: (c) => (
        <>
            <Shadow c={c} rx={60} />
            <g transform="rotate(-14 60 104)">
                <rect x="36" y="72" width="48" height="58" rx="3" fill={c.white} />
                <rect x="41" y="77" width="38" height="38" fill={c.light} />
            </g>
            <g transform="rotate(12 140 100)">
                <rect x="116" y="70" width="48" height="58" rx="3" fill={c.white} />
                <rect x="121" y="75" width="38" height="38" fill={c.soft} />
            </g>
            <path d="M64 166v-66c0-36 72-36 72 0v66z" fill={c.accent} />
            <circle cx="100" cy="64" r="6" fill={c.gold} />
            <circle cx="100" cy="104" r="28" fill={c.white} />
            <circle cx="100" cy="104" r="28" fill="none" stroke={c.gold} strokeWidth="5" />
            {[[100, 82], [122, 104], [100, 126], [78, 104]].map(([x, y]) => (
                <circle key={`${x}-${y}`} cx={x} cy={y} r="2.2" fill={c.dark} />
            ))}
            <path d="M100 104V88M100 104l13 6" stroke={c.dark} strokeWidth="3.5" strokeLinecap="round" />
            <circle cx="100" cy="104" r="3" fill={c.gold} />
            <rect x="88" y="138" width="24" height="20" rx="4" fill={c.dark} opacity="0.35" />
            <path d="M100 138v10" stroke={c.gold} strokeWidth="2" />
            <circle cx="100" cy="151" r="5" fill={c.gold} />
            <rect x="56" y="160" width="88" height="10" rx="3" fill={c.dark} />
            <Sparkles points={[[164, 46, 0.7], [36, 50, 0.5]]} fill={c.gold} />
        </>
    ),
    cap: (c) => (
        <>
            <Shadow c={c} rx={62} />
            <path d="M56 132c0-48 88-48 88 0z" fill={c.accent} />
            <path d="M100 90v42M78 96c6 14 8 24 8 36M122 96c-6 14-8 24-8 36" stroke={c.dark} strokeWidth="2.5" opacity="0.35" fill="none" />
            <circle cx="100" cy="88" r="5" fill={c.dark} />
            <path d="M52 132h98c18 0 26 8 20 14-20 4-80 2-118-6z" fill={c.dark} />
            <circle cx="120" cy="114" r="9" fill={c.gold} />
            <path d="M120 108l2 4 4 .5-3 3 1 4-4-2-4 2 1-4-3-3 4-.5z" fill={c.white} />
            <path d="M128 40h40a10 10 0 0 1 10 10v12a10 10 0 0 1-10 10h-24l-10 9v-9h-6a10 10 0 0 1-10-10V50a10 10 0 0 1 10-10z" fill={c.white} />
            <text x="148" y="65" textAnchor="middle" fontFamily="Epilogue, sans-serif" fontWeight="800" fontSize="24" fill={c.accent}>
                ?
            </text>
            <circle cx="52" cy="62" r="14" fill={c.gold} />
            <text x="52" y="70" textAnchor="middle" fontFamily="Epilogue, sans-serif" fontWeight="800" fontSize="20" fill={c.white}>
                !
            </text>
            <Sparkles points={[[34, 104, 0.6]]} fill={c.gold} />
        </>
    ),
    house: (c) => (
        <>
            <ellipse cx="100" cy="166" rx="76" ry="9" fill={c.leaf} opacity="0.35" />
            <rect x="153" y="118" width="6" height="46" rx="2" fill={c.brown} />
            <circle cx="156" cy="112" r="20" fill={c.leaf} />
            <circle cx="146" cy="98" r="14" fill={c.leaf} />
            <rect x="122" y="70" width="12" height="26" fill={c.dark} />
            <rect x="52" y="104" width="92" height="60" fill={c.white} />
            <path d="M42 110 98 62l56 48z" fill={c.accent} />
            <path d="M48 110 98 68l50 42" stroke={c.dark} strokeWidth="3" opacity="0.25" fill="none" />
            <path d="M88 164v-26a10 10 0 0 1 20 0v26z" fill={c.dark} />
            <circle cx="104" cy="152" r="1.8" fill={c.gold} />
            <rect x="60" y="118" width="22" height="20" rx="3" fill={c.gold} />
            <path d="M71 118v20M60 128h22" stroke={c.white} strokeWidth="2" />
            <rect x="114" y="118" width="22" height="20" rx="3" fill={c.gold} />
            <Heart x={125} y={124} scale={0.45} fill={c.accent} />
            <Heart x={132} y={52} scale={0.6} fill={c.light} />
            <Heart x={142} y={36} scale={0.45} fill={c.light} opacity={0.7} />
            <Sparkles points={[[40, 62, 0.7]]} fill={c.gold} />
        </>
    ),
    moon: (c) => (
        <>
            <path d={CRESCENT} fill={c.gold} />
            <path d="M74 80c4-14 16-24 30-28" stroke={c.white} strokeWidth="4" opacity="0.35" fill="none" strokeLinecap="round" />
            <path d="M150 0v52" stroke={c.dark} strokeWidth="2" />
            <circle cx="150" cy="76" r="22" fill={c.gold} opacity="0.2" />
            <path d="M140 56h20l-4-6h-12z" fill={c.dark} />
            <path d="M138 58h24l-4 34h-16z" fill={c.accent} />
            <rect x="145" y="64" width="10" height="20" rx="3" fill={c.gold} />
            <path d="M142 92h16l-4 6h-8z" fill={c.dark} />
            <path d="M150 98v12" stroke={c.gold} strokeWidth="2" />
            <Sparkles points={[[48, 40, 0.9], [168, 132, 0.7], [44, 150, 0.6], [124, 162, 0.5]]} fill={c.gold} />
            <circle cx="70" cy="160" r="2" fill={c.accent} />
            <circle cx="176" cy="30" r="2" fill={c.accent} />
        </>
    ),
    mosque: (c) => (
        <>
            <path d={CRESCENT} transform="translate(124 16) scale(.38)" fill={c.gold} />
            <rect x="26" y="162" width="148" height="7" rx="3" fill={c.dark} />
            <rect x="34" y="86" width="14" height="78" fill={c.light} />
            <path d="M30 88l11-26 11 26z" fill={c.accent} />
            <rect x="32" y="102" width="18" height="5" fill={c.accent} />
            <rect x="152" y="86" width="14" height="78" fill={c.light} />
            <path d="M148 88l11-26 11 26z" fill={c.accent} />
            <rect x="150" y="102" width="18" height="5" fill={c.accent} />
            <path d="M100 42v12" stroke={c.gold} strokeWidth="3" />
            <circle cx="100" cy="40" r="4" fill={c.gold} />
            <path d="M66 112c0-32 34-44 34-60 0 16 34 28 34 60z" fill={c.accent} />
            <path d="M76 104c2-14 10-22 18-28" stroke={c.white} strokeWidth="3" opacity="0.35" fill="none" strokeLinecap="round" />
            <rect x="56" y="108" width="88" height="56" fill={c.white} />
            <path d="M88 164v-26a12 12 0 0 1 24 0v26z" fill={c.accent} />
            <path d="M66 150v-14a6 6 0 0 1 12 0v14zM122 150v-14a6 6 0 0 1 12 0v14z" fill={c.gold} />
            <Sparkles points={[[40, 40, 0.7], [76, 24, 0.5], [178, 92, 0.5]]} fill={c.gold} />
        </>
    ),
    car: (c) => (
        <>
            <rect x="24" y="166" width="152" height="4" rx="2" fill={c.dark} opacity="0.2" />
            <rect x="76" y="78" width="30" height="18" rx="3" fill={c.gold} />
            <rect x="106" y="82" width="24" height="14" rx="3" fill={c.light} />
            <path d="M91 78v18M118 82v14" stroke={c.dark} strokeWidth="2" opacity="0.3" />
            <path d="M70 118l12-22h42l16 22z" fill={c.light} />
            <path d="M80 116l8-15h14v15zM108 101h12l11 15h-23z" fill={c.white} opacity="0.85" />
            <path d="M36 140c0-16 8-22 24-22h88c12 0 20 8 20 22v8H36z" fill={c.accent} />
            <path d="M104 120v26" stroke={c.dark} strokeWidth="2" opacity="0.3" />
            <circle cx="162" cy="130" r="4" fill={c.gold} />
            <rect x="34" y="144" width="136" height="6" rx="3" fill={c.dark} opacity="0.3" />
            {[66, 138].map((x) => (
                <g key={x}>
                    <circle cx={x} cy="152" r="14" fill={c.dark} />
                    <circle cx={x} cy="152" r="6" fill={c.white} />
                </g>
            ))}
            <Heart x={148} y={58} scale={0.7} fill={c.accent} opacity={0.7} />
            <Cloud x={46} y={58} scale={0.8} fill={c.white} />
        </>
    ),
    umbrella: (c) => (
        <>
            <ellipse cx="100" cy="170" rx="44" ry="5" fill={c.light} opacity="0.6" />
            {[[36, 40], [60, 24], [150, 30], [172, 60], [28, 132], [168, 130], [52, 150]].map(([x, y]) => (
                <path key={`${x}-${y}`} d={`M${x} ${y}l-4 12`} stroke={c.light} strokeWidth="3" strokeLinecap="round" />
            ))}
            <path d="M100 50V38" stroke={c.dark} strokeWidth="3" strokeLinecap="round" />
            <path d="M36 112q64-84 128 0-16-12-32 0-16-12-32 0-16-12-32 0-16-12-32 0z" fill={c.accent} />
            <path d="M100 50q-16 30-32 62M100 50q16 30 32 62M100 50v62" stroke={c.dark} strokeWidth="2" opacity="0.3" fill="none" />
            <path d="M60 88c8-14 20-24 32-28" stroke={c.white} strokeWidth="3" opacity="0.35" fill="none" strokeLinecap="round" />
            <path d="M100 112v44a9 9 0 0 1-18 0" stroke={c.dark} strokeWidth="5" fill="none" strokeLinecap="round" />
            <Heart x={124} y={138} scale={0.8} fill={c.gold} />
        </>
    ),
    flag: (c) => (
        <>
            <Shadow c={c} rx={30} />
            <rect x="58" y="44" width="6" height="126" rx="3" fill={c.dark} />
            <circle cx="61" cy="42" r="6" fill={c.gold} />
            <path d="M64 52c26-12 48 12 86 0v56c-38 12-60-12-86 0z" fill={c.white} stroke={c.light} strokeWidth="4" strokeLinejoin="round" />
            <path d="M64 92c26-12 48 12 86 0" stroke={c.light} strokeWidth="3" fill="none" opacity="0.7" />
            <Heart x={106} y={66} scale={1.1} fill={c.accent} />
            <path d="M120 150c14-4 24-14 30-28" stroke={c.leafDark} strokeWidth="3" fill="none" strokeLinecap="round" />
            {[[128, 146, -40], [138, 138, -20], [146, 128, -60], [124, 140, 20]].map(([x, y, rotate]) => (
                <ellipse key={`${x}-${y}`} cx={x} cy={y} rx="7" ry="3.5" transform={`rotate(${rotate} ${x} ${y})`} fill={c.leaf} />
            ))}
            <Sparkles points={[[164, 44, 0.8], [36, 100, 0.6]]} fill={c.gold} />
        </>
    ),
    rainbow: (c) => (
        <>
            <path d="M40 140a60 60 0 0 1 120 0" stroke={c.accent} strokeWidth="12" fill="none" />
            <path d="M52 140a48 48 0 0 1 96 0" stroke={c.gold} strokeWidth="12" fill="none" />
            <path d="M64 140a36 36 0 0 1 72 0" stroke={c.light} strokeWidth="12" fill="none" />
            <Cloud x={46} y={140} fill={c.white} />
            <Cloud x={154} y={140} fill={c.white} />
            <Heart x={100} y={112} scale={0.9} fill={c.accent} />
            <Sparkles points={[[40, 60, 0.8], [164, 52, 0.6], [100, 40, 0.5]]} fill={c.gold} />
        </>
    ),
    heart: (c) => (
        <>
            <Shadow c={c} rx={40} />
            <path d="M40 150 160 62" stroke={c.gold} strokeWidth="5" strokeLinecap="round" />
            <path d="M40 150l-12-2 6 12zM46 146l-12-2 6 12z" fill={c.accent} />
            <path d={HEART} transform="translate(100 76) scale(3.4)" fill={c.accent} />
            <ellipse cx="80" cy="92" rx="9" ry="5" transform="rotate(-35 80 92)" fill={c.white} opacity="0.4" />
            <path d="M126 87 160 62" stroke={c.gold} strokeWidth="5" strokeLinecap="round" />
            <path d="M170 55l-20 3 10 13z" fill={c.gold} />
            <Heart x={40} y={56} scale={0.7} fill={c.light} />
            <Heart x={164} y={118} scale={0.55} fill={c.light} />
            <Sparkles points={[[150, 30, 0.7], [44, 110, 0.5]]} fill={c.gold} />
        </>
    ),
    sunset: (c) => (
        <>
            <circle cx="100" cy="118" r="40" fill={c.gold} opacity="0.25" />
            <circle cx="100" cy="118" r="32" fill={c.gold} />
            <path d="M20 170c30-50 60-42 84-20 20-18 52-32 80 20z" fill={c.accent} />
            <path d="M16 172c44-32 94-22 124 0z" fill={c.dark} />
            <path d="M58 66q6-6 12 0 6-6 12 0M118 52q4-4 8 0 4-4 8 0" stroke={c.dark} strokeWidth="2.5" fill="none" strokeLinecap="round" />
            <rect x="138" y="80" width="34" height="5" rx="2.5" fill={c.white} opacity="0.7" />
            <rect x="30" y="96" width="26" height="5" rx="2.5" fill={c.white} opacity="0.7" />
        </>
    ),
    gamepad: (c) => (
        <>
            <Shadow c={c} rx={56} />
            <path d="M52 108c0-16 12-20 28-20h40c16 0 28 4 28 20l12 38c4 14-12 22-22 10l-12-16H74l-12 16c-10 12-26 4-22-10z" fill={c.accent} />
            <path d="M60 104c2-6 8-8 16-8" stroke={c.white} strokeWidth="3" opacity="0.35" fill="none" strokeLinecap="round" />
            <rect x="66" y="104" width="8" height="24" rx="2" fill={c.white} />
            <rect x="58" y="112" width="24" height="8" rx="2" fill={c.white} />
            <circle cx="126" cy="106" r="5" fill={c.gold} />
            <circle cx="138" cy="116" r="5" fill={c.light} />
            <circle cx="126" cy="126" r="5" fill={c.white} />
            <circle cx="114" cy="116" r="5" fill={c.soft} />
            <rect x="92" y="110" width="7" height="3" rx="1.5" fill={c.dark} opacity="0.5" />
            <rect x="101" y="110" width="7" height="3" rx="1.5" fill={c.dark} opacity="0.5" />
            {[[90, 50], [96, 50], [84, 56], [90, 56], [96, 56], [102, 56], [90, 62], [96, 62], [93, 68]].map(([x, y]) => (
                <rect key={`${x}-${y}`} x={x} y={y} width="6" height="6" fill={c.accent} opacity="0.75" />
            ))}
            <Sparkles points={[[46, 60, 0.7], [156, 52, 0.8], [168, 92, 0.5]]} fill={c.gold} />
        </>
    ),
    graduation: (c) => (
        <>
            <Shadow c={c} rx={60} />
            <g transform="rotate(-18 131 137)">
                <rect x="92" y="128" width="78" height="18" rx="9" fill={c.white} />
                <ellipse cx="170" cy="137" rx="4" ry="9" fill={c.light} />
                <rect x="126" y="128" width="10" height="18" fill={c.accent} />
            </g>
            <path d="M66 98v26c0 12 68 12 68 0V98z" fill={c.accent} />
            <path d="M100 62l60 26-60 26-60-26z" fill={c.dark} />
            <path d="M100 68 52 88" stroke={c.white} strokeWidth="2" opacity="0.25" />
            <circle cx="100" cy="88" r="4" fill={c.gold} />
            <path d="M100 88c20 4 38 8 46 12v30" stroke={c.gold} strokeWidth="3" fill="none" />
            <rect x="142" y="128" width="8" height="16" rx="2" fill={c.gold} />
            <Sparkles points={[[40, 50, 0.8], [164, 50, 0.6], [44, 140, 0.5]]} fill={c.gold} />
        </>
    ),
    medal: (c) => (
        <>
            <Shadow c={c} rx={36} />
            <path d="M70 34h24l16 60H86z" fill={c.accent} />
            <path d="M130 34h-24L90 94h24z" fill={c.light} />
            <rect x="90" y="88" width="20" height="10" rx="3" fill={c.goldDark} />
            <circle cx="100" cy="128" r="34" fill={c.gold} />
            <circle cx="100" cy="128" r="25" fill={c.goldDark} />
            <path d={STAR} transform="translate(100 128) scale(1.5)" fill={c.white} />
            <path d="M74 114c4-8 10-14 18-16" stroke={c.white} strokeWidth="3" opacity="0.45" fill="none" strokeLinecap="round" />
            <Sparkles points={[[44, 100, 0.8], [158, 92, 0.7], [150, 158, 0.5]]} fill={c.gold} />
        </>
    ),
    books: (c) => (
        <>
            <Shadow c={c} rx={64} />
            {[
                [44, 144, 112, c.accent],
                [54, 124, 96, c.gold],
                [48, 104, 100, c.light],
            ].map(([x, y, width, fill]) => (
                <g key={y}>
                    <rect x={x} y={y} width={width} height="20" rx="4" fill={fill} />
                    <rect x={x + width - 12} y={y + 4} width="9" height="12" rx="1" fill={c.white} />
                    <rect x={x + 10} y={y + 4} width="4" height="12" fill={c.dark} opacity="0.25" />
                </g>
            ))}
            <path d="M112 80c0-6 4-10 8-12" stroke={c.brown} strokeWidth="3" strokeLinecap="round" fill="none" />
            <ellipse cx="124" cy="72" rx="8" ry="4" transform="rotate(-25 124 72)" fill={c.leaf} />
            <circle cx="112" cy="92" r="13" fill={c.accent} />
            <ellipse cx="106" cy="88" rx="3" ry="5" fill={c.white} opacity="0.4" />
            <g transform="rotate(-30 66 80)">
                <rect x="60" y="50" width="10" height="50" rx="2" fill={c.gold} />
                <path d="M60 100h10l-5 10z" fill={c.brown} />
                <rect x="60" y="50" width="10" height="7" fill={c.accent} />
            </g>
            <Sparkles points={[[160, 60, 0.8], [40, 64, 0.5]]} fill={c.gold} />
        </>
    ),
    tulips: (c) => (
        <>
            <Shadow c={c} rx={40} />
            <path d="M100 126V72M82 126c0-22-6-30-10-40M118 126c0-22 6-30 10-40" stroke={c.leafDark} strokeWidth="4" fill="none" strokeLinecap="round" />
            <path d="M100 126c-12-14-14-26-10-34 8 10 12 20 10 34zM100 126c12-14 14-26 10-34-8 10-12 20-10 34z" fill={c.leaf} />
            <path d={TULIP} transform="translate(72 82) rotate(-12)" fill={c.light} />
            <path d={TULIP} transform="translate(100 68)" fill={c.gold} />
            <path d={TULIP} transform="translate(128 82) rotate(12)" fill={c.light} />
            <path d="M66 132h68l-8 38H74z" fill={c.accent} />
            <rect x="62" y="124" width="76" height="12" rx="4" fill={c.dark} />
            <Heart x={100} y={146} scale={0.6} fill={c.white} opacity={0.6} />
            <Sparkles points={[[40, 60, 0.7], [160, 50, 0.6], [164, 120, 0.5]]} fill={c.gold} />
        </>
    ),
    briefcase: (c) => (
        <>
            <Shadow c={c} rx={60} />
            <path d="M84 92V82a6 6 0 0 1 6-6h20a6 6 0 0 1 6 6v10" stroke={c.dark} strokeWidth="6" fill="none" />
            <rect x="46" y="92" width="108" height="72" rx="10" fill={c.accent} />
            <rect x="46" y="112" width="108" height="6" fill={c.dark} opacity="0.3" />
            <rect x="92" y="106" width="16" height="16" rx="3" fill={c.gold} />
            <path d="M56 100h30" stroke={c.white} strokeWidth="3" opacity="0.3" strokeLinecap="round" />
            {[[40, 56, c.accent, 20], [160, 60, c.gold, -30], [150, 36, c.light, 45], [54, 34, c.gold, 10]].map(([x, y, fill, rotate]) => (
                <rect key={`${x}-${y}`} x={x} y={y} width="6" height="12" rx="2" fill={fill} transform={`rotate(${rotate} ${x} ${y})`} />
            ))}
            <Sparkles points={[[100, 46, 0.9], [170, 110, 0.5]]} fill={c.gold} />
        </>
    ),
    sunflower: (c) => (
        <>
            <Shadow c={c} rx={34} />
            <path d="M56 104 100 172l44-68z" fill={c.light} />
            <ellipse cx="62" cy="112" rx="16" ry="6" transform="rotate(-40 62 112)" fill={c.leaf} />
            <ellipse cx="138" cy="112" rx="16" ry="6" transform="rotate(40 138 112)" fill={c.leaf} />
            {[[74, 96, 0.7], [126, 96, 0.7], [100, 78, 1]].map(([x, y, scale]) => (
                <g key={x} transform={`translate(${x} ${y}) scale(${scale})`}>
                    {Array.from({ length: 12 }, (_, petal) => (
                        <ellipse key={petal} cx="0" cy="-20" rx="6" ry="12" transform={`rotate(${petal * 30})`} fill={c.gold} />
                    ))}
                    <circle r="12" fill={c.brown} />
                    <circle cx="-3" cy="-3" r="2" fill={c.goldDark} opacity="0.7" />
                    <circle cx="4" cy="2" r="2" fill={c.goldDark} opacity="0.7" />
                </g>
            ))}
            <path d="M56 104 100 172l20-62z" fill={c.accent} />
            <ellipse cx="90" cy="150" rx="10" ry="6" transform="rotate(-25 90 150)" fill={c.gold} />
            <ellipse cx="110" cy="150" rx="10" ry="6" transform="rotate(25 110 150)" fill={c.gold} />
            <circle cx="100" cy="152" r="4" fill={c.goldDark} />
        </>
    ),
    plane: (c) => (
        <>
            <path d="M24 152c32 0 42-40 76-38" stroke={c.accent} strokeWidth="3" strokeDasharray="2 8" strokeLinecap="round" fill="none" />
            <Cloud x={56} y={70} fill={c.white} />
            <Cloud x={160} y={146} scale={0.8} fill={c.white} />
            <g transform="translate(-40 -28) scale(1.3) rotate(-15 128 95)">
                <path d="M116 96l20-30h10l-10 32z" fill={c.accent} />
                <path d="M92 92l-8-20h10l10 16z" fill={c.accent} />
                <path d="M90 96c0-6 6-10 14-10h56c10 0 16 6 8 12-8 6-18 6-28 6h-36c-8 0-14-2-14-8z" fill={c.white} />
                <rect x="100" y="98" width="56" height="3" rx="1.5" fill={c.accent} opacity="0.6" />
                {[112, 122, 132, 142].map((x) => (
                    <circle key={x} cx={x} cy="92" r="2.5" fill={c.light} />
                ))}
                <path d="M118 100l18 22h10l-12-22z" fill={c.dark} />
            </g>
            <Heart x={160} y={44} scale={0.7} fill={c.accent} opacity={0.7} />
            <Sparkles points={[[40, 36, 0.6], [176, 84, 0.5]]} fill={c.gold} />
        </>
    ),
};

/**
 * Emoji tema (diatur admin) dipetakan ke ilustrasi, sehingga template baru langsung ikut tanpa data tambahan.
 */
const EMOJI_SCENES = {
    '🎂': 'cake',
    '🧁': 'cupcake',
    '🎉': 'popper',
    '🎊': 'popper',
    '👑': 'crown',
    '🎁': 'gifts',
    '💌': 'letter',
    '✉': 'letter',
    '🌹': 'rose',
    '🕰': 'clock',
    '⏰': 'clock',
    '🧢': 'cap',
    '🏡': 'house',
    '🏠': 'house',
    '🌙': 'moon',
    '🕌': 'mosque',
    '🚗': 'car',
    '☔': 'umbrella',
    '🏳': 'flag',
    '🌈': 'rainbow',
    '💘': 'heart',
    '💖': 'heart',
    '❤': 'heart',
    '🌇': 'sunset',
    '🌅': 'sunset',
    '🎮': 'gamepad',
    '🎓': 'graduation',
    '🏅': 'medal',
    '🏆': 'medal',
    '📚': 'books',
    '🌷': 'tulips',
    '💐': 'tulips',
    '💼': 'briefcase',
    '🌻': 'sunflower',
    '✈': 'plane',
};

export function sceneFor(theme) {
    const emoji = (theme?.emoji ?? '').replace(/️/g, '');

    return EMOJI_SCENES[emoji] ?? 'letter';
}

/**
 * Ilustrasi tema kartu, mengikuti warna aksen tema.
 */
export default function Illustration({ theme, className = '' }) {
    const accent = theme.accent;
    const colors = {
        accent,
        dark: mixHex(accent, '#1f1020', 0.45),
        light: mixHex(accent, '#ffffff', 0.45),
        soft: theme.soft ?? mixHex(accent, '#ffffff', 0.8),
        white: '#fffaf6',
        gold: '#f7b733',
        goldDark: '#d9921c',
        leaf: '#6aa476',
        leafDark: '#3f7a50',
        brown: '#7a4a24',
    };

    return (
        <svg viewBox="0 0 200 200" className={className} aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            {SCENES[sceneFor(theme)](colors)}
        </svg>
    );
}
