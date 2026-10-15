import Icon from '@/Components/Icon';
import { useState } from 'react';
import { Confetti, SceneCard, SceneLabel } from './shared';

export default function Quiz({ values }) {
    const questions = values.questions ?? [];
    const [index, setIndex] = useState(0);
    const [picked, setPicked] = useState(null);
    const [score, setScore] = useState(0);

    if (questions.length === 0) {
        return <SceneCard><p className="text-body-sm text-on-surface-variant">Belum ada pertanyaan kuis.</p></SceneCard>;
    }

    if (index >= questions.length) {
        return (
            <SceneCard className="relative items-center text-center">
                {score === questions.length && <Confetti />}
                <Icon name="emoji_events" size={40} className="text-tertiary" />
                <p className="font-display text-headline-sm text-on-surface">
                    Skor {score}/{questions.length}
                </p>
                <p className="text-body-sm text-on-surface-variant">
                    {score === questions.length ? values.success_message || 'Kamu memang paling kenal aku!' : 'Hampir! Yang penting kamu tetap juara di hatiku.'}
                </p>
            </SceneCard>
        );
    }

    const question = questions[index];

    const choose = (optionIndex) => {
        if (picked !== null) {
            return;
        }

        setPicked(optionIndex);

        if (optionIndex === Number(question.answer)) {
            setScore((value) => value + 1);
        }

        setTimeout(() => {
            setPicked(null);
            setIndex((value) => value + 1);
        }, 900);
    };

    return (
        <SceneCard>
            <SceneLabel icon="quiz">
                Kuis {index + 1}/{questions.length}
            </SceneLabel>
            <p className="font-display text-headline-sm text-on-surface">{question.question}</p>
            <div className="flex flex-col gap-2">
                {question.options.map((option, optionIndex) => {
                    const isAnswer = optionIndex === Number(question.answer);
                    const tone =
                        picked === null
                            ? 'bg-surface-container-low'
                            : isAnswer
                              ? 'bg-success text-white'
                              : picked === optionIndex
                                ? 'bg-error-container text-on-error-container'
                                : 'bg-surface-container-low opacity-60';

                    return (
                        <button
                            key={optionIndex}
                            type="button"
                            onClick={() => choose(optionIndex)}
                            className={`rounded-2xl px-4 py-3 text-left text-label-lg transition-colors ${tone}`}
                        >
                            {option}
                        </button>
                    );
                })}
            </div>
        </SceneCard>
    );
}
