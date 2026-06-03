import { ChangeEvent, ClipboardEvent, KeyboardEvent, useCallback, useEffect, useRef } from 'react';

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
  /** Inline styles applied to the wrapper. */
  style?: React.CSSProperties;
  /** Inline styles applied to each box. */
  boxStyle?: React.CSSProperties;
  /** aria-label for the whole control. Default "One-time code". */
  ariaLabel?: string;
  /** Auto-focus the first box on mount. Default true. */
  autoFocus?: boolean;
}

/**
 * Six-box (or N-box) OTP input. Handles paste, backspace, arrow nav,
 * and auto-advance like the iOS / Android native code field.
 *
 * Pairs with `inputMode="numeric"` and `autoComplete="one-time-code"` so iOS
 * SMS auto-fill and Chrome credentials-manager work out of the box.
 *
 * @example
 *   <OtpInput length={6} value={code} onChange={setCode}
 *             onComplete={(c) => verify({ phone, code: c })} />
 */
export function OtpInput({
  length = 6,
  value,
  onChange,
  onComplete,
  disabled,
  style,
  boxStyle,
  ariaLabel = 'One-time code',
  autoFocus = true,
}: OtpInputProps) {
  const refs = useRef<Array<HTMLInputElement | null>>([]);

  useEffect(() => {
    if (autoFocus && refs.current[0]) refs.current[0].focus();
  }, [autoFocus]);

  useEffect(() => {
    if (value.length === length && onComplete) onComplete(value);
  }, [value, length, onComplete]);

  const setAt = useCallback((i: number, char: string) => {
    const cur = value.padEnd(length, ' ');
    const next = (cur.substring(0, i) + char + cur.substring(i + 1)).trimEnd();
    onChange(next.replace(/\s/g, ''));
  }, [value, length, onChange]);

  const handleChange = (i: number) => (e: ChangeEvent<HTMLInputElement>) => {
    const raw = e.target.value.replace(/\D/g, '');
    if (!raw) {
      setAt(i, '');
      return;
    }
    // If user typed/pasted multiple chars in one box, distribute.
    if (raw.length > 1) {
      const filled = (value + raw).replace(/\D/g, '').slice(0, length);
      onChange(filled);
      const focusIdx = Math.min(filled.length, length - 1);
      refs.current[focusIdx]?.focus();
      return;
    }
    setAt(i, raw);
    if (i < length - 1) refs.current[i + 1]?.focus();
  };

  const handleKeyDown = (i: number) => (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace') {
      if (value[i]) {
        setAt(i, '');
      } else if (i > 0) {
        refs.current[i - 1]?.focus();
        setAt(i - 1, '');
      }
      e.preventDefault();
    } else if (e.key === 'ArrowLeft' && i > 0) {
      refs.current[i - 1]?.focus();
      e.preventDefault();
    } else if (e.key === 'ArrowRight' && i < length - 1) {
      refs.current[i + 1]?.focus();
      e.preventDefault();
    }
  };

  const handlePaste = (e: ClipboardEvent<HTMLInputElement>) => {
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, length);
    if (!pasted) return;
    e.preventDefault();
    onChange(pasted);
    const focusIdx = Math.min(pasted.length, length - 1);
    refs.current[focusIdx]?.focus();
  };

  const wrapStyle: React.CSSProperties = {
    display: 'flex',
    gap: 8,
    ...style,
  };
  const defaultBoxStyle: React.CSSProperties = {
    width: 44,
    height: 52,
    textAlign: 'center',
    fontSize: 22,
    fontWeight: 600,
    border: '1px solid #d4d4d8',
    borderRadius: 8,
    outline: 'none',
    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
    ...boxStyle,
  };

  return (
    <div style={wrapStyle} role="group" aria-label={ariaLabel}>
      {Array.from({ length }).map((_, i) => (
        <input
          key={i}
          ref={(el) => { refs.current[i] = el; }}
          type="text"
          inputMode="numeric"
          autoComplete={i === 0 ? 'one-time-code' : 'off'}
          maxLength={1}
          pattern="[0-9]*"
          value={value[i] || ''}
          onChange={handleChange(i)}
          onKeyDown={handleKeyDown(i)}
          onPaste={handlePaste}
          disabled={disabled}
          aria-label={`Digit ${i + 1} of ${length}`}
          style={defaultBoxStyle}
        />
      ))}
    </div>
  );
}
