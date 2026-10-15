import Icon from '@/Components/Icon';

const inputClass =
    'w-full rounded-2xl border-0 bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary/40';

export function ListField({ field, value = [], onChange }) {
    const items = value ?? [];
    const max = field.max ?? 10;

    return (
        <div className="flex flex-col gap-2">
            {items.map((item, index) => (
                <div key={index} className="flex items-start gap-2">
                    <span className="mt-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-label-sm text-primary">{index + 1}</span>
                    <textarea
                        rows={2}
                        maxLength={field.item_max ?? 300}
                        value={item}
                        onChange={(event) => onChange(items.map((current, itemIndex) => (itemIndex === index ? event.target.value : current)))}
                        className={`${inputClass} resize-none`}
                    />
                    <button
                        type="button"
                        aria-label="Hapus"
                        onClick={() => onChange(items.filter((_, itemIndex) => itemIndex !== index))}
                        className="mt-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container"
                    >
                        <Icon name="delete_outline" size={18} />
                    </button>
                </div>
            ))}
            {items.length < max && (
                <button
                    type="button"
                    onClick={() => onChange([...items, ''])}
                    className="flex items-center justify-center gap-1 rounded-full bg-surface-container py-2 text-label-md text-primary"
                >
                    <Icon name="add" size={18} />
                    Tambah {field.label?.toLowerCase() ?? 'item'}
                </button>
            )}
        </div>
    );
}

export function QuizField({ field, value = [], onChange }) {
    const questions = value ?? [];
    const update = (index, patch) => onChange(questions.map((question, questionIndex) => (questionIndex === index ? { ...question, ...patch } : question)));

    return (
        <div className="flex flex-col gap-3">
            {questions.map((question, index) => (
                <div key={index} className="flex flex-col gap-2 rounded-2xl bg-surface-container-low p-3">
                    <div className="flex items-center justify-between">
                        <span className="text-label-sm text-primary">Pertanyaan {index + 1}</span>
                        <button type="button" onClick={() => onChange(questions.filter((_, questionIndex) => questionIndex !== index))} className="text-on-surface-variant">
                            <Icon name="delete_outline" size={18} />
                        </button>
                    </div>
                    <input
                        type="text"
                        maxLength={150}
                        value={question.question}
                        placeholder="Apa makanan favoritku?"
                        onChange={(event) => update(index, { question: event.target.value })}
                        className={inputClass}
                    />
                    {question.options.map((option, optionIndex) => (
                        <label key={optionIndex} className="flex items-center gap-2">
                            <input
                                type="radio"
                                name={`answer-${index}`}
                                checked={Number(question.answer) === optionIndex}
                                onChange={() => update(index, { answer: optionIndex })}
                                className="text-primary focus:ring-primary"
                            />
                            <input
                                type="text"
                                maxLength={60}
                                value={option}
                                placeholder={`Pilihan ${optionIndex + 1}`}
                                onChange={(event) =>
                                    update(index, { options: question.options.map((current, currentIndex) => (currentIndex === optionIndex ? event.target.value : current)) })
                                }
                                className={`${inputClass} py-2`}
                            />
                        </label>
                    ))}
                    {question.options.length < 4 && (
                        <button type="button" onClick={() => update(index, { options: [...question.options, ''] })} className="self-start text-label-sm text-primary">
                            + Tambah pilihan
                        </button>
                    )}
                    <p className="text-label-sm text-on-surface-variant">Pilih bulatan di samping jawaban yang benar.</p>
                </div>
            ))}
            {questions.length < (field.max ?? 5) && (
                <button
                    type="button"
                    onClick={() => onChange([...questions, { question: '', options: ['', ''], answer: 0 }])}
                    className="flex items-center justify-center gap-1 rounded-full bg-surface-container py-2 text-label-md text-primary"
                >
                    <Icon name="add" size={18} />
                    Tambah pertanyaan
                </button>
            )}
        </div>
    );
}
