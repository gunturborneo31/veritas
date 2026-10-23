export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex min-h-11 items-center justify-center rounded-full border border-transparent bg-error px-5 py-2.5 text-label-lg text-on-error transition duration-150 ease-in-out hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-error/40 focus:ring-offset-2 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
