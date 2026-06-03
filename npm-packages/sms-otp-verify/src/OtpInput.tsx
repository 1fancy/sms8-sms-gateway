import {
  CSSProperties,
  ChangeEvent,
  ClipboardEvent,
  HTMLAttributes,
  KeyboardEvent,
  ReactNode,
  useCallback,
  useEffect,
  useRef,
} from 'react';

/** Visual state passed to renderInput / className resolvers. */
export interface OtpInputState {
  /** Whether this box currently has the cursor. */
  focused: boolean;
  /** Whether this box has a digit in it. */
  filled: boolean;
  /** Index of this box (0-based). */
  index: number;
  /** Whether the whole control is disabled. */
  disabled: boolean;
  /** Whether the whole control is in an error state. */
  error: boolean;
}

export interface OtpInputProps {
  /** Number of digit boxes. Default 6. */
  length?: number;
  /** Current value (controlled). */
  value: string;
  /** Called whenever the user types, pastes, or deletes. */
  onChange: (value: string) => void;
  /** Called once `value.length === length`. Useful for auto-submit. */
  onComplete?: (value: string) => void;
  /** Disable all boxes. */
  disabled?: boolean;
  /** Show error visual state (red border) without preventing typing. */
  error?: boolean;
  /** Mask the digits as `•` instead of showing them. Default false. */
  mask?: boolean;
  /** Character to use when `mask` is true. Default `•`. */
  maskChar?: string;
  /** Restrict input to digits only. Default true. Set false to allow letters. */
  digitsOnly?: boolean;
  /** Auto-focus the first box on mount. Default true. */
  autoFocus?: boolean;
  /** RTL layout (right-to-left). Default false. */
  rtl?: boolean;

  /** Inline styles applied to the wrapper. */
  style?: CSSProperties;
  /** Class name applied to the wrapper. */
  containerClassName?: string;
  /** Inline styles applied to each box (overridden by `inputClassName`). */
  boxStyle?: CSSProperties;
  /** Static class name applied to every box. */
  inputClassName?: string;
  /** Class name applied to a focused box (added on top of `inputClassName`). */
  focusedClassName?: string;
  /** Class name applied to a filled (non-empty) box. */
  filledClassName?: string;
  /** Class name applied when the whole control is in an error state. */
  errorClassName?: string;

  /** Optional render-prop for full control over each input box. */
  renderInput?: (props: OtpRenderInputProps, state: OtpInputState) => ReactNode;
  /** Optional separator rendered between every two boxes. Can be a string or a function. */
  renderSeparator?: ((index: number) => ReactNode) | ReactNode;

  /** aria-label for the whole control. Default "One-time code". */
  ariaLabel?: string;
  /** Placeholder character shown in empty boxes. Default empty. */
  placeholder?: string;
}

/** Props the consumer should spread on their custom input. */
export type OtpRenderInputProps = HTMLAttributes<HTMLInputElement> & {
  ref: (el: HTMLInputElement | null) => void;
  type: 'text' | 'password';
  inputMode: 'numeric' | 'text';
  autoComplete: 'one-time-code' | 'off';
  maxLength: 1;
  value: string;
  disabled: boolean;
  'aria-label': string;
  pattern: string;
};

/**
 * N-box OTP input. Handles paste (auto-fills all boxes), backspace, arrow nav,
 * autoAdvance. Pairs with `inputMode="numeric"` + `autoComplete="one-time-code"`
 * for iOS SMS auto-fill and Chrome credentials-manager.
 *
 * Supports separators, RTL, masked / hidden codes, error states, and a full
 * `renderInput` render-prop for total style control.
 *
 * @example
 *   <OtpInput value={code} onChange={setCode}
 *             onComplete={(c) => verify(c)}
 *             renderSeparator={(i) => i === 2 ? <span>-</span> : null} />
 */
export function OtpInput({
  length = 6,
  value,
  onChange,
  onComplete,
  disabled = false,
  error = false,
  mask = false,
  maskChar = '•',
  digitsOnly = true,
  autoFocus = true,
  rtl = false,

  style,
  containerClassName,
  boxStyle,
  inputClassName,
  focusedClassName,
  filledClassName,
  errorClassName,

  renderInput,
  renderSeparator,

  ariaLabel = 'One-time code',
  placeholder = '',
}: OtpInputProps) {
  const refs = useRef<Array<HTMLInputElement | null>>([]);
  const focusedRef = useRef<number>(-1);

  useEffect(() => {
    if (autoFocus && !disabled && refs.current[0]) refs.current[0].focus();
  }, [autoFocus, disabled]);

  useEffect(() => {
    if (value.length === length && onComplete) onComplete(value);
  }, [value, length, onComplete]);

  const sanitize = useCallback((raw: string) => {
    return digitsOnly ? raw.replace(/\D/g, '') : raw;
  }, [digitsOnly]);

  const setAt = useCallback((i: number, char: string) => {
    const cur = value.padEnd(length, ' ');
    const next = (cur.substring(0, i) + char + cur.substring(i + 1)).trimEnd();
    onChange(next.replace(/\s/g, ''));
  }, [value, length, onChange]);

  const focusBox = useCallback((i: number) => {
    if (i >= 0 && i < length) refs.current[i]?.focus();
  }, [length]);

  const handleChange = (i: number) => (e: ChangeEvent<HTMLInputElement>) => {
    const raw = sanitize(e.target.value);
    if (!raw) {
      setAt(i, '');
      return;
    }
    if (raw.length > 1) {
      // Multi-char paste landed in one box — distribute across remaining boxes.
      const filled = sanitize(value + raw).slice(0, length);
      onChange(filled);
      focusBox(Math.min(filled.length, length - 1));
      return;
    }
    setAt(i, raw);
    if (i < length - 1) focusBox(i + 1);
  };

  const handleKeyDown = (i: number) => (e: KeyboardEvent<HTMLInputElement>) => {
    const prev = rtl ? i + 1 : i - 1;
    const next = rtl ? i - 1 : i + 1;
    if (e.key === 'Backspace') {
      if (value[i]) {
        setAt(i, '');
      } else if (prev >= 0 && prev < length) {
        focusBox(prev);
        setAt(prev, '');
      }
      e.preventDefault();
    } else if (e.key === 'ArrowLeft') {
      focusBox(rtl ? next : prev);
      e.preventDefault();
    } else if (e.key === 'ArrowRight') {
      focusBox(rtl ? prev : next);
      e.preventDefault();
    } else if (e.key === 'Home') {
      focusBox(0);
      e.preventDefault();
    } else if (e.key === 'End') {
      focusBox(length - 1);
      e.preventDefault();
    } else if (e.key === 'Delete') {
      setAt(i, '');
      e.preventDefault();
    }
  };

  const handlePaste = (e: ClipboardEvent<HTMLInputElement>) => {
    const pasted = sanitize(e.clipboardData.getData('text')).slice(0, length);
    if (!pasted) return;
    e.preventDefault();
    onChange(pasted);
    focusBox(Math.min(pasted.length, length - 1));
  };

  const handleFocus = (i: number) => () => {
    focusedRef.current = i;
    refs.current[i]?.select();
  };
  const handleBlur = () => {
    focusedRef.current = -1;
  };

  const wrapStyle: CSSProperties = {
    display: 'flex',
    gap: 8,
    direction: rtl ? 'rtl' : 'ltr',
    ...style,
  };
  const defaultBoxStyle: CSSProperties = {
    width: 44,
    height: 52,
    textAlign: 'center',
    fontSize: 22,
    fontWeight: 600,
    border: `1px solid ${error ? '#ef4444' : '#d4d4d8'}`,
    borderRadius: 8,
    outline: 'none',
    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
    color: '#0f0f1c',
    background: disabled ? '#f4f4f5' : '#ffffff',
    transition: 'border-color 0.15s, box-shadow 0.15s',
    ...boxStyle,
  };

  const boxes: ReactNode[] = [];
  for (let i = 0; i < length; i++) {
    const ch = value[i] || '';
    const display = mask && ch ? maskChar : ch;
    const focused = focusedRef.current === i;
    const filled = !!ch;

    const cls = [
      inputClassName,
      focused ? focusedClassName : undefined,
      filled  ? filledClassName  : undefined,
      error   ? errorClassName   : undefined,
    ].filter(Boolean).join(' ') || undefined;

    const baseProps: OtpRenderInputProps = {
      ref: (el) => { refs.current[i] = el; },
      type: mask ? 'password' : 'text',
      inputMode: digitsOnly ? 'numeric' : 'text',
      autoComplete: i === 0 ? 'one-time-code' : 'off',
      maxLength: 1,
      pattern: digitsOnly ? '[0-9]*' : '.*',
      value: display,
      disabled,
      onChange: handleChange(i),
      onKeyDown: handleKeyDown(i),
      onPaste: handlePaste,
      onFocus: handleFocus(i),
      onBlur: handleBlur,
      placeholder,
      className: cls,
      style: inputClassName ? undefined : defaultBoxStyle,
      'aria-label': `Digit ${i + 1} of ${length}`,
      'aria-invalid': error || undefined,
    } as OtpRenderInputProps;

    const state: OtpInputState = { focused, filled, index: i, disabled, error };

    boxes.push(
      <span key={`b${i}`} style={{ display: 'inline-block' }}>
        {renderInput ? renderInput(baseProps, state) : <input {...baseProps} />}
      </span>
    );

    if (renderSeparator && i < length - 1) {
      const sep = typeof renderSeparator === 'function'
        ? renderSeparator(i)
        : renderSeparator;
      if (sep != null) boxes.push(<span key={`s${i}`}>{sep}</span>);
    }
  }

  return (
    <div
      style={wrapStyle}
      className={containerClassName}
      role="group"
      aria-label={ariaLabel}
      aria-invalid={error || undefined}
      data-otp-input=""
    >
      {boxes}
    </div>
  );
}
