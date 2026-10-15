import { Polaroid } from './shared';

export default function FramedPhoto({ values }) {
    return (
        <div className="w-full max-w-[300px]">
            <Polaroid image={values.photo} caption={values.caption} />
        </div>
    );
}
