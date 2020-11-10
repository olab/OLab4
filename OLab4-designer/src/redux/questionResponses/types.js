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

export type QuestionResponseListItem = {
  id: number,
  ...QuestionResponse,
}

export type ScopedObjects = {
  [type: string]: Array<QuestionResponse | QuestionResponseListItem>,
};

export type ScopedObjectsState = {
  ...ScopedObjects,
  isFetching: boolean,
  isCreating: boolean,
  isUpdating: boolean,
  isDeleting: boolean,
};

const SCOPED_OBJECT_DETAILS_FULFILLED = 'RESPONSE_DETAILS_FULFILLED';
type ScopedObjectsDetailsFulfilled = {
  type: 'RESPONSE_DETAILS_FULFILLED',
  scopedObjectType: string,
  scopedObjectIndex: number,
  scopedObject: QuestionResponse,
};

const SCOPED_OBJECT_DETAILS_REQUESTED = 'RESPONSE_DETAILS_REQUESTED';
type ScopedObjectsDetailsRequested = {
  type: 'RESPONSE_DETAILS_REQUESTED',
  questionResponseId: number,
  questionResponseIndex: number,
  scopedObject: QuestionResponse,
};

const SCOPED_OBJECT_CREATE_REQUESTED = 'RESPONSE_CREATE_REQUESTED';
type ScopedObjectsCreateRequested = {
  type: 'RESPONSE_CREATE_REQUESTED',
  scopedObjectData: QuestionResponse,
};

const SCOPED_OBJECT_CREATE_FAILED = 'RESPONSE_CREATE_FAILED';
type ScopedObjectsCreateFailed = {
  type: 'RESPONSE_CREATE_FAILED',
};

const SCOPED_OBJECT_CREATE_SUCCEEDED = 'RESPONSE_CREATE_SUCCEEDED';
type ScopedObjectsCreateSucceeded = {
  type: 'RESPONSE_CREATE_SUCCEEDED',
  scopedObjectId: number,
  scopedObjectData: ScopedObjects,
};

const SCOPED_OBJECT_DELETE_REQUESTED = 'RESPONSE_DELETE_REQUESTED';
type ScopedObjectDeleteRequested = {
  type: 'RESPONSE_DELETE_REQUESTED',
  scopedObjectId: number,
};

const SCOPED_OBJECT_DELETE_SUCCEEDED = 'RESPONSE_DELETE_SUCCEEDED';
type ScopedObjectDeleteSucceeded = {
  type: 'RESPONSE_DELETE_SUCCEEDED',
  scopedObjectIndex: number,
};

const SCOPED_OBJECT_DELETE_FAILED = 'RESPONSE_DELETE_FAILED';
type ScopedObjectDeleteFailed = {
  type: 'RESPONSE_DELETE_FAILED',
};

const SCOPED_OBJECT_UPDATE_REQUESTED = 'RESPONSE_UPDATE_REQUESTED';
type ScopedObjectUpdateRequested = {
  type: 'RESPONSE_UPDATE_REQUESTED',
  questionResponseId: number,
  scopedObjectData: QuestionResponse,
};

const SCOPED_OBJECT_UPDATE_FULFILLED = 'RESPONSE_UPDATE_FULFILLED';
type ScopedObjectUpdateFulfilled = {
  type: 'RESPONSE_UPDATE_FULFILLED',
};

export type QuestionResponseActions =
  ScopedObjectsDetailsRequested | ScopedObjectsCreateRequested |
  ScopedObjectUpdateRequested | ScopedObjectsDetailsFulfilled |
  ScopedObjectDeleteSucceeded | ScopedObjectDeleteRequested |
  ScopedObjectsCreateSucceeded | ScopedObjectsCreateFailed |
  ScopedObjectDeleteFailed | ScopedObjectUpdateFulfilled;

export {
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
  SCOPED_OBJECT_DELETE_REQUESTED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
};
