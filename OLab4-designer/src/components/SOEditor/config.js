import { SCOPED_OBJECTS } from '../config';

import Files from './Files';
import Counters from './Counters';
import Constants from './Constants';
import Questions from './Questions';

export const EDITORS_FIELDS = {
  ID: 'ID',
  TYPE: 'Type',
  TEXT: 'Text',
  WIKI: 'Wiki',
  NAME: 'Name',
  STEM: 'Stem',
  WIDTH: 'Width',
  HEIGHT: 'Height',
  PARENT: 'Parent',
  VISIBLE: 'Visible',
  FEEDBACK: 'Feedback',
  FILE_TYPE: 'File Type',
  FILE_SIZE: 'File Size',
  COPYRIGHT: 'Copyright',
  ORIGIN_URL: 'Origin URL',
  DESCRIPTION: 'Description',
  SCOPE_LEVEL: 'Scope Level',
  PLACEHOLDER: 'Placeholder',
  LAYOUT_TYPE: 'Layout Type',
  SHOW_ANSWER: 'Show Answer',
  SHOW_SUBMIT: 'Show Submit',
  RESOURCE_URL: 'Resource Url',
  SCOPED_OBJECT_STATUS: 'Status',
  COUNTER_STATUS: 'Counter Status',
  QUESTION_TYPES: 'Question Types',
  STARTING_VALUE: 'Starting Value (optional)',
};

export const SCOPED_OBJECTS_MAPPING = {
  [SCOPED_OBJECTS.FILE.toLowerCase()]: Files,
  [SCOPED_OBJECTS.COUNTER.toLowerCase()]: Counters,
  [SCOPED_OBJECTS.CONSTANT.toLowerCase()]: Constants,
  [SCOPED_OBJECTS.QUESTION.toLowerCase()]: Questions,
};
