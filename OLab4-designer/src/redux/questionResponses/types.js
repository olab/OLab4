// @flow
export type ScopedObjectBase = {
  id?: number,
  width?: number,
  height?: number,
  status?: number,
  visible?: number,
  fileSize?: number,
  layoutType?: number,
  questionType?: number,
  parentId: number | null,
  url?: string,
  name: string,
  stem?: string,
  path?: string,
  mime?: string,
  wiki?: string,
  value?: string,
  contents?: string,
  feedback?: string,
  widthType?: string,
  copyright?: string,
  scopeLevel: string,
  originUrl?: string,
  heightType?: string,
  startValue?: string,
  description: string,
  resourceUrl?: string,
  placeholder?: string,
  isEmbedded?: boolean,
  isShowAnswer?: boolean,
  isShowSubmit?: boolean,
};

export type ScopedObjectDetails = {
  outOf: number,
  value: string,
  prefix: string,
  suffix: string,
  scopeLevel: string,
  startValue: string,
  description: string,
};

export type ScopedObjectListItem = {
  id: number,
  ...ScopedObjectBase,
}

export type ScopedObject = {
  id: number,
  acl: string,
  name: string,
  wiki: string,
  scopeLevel: string,
  description: string,
  isShowEyeIcon: boolean,
  isDetailsFetching: boolean,
  details: null | ScopedObjectDetails,
};

export type ScopedObjects = {
  [type: string]: Array<ScopedObject | ScopedObjectListItem>,
};

export type ScopedObjectState = {
  ...ScopedObject,
  isFetching: boolean,
  isCreating: boolean,
  isUpdating: boolean,
  isDeleting: boolean,
};

const SCOPED_OBJECTS_REQUEST_FAILED = 'SCOPED_OBJECTS_REQUEST_FAILED';
type ScopedObjectRequestFailed = {
  type: 'SCOPED_OBJECTS_REQUEST_FAILED',
};

const SCOPED_OBJECTS_REQUEST_SUCCEEDED = 'SCOPED_OBJECTS_REQUEST_SUCCEEDED';
type ScopedObjectRequestSucceeded = {
  type: 'SCOPED_OBJECTS_REQUEST_SUCCEEDED',
  scopedObjectsData: ScopedObject,
};

const SCOPED_OBJECTS_REQUESTED = 'SCOPED_OBJECTS_REQUESTED';
type ScopedObjectRequested = {
  type: 'SCOPED_OBJECTS_REQUESTED',
};

const SCOPED_OBJECT_DETAILS_REQUESTED = 'SCOPED_OBJECT_DETAILS_REQUESTED';
type ScopedObjectDetailsRequested = {
  type: 'SCOPED_OBJECT_DETAILS_REQUESTED',
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectIndex: number,
  scopedObject: ScopedObject,
};

const SCOPED_OBJECT_DETAILS_FULFILLED = 'SCOPED_OBJECT_DETAILS_FULFILLED';
type ScopedObjectDetailsFulfilled = {
  type: 'SCOPED_OBJECT_DETAILS_FULFILLED',
  scopedObjectType: string,
  scopedObjectIndex: number,
  scopedObject: ScopedObject,
};

const SCOPED_OBJECT_CREATE_FAILED = 'SCOPED_OBJECT_CREATE_FAILED';
type ScopedObjectCreateFailed = {
  type: 'SCOPED_OBJECT_CREATE_FAILED',
};

const SCOPED_OBJECT_CREATE_SUCCEEDED = 'SCOPED_OBJECT_CREATE_SUCCEEDED';
type ScopedObjectCreateSucceeded = {
  type: 'SCOPED_OBJECT_CREATE_SUCCEEDED',
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectData: ScopedObject,
};

const SCOPED_OBJECT_CREATE_REQUESTED = 'SCOPED_OBJECT_CREATE_REQUESTED';
type ScopedObjectCreateRequested = {
  type: 'SCOPED_OBJECT_CREATE_REQUESTED',
  scopedObjectType: string,
  scopedObjectData: ScopedObject,
};

const SCOPED_OBJECT_UPDATE_REQUESTED = 'SCOPED_OBJECT_UPDATE_REQUESTED';
type ScopedObjectUpdateRequested = {
  type: 'SCOPED_OBJECT_UPDATE_REQUESTED',
  scopedObjectId: number,
  scopedObjectType: string,
  scopedObjectData: ScopedObject,
};

const SCOPED_OBJECT_UPDATE_FULFILLED = 'SCOPED_OBJECT_UPDATE_FULFILLED';
type ScopedObjectUpdateFulfilled = {
  type: 'SCOPED_OBJECT_UPDATE_FULFILLED',
};

const SCOPED_OBJECTS_TYPED_REQUESTED = 'SCOPED_OBJECTS_TYPED_REQUESTED';
type ScopedObjectTypedRequested = {
  type: 'SCOPED_OBJECTS_TYPED_REQUESTED',
  scopedObjectType: string,
};

const SCOPED_OBJECTS_TYPED_SUCCEEDED = 'SCOPED_OBJECTS_TYPED_SUCCEEDED';
type ScopedObjectTypedSucceeded = {
  type: 'SCOPED_OBJECTS_TYPED_SUCCEEDED',
  scopedObjectType: string,
  scopedObjects: Array<ScopedObjectListItem>,
};

const SCOPED_OBJECTS_TYPED_FAILED = 'SCOPED_OBJECTS_TYPED_FAILED';
type ScopedObjectTypedFailed = {
  type: 'SCOPED_OBJECTS_TYPED_FAILED',
};

const SCOPED_OBJECTS_CLEAR = 'SCOPED_OBJECTS_CLEAR';
type ScopedObjectClear = {
  type: 'SCOPED_OBJECTS_CLEAR',
};

const SCOPED_OBJECT_DELETE_REQUESTED = 'SCOPED_OBJECT_DELETE_REQUESTED';
type ScopedObjectDeleteRequested = {
  type: 'SCOPED_OBJECT_DELETE_REQUESTED',
  scopedObjectId: number,
  scopedObjectType: string,
};

const SCOPED_OBJECT_DELETE_SUCCEEDED = 'SCOPED_OBJECT_DELETE_SUCCEEDED';
type ScopedObjectDeleteSucceeded = {
  type: 'SCOPED_OBJECT_DELETE_SUCCEEDED',
  scopedObjectType: string,
  scopedObjectIndex: number,
};

const SCOPED_OBJECT_DELETE_FAILED = 'SCOPED_OBJECT_DELETE_FAILED';
type ScopedObjectDeleteFailed = {
  type: 'SCOPED_OBJECT_DELETE_FAILED',
};

export type ScopedObjectActions = ScopedObjectRequestSucceeded |
  ScopedObjectRequested | ScopedObjectRequestFailed |
  ScopedObjectDetailsRequested | ScopedObjectDetailsFulfilled |
  ScopedObjectCreateFailed | ScopedObjectCreateSucceeded |
  ScopedObjectCreateRequested | ScopedObjectUpdateRequested |
  ScopedObjectUpdateFulfilled | ScopedObjectTypedRequested |
  ScopedObjectTypedSucceeded | ScopedObjectTypedFailed |
  ScopedObjectClear | ScopedObjectDeleteRequested |
  ScopedObjectDeleteSucceeded | ScopedObjectDeleteFailed;

export {
  SCOPED_OBJECTS_REQUEST_FAILED,
  SCOPED_OBJECTS_REQUEST_SUCCEEDED,
  SCOPED_OBJECTS_REQUESTED,
  SCOPED_OBJECTS_TYPED_REQUESTED,
  SCOPED_OBJECTS_TYPED_SUCCEEDED,
  SCOPED_OBJECTS_TYPED_FAILED,
  SCOPED_OBJECTS_CLEAR,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_DELETE_REQUESTED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
};
