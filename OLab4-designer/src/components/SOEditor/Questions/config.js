export const LAYOUT_TYPES = [
  'Vertical', 'Horizontal',
];

export const PICKER_QUESTION_TYPES = {
  3: 'Multiple Choice',
  4: 'Single Choice',
};

// NOTE: as more types come online, the relative in-array
// indexes will change and code has to be modified to accomodate
export const QUESTION_TYPES = {
  // 1: 'Single Line Text',
  2: 'Multi-Line Text',
  3: 'Multiple Choice',
  4: 'Single Choice',
  // 5: 'Slider',
  // 6: 'Drag and Drop',
  7: 'Script Concordance Testing',
  // 8: 'Situational Judgement Testing',
  // 9: 'Cumulative',
  // 10: 'Rich Text',
  // 11: 'Turk Talk',
  // 12: 'DropDown',
  // 13: 'Multiple-choice grid',
  // 14: 'Pick-choice grid'
  // 2: 'Multi-line':
  // 4: 'Pick Choice':
  // 5: 'Multiple Choice':
  // 7: 'Script Concordance Testing':
};

export const DEFAULT_WIDTH = {
  MIN: 10,
  MAX: 60,
  STEP: 10,
};

export const DEFAULT_HEIGHT = {
  MIN: 2,
  MAX: 8,
  STEP: 2,
};
