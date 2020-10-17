import { SCOPED_OBJECTS } from '../config';

import Files from './Files';
import Counters from './Counters';
import Constants from './Constants';
import Questions from './Questions';

export const EDITORS_FIELDS = {
  COPYRIGHT: 'Copyright',
  COUNTER_STATUS: 'Counter Status',
  DESCRIPTION: 'Description',
  FEEDBACK: 'Feedback',
  FILE_SIZE: 'File Size',
  FILE_TYPE: 'File Type',
  HEIGHT: 'Height',
  ID: 'Id',
  LAYOUT_TYPE: 'Layout Type',
  NAME: 'Name',
  ORIGIN_URL: 'Origin URL',
  PARENT: 'Parent',
  PLACEHOLDER: 'Placeholder',
  QUESTION_TYPES: 'Question Types',
  RESOURCE_URL: 'Resource Url',
  RESPONSES: 'Responses',
  SCOPE_LEVEL: 'Scope Level',
  SCOPED_OBJECT_STATUS: 'Status',
  SHOW_ANSWER: 'Show Answer',
  SHOW_SUBMIT: 'Show Submit',
  STARTING_VALUE: 'Starting Value (optional)',
  STEM: 'Stem',
  TEXT: 'Text',
  TYPE: 'Type',
  VISIBLE: 'Visible',
  WIDTH: 'Width',
  WIKI: 'Wiki',
};

export const SCOPED_OBJECTS_MAPPING = {
  [SCOPED_OBJECTS.FILE.toLowerCase()]: Files,
  [SCOPED_OBJECTS.COUNTER.toLowerCase()]: Counters,
  [SCOPED_OBJECTS.CONSTANT.toLowerCase()]: Constants,
  [SCOPED_OBJECTS.QUESTION.toLowerCase()]: Questions,
};
