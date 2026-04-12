const allowedKeyboardControlKeys = [
  'ArrowRight',
  'ArrowLeft',
  'Backspace',
  'Delete',
  'Tab',
  'Home',
  'End',
  'PageUp',
  'PageDown',
  'F5'
];
const allowedKeyboardControlCombinationKeys = ['a', 'c', 'v', 'z', 'y', 'x', 'r'];
const originalAllowedKeyboardNumberKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
let allowedKeyboardNumberKeys = originalAllowedKeyboardNumberKeys;

export function useOnFocus(event) {
  event.target.select();
}

export function useOnPaste(event, decimals, allowedKeys = []) {
  if (allowedKeys?.length) {
    allowedKeyboardNumberKeys = [...allowedKeyboardNumberKeys, ...allowedKeys];
  } else {
    allowedKeyboardNumberKeys = originalAllowedKeyboardNumberKeys;
  }

  return validatePastedText(event, decimals);
}

export function useOnKeyDown(event, decimals, allowedKeys = []) {
  if (allowedKeys?.length) {
    allowedKeyboardNumberKeys = [...allowedKeyboardNumberKeys, ...allowedKeys];
  } else {
    allowedKeyboardNumberKeys = originalAllowedKeyboardNumberKeys;
  }

  return validateKeyDownInput(event, decimals);
}

function validatePastedText(event, decimals) {
  if (decimals) {
    allowedKeyboardNumberKeys = [...allowedKeyboardNumberKeys, '.', ','];
  }
  const pastedText = event.clipboardData.getData('text');
  const index = [...pastedText].findIndex((char) => !allowedKeyboardNumberKeys.includes(char));

  if (index !== -1) {
    event.preventDefault();

    return false;
  }

  return event.target.value;
}

function validateKeyDownInput(event, decimals) {
  if (
    (!getAllowedKeyboardControlKeys(event) && !getAllowedKeyboardNumberKeys(event)) ||
    (getCtrlOrMetaKey(event) && !allowedKeyboardControlCombinationKeys.includes(event.key))
  ) {
    if (insertDecimalSeparatorIsAllowed(event, decimals)) {
      return event.target.value;
    }

    event.preventDefault();
  }

  if (!decimals) {
    return validateKeyboardKeys(event);
  } else {
    return validateNumbersAndDecimalsInput(event, decimals);
  }
}

function validateNumbersAndDecimalsInput(event, decimals) {
  if (
    (!getAllowedKeyboardControlKeys(event) && !getAllowedKeyboardNumberKeys(event)) ||
    (getAllowedKeyboardNumberKeys(event) && !insertNumberAndDecimalIsAllowed(event, decimals))
  ) {
    event.preventDefault();
  }

  return event.target.value;
}

function validateKeyboardKeys(event) {
  if (!getAllowedKeyboardControlKeys(event) && !getAllowedKeyboardNumberKeys(event)) {
    event.preventDefault();
  }

  return event.target.value;
}

function getAllowedKeyboardControlKeys(event) {
  return (
    allowedKeyboardControlKeys.includes(event.key) ||
    (getCtrlOrMetaKey(event) && allowedKeyboardControlCombinationKeys.includes(event.key))
  );
}

function getAllowedKeyboardNumberKeys(event) {
  return allowedKeyboardNumberKeys.includes(event.key);
}

function getInputSelection(event) {
  return event.target.value.substring(event.target.selectionStart, event.target.selectionEnd);
}

function getCursorIsBeforeDecimalSeparator(event) {
  const cursor = event.target.selectionStart;
  const decimalIndex = event.target.value.indexOf(',');

  if (decimalIndex === -1) {
    return true;
  }

  return cursor <= decimalIndex;
}

function insertDecimalSeparatorIsAllowed(event, decimals = 0) {
  let separatorIsAllowed = false;

  if (event.key === ',' && !!decimals) {
    separatorIsAllowed = event.target.value.length - event.target.selectionEnd <= decimals;
  }

  return (
    !!decimals &&
    event.key === ',' &&
    (!event.target.value.toString().includes(',') || getInputSelection(event).includes(',')) &&
    separatorIsAllowed
  );
}

function insertNumberAndDecimalIsAllowed(event, decimals = 0) {
  const regexNumbersAndDecimals = new RegExp(`^[\\d.]*,\\d{${decimals},}$`, 'g');

  return (
    !regexNumbersAndDecimals.test(event.target.value) ||
    !!getInputSelection(event) ||
    getCursorIsBeforeDecimalSeparator(event)
  );
}

function getCtrlOrMetaKey(event) {
  return !isMac() ? event.ctrlKey : event.metaKey;
}

function isMac() {
  return /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform);
}
