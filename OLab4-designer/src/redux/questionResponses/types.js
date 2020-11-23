// @flow
export type QuestionResponse = {
  acl: string,
  created_at: string,
  description: string,
  feedback: string,
  from: string,
  id: number,
  is_correct: boolean,
  isDetailsFetching: boolean,
  name: string,
  order: number,
  parent_id: number,
  question_id: number,
  response: string,
  score: number,
  to: string,
  updated_At: string,
};

export type ScopedObjects = {
  [type: string]: Array<ScopedObject | ScopedObjectListItem>,
};

export type ScopedObjectsState = {
  ...ScopedObjects,
  isFetching: boolean,
  isCreating: boolean,
  isUpdating: boolean,
  isDeleting: boolean,
};

const RESPONSE_CREATE_REQUESTED = 'RESPONSE_CREATE_REQUESTED';
type QuestionResponseCreateRequested = {
  type: 'RESPONSE_CREATE_REQUESTED',
  scopedObjectData: QuestionResponse,
};

const RESPONSE_CREATE_FAILED = 'RESPONSE_CREATE_FAILED';
type QuestionResponseCreateFailed = {
  type: 'RESPONSE_CREATE_FAILED',
};

const RESPONSE_CREATE_SUCCEEDED = 'RESPONSE_CREATE_SUCCEEDED';
type QuestionResponseCreateSucceeded = {
  type: 'RESPONSE_CREATE_SUCCEEDED',
  scopedObjectId: number,
  scopedObjectData: QuestionResponse,
};

const RESPONSE_DELETE_REQUESTED = 'RESPONSE_DELETE_REQUESTED';
type QuestionResponseDeleteRequested = {
  type: 'RESPONSE_DELETE_REQUESTED',
  scopedObjectId: number,
};

const RESPONSE_DELETE_SUCCEEDED = 'RESPONSE_DELETE_SUCCEEDED';
type QuestionResponseDeleteSucceeded = {
  type: 'RESPONSE_DELETE_SUCCEEDED',
  scopedObjectIndex: number,
};

const RESPONSE_DELETE_FAILED = 'RESPONSE_DELETE_FAILED';
type QuestionResponseDeleteFailed = {
  type: 'RESPONSE_DELETE_FAILED',
};

const RESPONSE_UPDATE_REQUESTED = 'RESPONSE_UPDATE_REQUESTED';
type QuestionResponseUpdateRequested = {
  type: 'RESPONSE_UPDATE_REQUESTED',
  scopedObjectData: Question,
};

const RESPONSE_UPDATE_FULFILLED = 'RESPONSE_UPDATE_FULFILLED';
type QuestionResponseUpdateFulfilled = {
  type: 'RESPONSE_UPDATE_FULFILLED',
};

export type QuestionResponseActions =
  QuestionResponseCreateFailed |
  QuestionResponseCreateRequested |
  QuestionResponseCreateSucceeded |
  QuestionResponseDeleteFailed |
  QuestionResponseDeleteRequested |
  QuestionResponseDeleteSucceeded |
  QuestionResponseUpdateFulfilled |
  QuestionResponseUpdateRequested;

export {
  RESPONSE_CREATE_FAILED,
  RESPONSE_CREATE_REQUESTED,
  RESPONSE_CREATE_SUCCEEDED,
  RESPONSE_DELETE_FAILED,
  RESPONSE_DELETE_REQUESTED,
  RESPONSE_DELETE_SUCCEEDED,
  RESPONSE_UPDATE_FULFILLED,
  RESPONSE_UPDATE_REQUESTED,
};
